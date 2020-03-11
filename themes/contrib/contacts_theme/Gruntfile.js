module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    sass: {
      dist: {
        options: {
          loadPath: [
            'node_modules/bootstrap/scss'
          ]
        },
        files: [
          {
            expand: true,
            cwd: 'scss',
            src: '**/*.scss',
            dest: 'css',
            ext: '.css'
          }
        ]
      }
    },
    autoprefixer:{
      dist:{
        options: {
          map: true
        },
        files:[
          {
            expand: true,
            cwd: 'css',
            src: '**/*.css',
            dest: 'css',
            ext: '.css'
          }
        ]
      }
    },
    watch: {
      css: {
        files: '**/*.scss',
        tasks: ['sass', 'autoprefixer']
      }
    },
    copy: {
      bootstrap:
        {
          expand: true,
          cwd: 'node_modules/bootstrap/js/dist',
          src: [
            'util.js',
            'dropdown.js',
            'collapse.js'
          ],
          dest: 'js'
        }
    }
  });
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-autoprefixer');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.registerTask('default',['watch']);
  grunt.registerTask('once',['sass', 'autoprefixer', 'copy']);
}
