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
          'assets/css/raw/styles.css': 'assets/sass/main.scss'
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
            annotation: 'assets/css/raw/'
          }
        },
        files: {
          'assets/css/raw/styles.css': 'assets/css/raw/styles.css'
        }
      },
      compressed: {
        options: {
          processors: [
            cssnano()
          ],
          map: {
            inline: false,
            annotation: 'src/css/'
          }
        },
        files: {
          'assets/css/styles.min.css': 'assets/css/raw/styles.css'
        }
      }
    },
    browserify: {
      options: {
        transform: [['babelify', {presets: ['@babel/env'], compact: true}]]
      },
      build: {
        files: {
          'assets/app.bundle.js': 'assets/js/app.js'
        }
      }
    },
    uglify: {
      build: {
        files: {
          'assets/app.bundle.min.js': 'assets/js/bundle/app.bundle.js'
        }
      }
    },
    concat: {
      build: {
        files: {
          'assets/app.bundle.min.js': [
            'assets/js/vendor/xm_accordion.min.js',
            'assets/js/vendor/xm_dropdown.min.js',
            'assets/js/vendor/xm_hexagon.min.js',
            'assets/js/vendor/xm_popup.min.js',
            'assets/js/vendor/xm_progressBar.min.js',
            'assets/js/vendor/xm_tab.min.js',
            'assets/js/vendor/xm_tooltip.min.js',
            'assets/app.bundle.min.js'
          ]
        }
      }
    },
    connect: {
      build: {
        options: {
          base: 'assets/',
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
        files: ['assets/*.html']
      },
      sass: {
        files: ['assets/sass/**'],
        tasks: ['styles']
      },
      js: {
        files: ['assets/js/**/*.js', '!assets/js/bundle/**', '!assets/js/vendor/*.min.js'],
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