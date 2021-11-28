var gulp = require('gulp');

// Include plugins (from package.json)
var plugins = require('gulp-load-plugins')();
var sass = require('gulp-sass');

//path
var source = './src'; // dossier de travail
var destination = './dist';

gulp.task('sass', function () {
    return gulp.src('assets/scss/*.scss')
    .pipe(sass())
    .pipe(gulp.dest('assets/css'));
});

gulp.task('watch', function(){
    gulp.watch('assets/scss/*.scss', gulp.series('sass')); 
    gulp.watch('assets/scss/parts/*.scss', gulp.series('sass')); 
    // Other watchers
});