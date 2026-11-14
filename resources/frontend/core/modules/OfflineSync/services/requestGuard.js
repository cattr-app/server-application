/**
 * Tells which of several overlapping asynchronous requests may still apply its result: only the latest one that
 * was started, and only while the owner (a component) is alive.
 */
export function createRequestGuard() {
    let latest = 0;
    let disposed = false;

    return {
        /** Starts a request; the requests started before no longer are current */
        begin() {
            return ++latest;
        },
        isCurrent(token) {
            return !disposed && token === latest;
        },
        /** The owner is gone: no result is current any more */
        dispose() {
            disposed = true;
        },
    };
}
