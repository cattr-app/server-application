const mix = require('laravel-mix');

require('laravel-mix-bundle-analyzer');
require('laravel-mix-compress');
require('laravel-mix-clean');
require('laravel-mix-sri');
require('laravel-mix-serve');
require('laravel-mix-workbox');

if (mix.isWatching()) {
  mix.bundleAnalyzer();
}

process.env.VUE_APP_VERSION = process.env.APP_VERSION;
process.env.VUE_APP_DOCKER_VERSION = process.env.IMAGE_VERSION;

mix
  .before(() => {
    console.log('I will be logged before the compilation begins.');
  })
  .alias({
  '@': 'resources/frontend/core',
  '_app': 'resources/frontend',
  '_modules': 'resources/frontend/vendor_modules',
  '_internal': 'resources/frontend/core/modules',
  })
  .sass('resources/frontend/core/sass/app.scss', 'dist')
  .js('resources/frontend/main.js', 'dist/app.js')
  .vue({
    extractStyles: true,
    version: 2,
    globalStyles: 'resources/frontend/core/sass/includes/variables.scss'
  })
  .version()
  .compress()
  .serve({
    args: ['artisan', 'serve', '--host=0.0.0.0'],
    dev: false,
  })
  .generateIntegrityHash()
  .generateSW({
    swDest: 'sw.js'
  })
  .clean({
    cleanOnceBeforeBuildPatterns: [
      '**/*',
      '!index.php',
      '!robots.txt',
      '!vendor/**',
      '!favicon.ico'
    ],
  })
  .webpackConfig({
    resolve: {
      fallback: {
        path: require.resolve('path-browserify'),
      }
    },
  });
