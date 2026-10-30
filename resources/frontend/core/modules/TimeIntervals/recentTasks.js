// Recently used tasks for the manual time-entry selector.
// Only task IDs are kept, per signed-in account, in this browser's localStorage (which is
// already scoped to the application origin). The IDs are hints, never permissions:
// the selector resolves them through the authorized task API before showing them.

export const RECENT_TASKS_LIMIT = 10;

const STORAGE_PREFIX = 'time_intervals.recent_tasks.';

function toId(value) {
    const id = typeof value === 'string' && value.trim() !== '' ? Number(value) : value;
    return Number.isSafeInteger(id) && id > 0 ? id : null;
}

function defaultStorage() {
    try {
        return window.localStorage;
    } catch (e) {
        return null;
    }
}

export function storageKey(accountId) {
    const id = toId(accountId);
    return id === null ? null : STORAGE_PREFIX + id;
}

/**
 * Keeps valid unique task IDs in their original order, capped at the limit.
 */
export function normalizeTaskIds(value) {
    if (!Array.isArray(value)) {
        return [];
    }

    const ids = [];
    for (const item of value) {
        const id = toId(item);
        if (id !== null && !ids.includes(id)) {
            ids.push(id);
            if (ids.length === RECENT_TASKS_LIMIT) break;
        }
    }

    return ids;
}

/**
 * @returns {number[]} most recent first; empty when storage is unavailable or the data is malformed
 */
export function readRecentTaskIds(accountId, storage = defaultStorage()) {
    const key = storageKey(accountId);
    if (key === null || !storage) {
        return [];
    }

    try {
        return normalizeTaskIds(JSON.parse(storage.getItem(key)));
    } catch (e) {
        return [];
    }
}

/**
 * Moves the task to the top of the account's history.
 * @returns {number[]} the updated history (not persisted if storage fails)
 */
export function rememberRecentTask(accountId, taskId, storage = defaultStorage()) {
    const key = storageKey(accountId);
    const id = toId(taskId);
    if (key === null || id === null) {
        return [];
    }

    const ids = normalizeTaskIds([id, ...readRecentTaskIds(accountId, storage)]);
    if (storage) {
        try {
            storage.setItem(key, JSON.stringify(ids));
        } catch (e) {
            // Storage may be full or disabled; history is a convenience only.
        }
    }

    return ids;
}
