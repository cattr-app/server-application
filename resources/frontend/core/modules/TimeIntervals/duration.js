// Duration helpers for the manual time-entry form.
// All arithmetic is done on UTC instants (epoch milliseconds), so DST transitions,
// midnight crossings and timezone switches never change the elapsed time.

export const DEFAULT_DURATION_MINUTES = 10;
export const DURATION_PRESETS = [15, 30, 60, 120];
export const MAX_DURATION_MINUTES = 9 * 60;

const MS_PER_MINUTE = 60 * 1000;
const NUMBER_PATTERN = /^\s*-?\d+(\.\d+)?\s*$/;

/**
 * Parses a duration in minutes as stored in the form (number) or typed by the user (string).
 * Returns NaN for anything that is not a plain decimal number.
 */
export function parseDuration(value) {
    if (typeof value === 'number') {
        return value;
    }

    if (typeof value !== 'string' || !NUMBER_PATTERN.test(value)) {
        return NaN;
    }

    return Number(value);
}

/**
 * @returns {string|null} error code (`invalid`, `not_positive`, `too_long`) or null for a valid duration
 */
export function validateDuration(value) {
    const minutes = parseDuration(value);

    if (!Number.isFinite(minutes)) {
        return 'invalid';
    }

    if (minutes <= 0) {
        return 'not_positive';
    }

    if (minutes > MAX_DURATION_MINUTES) {
        return 'too_long';
    }

    return null;
}

/**
 * @returns {string|null} ISO end instant, or null when the start or the duration is invalid
 */
export function addDuration(startAt, duration) {
    const start = Date.parse(startAt);

    if (!Number.isFinite(start) || validateDuration(duration) !== null) {
        return null;
    }

    return new Date(start + parseDuration(duration) * MS_PER_MINUTE).toISOString();
}

/**
 * @returns {number} elapsed minutes between two instants (may be zero, negative or NaN)
 */
export function durationBetween(startAt, endAt) {
    const start = Date.parse(startAt);
    const end = Date.parse(endAt);

    if (!Number.isFinite(start) || !Number.isFinite(end)) {
        return NaN;
    }

    return (end - start) / MS_PER_MINUTE;
}

/**
 * Formats a duration for the input; keeps whatever the user typed untouched.
 */
export function formatDuration(value) {
    if (typeof value === 'string') {
        return value;
    }

    if (typeof value !== 'number' || !Number.isFinite(value)) {
        return '';
    }

    return String(Math.round(value * 100) / 100);
}

/**
 * Keeps `start_at`, `duration` and `end_at` consistent. Every handler reacts to a single
 * user-facing event and writes only the other side of the pair, so handlers never trigger each other.
 *
 * @param {object} values form values
 * @param {function(string, *): void} setValue form setter
 */
export function createDurationHandlers(values, setValue) {
    // The form sets the default on load; fall back to it while the field is not initialized yet.
    const currentDuration = () => (values.duration === undefined ? DEFAULT_DURATION_MINUTES : values.duration);

    const recomputeEnd = (startAt, duration) => {
        const endAt = addDuration(startAt, duration);

        // An invalid duration keeps the current end; the form stays invalid until it is fixed.
        if (endAt !== null) {
            setValue('end_at', endAt);
        }
    };

    return {
        onStartChange(startAt) {
            recomputeEnd(startAt, currentDuration());
        },
        onDurationChange(duration) {
            recomputeEnd(values.start_at, duration);
        },
        onEndChange(endAt, { source } = {}) {
            // Initialization and timezone switches keep the same instants: re-derive the end from
            // start + duration instead of letting the end input overwrite the selected duration.
            if (source === 'init' || source === 'timezone') {
                recomputeEnd(values.start_at, currentDuration());
                return;
            }

            setValue('duration', durationBetween(values.start_at, endAt));
        },
    };
}

/**
 * Removes form-only fields; the `time-intervals/create` payload stays unchanged.
 */
export function toIntervalPayload(values) {
    const { duration, ...payload } = values;

    return payload;
}
