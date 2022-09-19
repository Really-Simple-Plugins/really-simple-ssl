const gulp = require('gulp');
const concat = require('gulp-concat');
const cssbeautify = require('gulp-cssbeautify');
const cssuglify = require('gulp-uglifycss');
const jsuglify = require('gulp-uglify');
const sass = require('gulp-sass')(require('sass'));
const spawn = require('child_process').spawn;

function scssTask(cb) {
  // compile scss to css and minify
  gulp.src('./assets/css/admin.scss')
  .pipe(sass(({outputStyle: 'expanded'})).on('error', sass.logError))
  .pipe(cssbeautify())
  .pipe(gulp.dest('./assets/css'))
  .pipe(cssuglify())
  .pipe(concat('admin.min.css'))
  .pipe(gulp.dest('./assets/css'));

   cb();
}
exports.scss = scssTask

function jsTask(cb) {
  // @todo @rogier even overleggen over wat hier in moet?
  // compile js and minify
  // gulp.src('js/src/burst.js')
  // .pipe(concat('burst.js'))
  // .pipe(gulp.dest('./js/build'))
  // .pipe(concat('burst.min.js'))
  // .pipe(jsuglify())
  // .pipe(gulp.dest('./js/build'));

  cb();
}
exports.js = jsTask

function defaultTask(cb) {
  gulp.watch('./assets/css/**/*.scss', { ignoreInitial: false }, scssTask);
//   gulp.watch('./assets/js/**/*.js', { ignoreInitial: false }, jsTask);
  spawn('npm', ['start'], { cwd: 'settings', stdio: 'inherit' })
  cb();
}
exports.default = defaultTask

// function buildTask(cb) {
//   gulp.task(scssTask);
//   spawn('npm', ['build'], { cwd: 'settings', stdio: 'inherit' })
//   run('npm run build').exec()
//   cb();
// }
// exports.build = buildTask