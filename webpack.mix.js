const mix = require('laravel-mix');

require('laravel-mix-bundle-analyzer');
require('laravel-mix-compress');
require('laravel-mix-clean');
require('laravel-mix-sri');
require('laravel-mix-serve');
require('laravel-mix-workbox');
require('laravel-mix-polyfill');

const compiler = require('./resources/frontend/compiler/index');

if (mix.isWatching()) {
    mix.bundleAnalyzer();
}

process.env.MIX_APP_VERSION = process.env.APP_VERSION;
process.env.MIX_DOCKER_VERSION = process.env.IMAGE_VERSION;
process.env.MIX_REVERB_APP_KEY = process.env.REVERB_APP_KEY;

mix.before(() => {
    compiler();
})
    .alias({
        '@': 'resources/frontend/core',
        _app: 'resources/frontend',
        _modules: 'resources/frontend/vendor_modules',
        _internal: 'resources/frontend/core/modules',
    })
    .sass('resources/frontend/core/sass/app.scss', 'dist')
    .js('resources/frontend/main.js', 'dist/app.js')
    .vue({
        extractStyles: true,
        version: 2,
        globalStyles: 'resources/frontend/core/sass/includes/variables.scss',
    })
    .polyfill({
        enabled: true,
        useBuiltIns: 'usage',
        targets: false,
    })
    .version()
    .compress()
    .serve({
        args: ['artisan', 'serve', '--host=0.0.0.0'],
        dev: false,
    })
    .generateIntegrityHash()
    .generateSW({
        swDest: 'service-worker.js',
    })
    .clean({
        cleanOnceBeforeBuildPatterns: ['**/*', '!index.php', '!robots.txt', '!vendor/**', '!favicon.ico', '!storage/**'],
    })
    .webpackConfig({
        output: {
            publicPath: '/',
            // Lazy chunks are requested by the webpack runtime without the ?id= suffix of mix-manifest.json,
            // so a CDN or browser could pair a cached chunk of an older build with a newer app.js.
            // A content hash in the file name makes every build request its own chunk URLs.
            chunkFilename: 'dist/app/[name].[contenthash:8].js',
        },
        resolve: {
            fallback: {
                path: require.resolve('path-browserify'),
                events: require.resolve('events/'),
            },
        },
    });
