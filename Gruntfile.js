'use strict';

module.exports = function (grunt) {
  grunt.loadNpmTasks('grunt-apidoc');
  grunt.initConfig({
    apidoc: {
      mypp: {
        src: './app/',
        dest: './apidoc/',
        template: './template-apidoc/',
        options: {
          debug: true,
          includeFilters: [".*\\.php$"],
          excludeFilters: ["node_modules/"],
        }
      }
    }
  });
};
