import axios from 'axios';

/**
 * Whether a rejected API request needs no handling in the calling component.
 *
 * - Canceled requests: leaving a page cancels its pending requests (store action cancelPendingRequests).
 * - Responses with an error status: the HTTP interceptor already reports them to the user
 *   and logs out on 401/503.
 *
 * Anything else (a network failure without a response, a bug in the calling code) is not handled
 * and should be rethrown, so it stays visible.
 *
 * @param {unknown} error
 * @returns {boolean}
 */
export function isHandledRequestError(error) {
    return axios.isCancel(error) || Boolean(error && error.response);
}
