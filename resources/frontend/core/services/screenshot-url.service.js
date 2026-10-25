import axios from 'axios';
import { createScreenshotUrlCache, isUrlExpired } from '@/utils/screenshotUrl';

const cache = createScreenshotUrlCache(id =>
    axios.get(`time-intervals/${id}/screenshot-urls`, { muteError: true }).then(({ data }) => data.data),
);

/** Signed routes come as "/api/...", S3 presigned URLs are absolute */
function toAbsoluteUrl(url) {
    return new URL(url, axios.defaults.baseURL || window.location.origin).href;
}

/**
 * Temporary URL of the interval screenshot (or its thumbnail) for <img src>.
 *
 * Uses the URL of the interval payload (time-intervals/list, time-intervals/show) while it is valid;
 * otherwise (expired, or a report payload without URLs) asks the API for fresh ones.
 *
 * @param {Object} interval
 * @param {Boolean} thumbnail
 * @returns {Promise<string>}
 */
export async function getScreenshotUrl(interval, thumbnail = false) {
    const key = thumbnail ? 'thumbnail_url' : 'screenshot_url';

    if (interval[key] && !isUrlExpired(interval[key])) {
        return toAbsoluteUrl(interval[key]);
    }

    const urls = await cache.get(interval.id);
    return toAbsoluteUrl(urls[key]);
}
