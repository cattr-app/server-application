// Helpers for temporary (signed / presigned) screenshot URLs. Kept free of imports so they can be unit-tested.

// Treat a URL as expired slightly before its real expiration (clock skew, slow image loading).
export const EXPIRY_MARGIN_MS = 60 * 1000;

/**
 * Expiration time (ms) of a temporary URL: `expires` of a Laravel signed route
 * or `X-Amz-Date` + `X-Amz-Expires` of an S3 presigned URL; null when unknown.
 */
export function urlExpiresAt(url) {
    let params;
    try {
        params = new URL(url, 'http://localhost').searchParams;
    } catch (e) {
        return null;
    }

    if (params.has('expires')) {
        const expires = Number(params.get('expires'));
        return Number.isFinite(expires) ? expires * 1000 : null;
    }

    const amzDate = params.get('X-Amz-Date');
    const amzExpires = Number(params.get('X-Amz-Expires'));
    const match = amzDate && /^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z$/.exec(amzDate);
    if (match && Number.isFinite(amzExpires)) {
        const [, y, m, d, hh, mm, ss] = match.map(Number);
        return Date.UTC(y, m - 1, d, hh, mm, ss) + amzExpires * 1000;
    }

    return null;
}

/** Whether a temporary URL is expired (or its expiration is unknown) at `now` */
export function isUrlExpired(url, now = Date.now()) {
    const expiresAt = urlExpiresAt(url);
    return expiresAt === null || expiresAt - EXPIRY_MARGIN_MS <= now;
}

/**
 * Cache of fresh URLs fetched per interval id, so a thumbnail and its modal share one request.
 * `fetchUrls(id)` resolves to { screenshot_url, thumbnail_url, expires_in }.
 */
export function createScreenshotUrlCache(fetchUrls, now = () => Date.now()) {
    const entries = new Map();

    return {
        get(id, { force = false } = {}) {
            const entry = entries.get(id);
            if (!force && entry && entry.validUntil > now()) {
                return entry.promise;
            }

            const promise = Promise.resolve(fetchUrls(id));
            const created = { promise, validUntil: Infinity };
            entries.set(id, created);

            promise.then(
                urls => {
                    created.validUntil = now() + Number(urls.expires_in || 0) * 1000 - EXPIRY_MARGIN_MS;
                },
                () => {
                    if (entries.get(id) === created) {
                        entries.delete(id);
                    }
                },
            );

            return promise;
        },
        clear() {
            entries.clear();
        },
    };
}
