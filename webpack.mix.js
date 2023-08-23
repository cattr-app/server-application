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
process.env.MIX_PUSHER_APP_KEY = process.env.PUSHER_APP_KEY;
process.env.MIX_PUSHER_SCHEME = process.env.PUSHER_SCHEME;
process.env.MIX_SENTRY_DSN = process.env.SENTRY_DSN;

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
        swDest: 'sw.js',
    })
    .clean({
        cleanOnceBeforeBuildPatterns: ['**/*', '!index.php', '!robots.txt', '!vendor/**', '!favicon.ico', '!storage/**'],
    })
    .webpackConfig({
        output: {
            publicPath: '/',
        },
        resolve: {
            fallback: {
                path: require.resolve('path-browserify'),
            },
        },
    });
