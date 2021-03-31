module.exports = function(grunt) {

  const sass = require('node-sass'),
        cssnano = require('cssnano');

  grunt.initConfig({
    sass: {
      options: {
        implementation: sass,
        sourceMap: true
      },
      build: {
        options: {
          outputStyle: 'expanded'
        },
        files: {
          'css/raw/styles.css': 'sass/main.scss'
        }
      }
    },
    postcss: {
      autoprefix: {
        options: {
          processors: [
            require('autoprefixer')()
          ],
          map: {
            inline: false,
            annotation: 'css/raw/'
          }
        },
        files: {
          'css/raw/styles.css': 'css/raw/styles.css'
        }
      },
      compressed: {
        options: {
          processors: [
            cssnano()
          ],
          map: {
            inline: false,
            annotation: 'css/'
          }
        },
        files: {
          'css/styles.min.css': 'css/raw/styles.css'
        }
      }
    },
    browserify: {
      options: {
        transform: [['babelify', {presets: ['@babel/env'], compact: true}]]
      },
      build: {
        files: {
          'js/bundle/app.bundle.js': 'js/app.js'
        }
      }
    },
    uglify: {
      build: {
        files: {
          'app.bundle.min.js': 'js/bundle/app.bundle.js'
        }
      }
    },
    concat: {
      build: {
        files: {
          'app.bundle.min.js': [
            'js/vendor/xm_accordion.min.js',
            'js/vendor/xm_dropdown.min.js',
            'js/vendor/xm_hexagon.min.js',
            'js/vendor/xm_popup.min.js',
            'js/vendor/xm_progressBar.min.js',
            'js/vendor/xm_tab.min.js',
            'js/vendor/xm_tooltip.min.js',
            'app.bundle.min.js'
          ]
        }
      }
    },
    connect: {
      build: {
        options: {
          base: '',
          hostname: 'localhost',
          port: 8123,
          protocol: 'http',
          open: true,
          livereload: 9152
        }
      }
    },
    watch: {
      options: {
        livereload: 9152
      },
      html: {
        files: ['*.html']
      },
      sass: {
        files: ['sass/**'],
        tasks: ['styles']
      },
      js: {
        files: ['js/**/*.js', '!js/bundle/**', '!js/vendor/*.min.js'],
        tasks: ['scripts']
      }
    }
  });

  // Load tasks
  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-browserify');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-contrib-watch');

  // Register global tasks
  grunt.registerTask('default', ['styles', 'scripts', 'launch']);

  // Register custom tasks
  grunt.registerTask('styles', ['sass:build', 'postcss:autoprefix', 'postcss:compressed']);
  grunt.registerTask('scripts', ['browserify:build', 'uglify:build', 'concat:build']);
  grunt.registerTask('launch', ['connect:build', 'watch']);
};