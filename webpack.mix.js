const mix = require('laravel-mix');

require('laravel-mix-bundle-analyzer');
require('laravel-mix-compress');
require('laravel-mix-clean');
require('laravel-mix-sri');
require('laravel-mix-serve');
require('laravel-mix-workbox');

const compiler = require('./resources/frontend/compiler/index');

if (mix.isWatching()) {
  mix.bundleAnalyzer();
}

if(mix.inProduction()){
  mix.version()
    .compress();

  mix.then(() => {
    const convertToFileHash = require("laravel-mix-make-file-hash");
    convertToFileHash({
      publicPath: "public",
      manifestFilePath: "public/mix-manifest.json"
    });
  });
}

process.env.VUE_APP_VERSION = process.env.APP_VERSION;
process.env.VUE_APP_DOCKER_VERSION = process.env.IMAGE_VERSION;

mix
  .before(() => {
    compiler();
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
    output: {
      publicPath: '/',
    },
    resolve: {
      fallback: {
        path: require.resolve('path-browserify'),
      }
    },
  });
