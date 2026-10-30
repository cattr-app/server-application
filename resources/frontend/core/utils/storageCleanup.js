/**
 * Polling of the screenshot cleanup status (GET about/storage) for the About page.
 *
 * Without imports: the unit test loads it into a sandbox with fake timers.
 */

export const ACTIVE_CLEANUP_STATUSES = ['queued', 'running'];
export const FINISHED_CLEANUP_STATUSES = ['completed', 'skipped', 'failed'];

/**
 * Status of the cleanup in a GET about/storage response. A server without thinning.status only reports
 * whether a cleanup is running.
 */
export function cleanupStatus(storage) {
    const thinning = (storage && storage.thinning) || {};

    if (thinning.status) {
        return thinning.status;
    }

    return thinning.now ? 'running' : 'idle';
}

/**
 * Whether a cleanup is queued or running.
 */
export function isCleanupActive(storage) {
    return ACTIVE_CLEANUP_STATUSES.includes(cleanupStatus(storage));
}

/**
 * Outcome to report when a cleanup observed as active has finished: null while it has not, and for a
 * finished cleanup that was never observed as active (status left from an earlier run).
 */
export function finishedCleanupStatus(previous, current) {
    if (!isCleanupActive(previous)) {
        return null;
    }

    const status = cleanupStatus(current);

    return FINISHED_CLEANUP_STATUSES.includes(status) ? status : null;
}

/**
 * Refreshes the storage info: every `activeInterval` ms while a cleanup is queued or running (or the last
 * refresh failed), every `idleInterval` ms otherwise, and not at all while the page is hidden.
 *
 * A response is applied only if no later request has been applied already, and nothing is applied after stop().
 */
export function createStoragePoller({
    fetch,
    onUpdate,
    onError = () => {},
    isActive = isCleanupActive,
    isHidden = () => false,
    activeInterval = 10000,
    idleInterval = 60000,
    timers = { setTimeout, clearTimeout },
}) {
    let timer = null;
    let stopped = true;
    let requested = 0;
    let applied = 0;
    let failed = false;
    let active = false;

    /**
     * Replaces the pending timer with the next poll, unless the poller is stopped.
     */
    function schedule() {
        timers.clearTimeout(timer);
        timer = null;

        if (stopped) {
            return;
        }

        timer = timers.setTimeout(tick, active || failed ? activeInterval : idleInterval);
    }

    /**
     * Timer callback: polls now, or postpones the poll while the page is hidden.
     */
    function tick() {
        timer = null;

        if (isHidden()) {
            schedule();
            return;
        }

        refresh();
    }

    /**
     * Fetches the storage info and applies it unless a later response has been applied or the poller is stopped.
     */
    async function refresh() {
        const id = ++requested;

        try {
            const storage = await fetch();

            if (stopped || id < applied) {
                return;
            }

            applied = id;
            failed = false;
            active = isActive(storage);
            onUpdate(storage);
        } catch (error) {
            if (stopped || id < applied) {
                return;
            }

            applied = id;
            failed = true;
            onError(error);
        }

        // Only the latest request decides when to poll next
        if (id === requested) {
            schedule();
        }
    }

    return {
        start() {
            stopped = false;
            return refresh();
        },
        stop() {
            stopped = true;
            timers.clearTimeout(timer);
            timer = null;
        },
        refresh,
        /**
         * Discards the responses of requests already sent, e.g. sent before a cleanup was requested.
         */
        invalidate() {
            applied = ++requested;
        },
        setActive(value) {
            active = value;
            schedule();
        },
    };
}
