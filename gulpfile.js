const gulp = require('gulp');
const { watch } = require('gulp');
const concat = require('gulp-concat');
const cssbeautify = require('gulp-cssbeautify');
const cssuglify = require('gulp-uglifycss');

const sass = require('gulp-sass')(require('node-sass'));

function scssTask(cb) {
  // compile scss to css and minify
  gulp.src('./assets/css/admin.scss')
  .pipe(sass(({outputStyle: 'uncompressed'})).on('error', sass.logError))
  .pipe(cssbeautify())
  .pipe(gulp.dest('./assets/css'))
  .pipe(cssuglify())
  .pipe(concat('admin.min.css'))
  .pipe(gulp.dest('./assets/css'));

  cb();
}
exports.scss = scssTask

function jsTask(cb) {
  // compile scss to css and minify
  gulp.src('./assets/css/admin.scss')
  .pipe(sass(({outputStyle: 'uncompressed'})).on('error', sass.logError))
  .pipe(cssbeautify())
  .pipe(gulp.dest('./assets/css'))
  .pipe(cssuglify())
  .pipe(concat('admin.min.css'))
  .pipe(gulp.dest('./assets/css'));

  cb();
}
exports.js = jsTask

exports.default = function() {
  // You can use a single task
  console.log('default task');
  watch('./assets/css/**/*.scss', { ignoreInitial: false }, scssTask);
  watch('./assets/css/**/*.scss', { ignoreInitial: false }, jsTask);

};