/**
 * Connection options of the Reverb WebSocket endpoint as seen by the browser.
 *
 * The scheme and port follow the page the application is opened on: the server-side REVERB_SCHEME
 * describes how Laravel reaches Reverb inside the container, not how a browser reaches it through
 * a TLS-terminating proxy. host, path and port can be overridden explicitly at build time.
 *
 * @param {{protocol: string, hostname: string, port: string}} location
 * @param {{host?: string, path?: string, port?: string}} [overrides]
 * @returns {{wsHost: string, wsPath: string, wsPort: number|string, wssPort: number|string, forceTLS: boolean}}
 */
export function reverbConfig(location, overrides = {}) {
    const forceTLS = location.protocol === 'https:';
    const port = overrides.port || location.port || (forceTLS ? 443 : 80);

    return {
        wsHost: overrides.host ?? location.hostname,
        wsPath: overrides.path ?? '',
        wsPort: port,
        wssPort: port,
        forceTLS,
    };
}

export default reverbConfig;
