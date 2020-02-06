module.exports = function (grunt) {
  grunt.initConfig({
    sass: {
      dist: {
        files: {
          'assets/octavia.style.css': 'assets/octavia.style.scss',
          'assets/overrides.css': 'assets/overrides.scss'
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-sass');

  grunt.registerTask('default', ['sass']);
};
