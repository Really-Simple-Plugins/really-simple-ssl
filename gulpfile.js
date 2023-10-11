const gulp = require('gulp');
const rtlcss = require('gulp-rtlcss');
const concat = require('gulp-concat');
const cssbeautify = require('gulp-cssbeautify');
const cssuglify = require('gulp-uglifycss');
const sass = require('gulp-sass')(require('sass'));
const spawn = require('child_process').spawn;

function scssTask(cb) {
    // compile scss to css and minify
    gulp.src('./assets/css/admin.scss')
        .pipe(sass({ outputStyle: 'expanded' }).on('error', sass.logError))
        .pipe(cssbeautify())
        .pipe(gulp.dest('./assets/css'))
        .pipe(cssuglify())
        .pipe(concat('admin.min.css'))
        .pipe(gulp.dest('./assets/css'))
        .pipe(rtlcss())
        .pipe(gulp.dest('./assets/css/rtl'));
    cb();
}

exports.scss = scssTask;

function scssPluginTask(cb) {
    // compile scss to css and minify
    gulp.src('./assets/css/rsssl-plugin.scss')
        .pipe(sass({ outputStyle: 'expanded' }).on('error', sass.logError))
        .pipe(cssbeautify())
        .pipe(gulp.dest('./assets/css'))
        .pipe(cssuglify())
        .pipe(concat('rsssl-plugin.min.css'))
        .pipe(gulp.dest('./assets/css'))
        .pipe(rtlcss())
        .pipe(gulp.dest('./assets/css/rtl'));
    cb();
}

exports.scssPlugin = scssPluginTask;

function defaultTask(cb) {
    gulp.watch('./assets/css/**/*.scss', { ignoreInitial: false }, scssTask);
    gulp.watch('./assets/css/**/*.scss', { ignoreInitial: false }, scssPluginTask);
    spawn('npm', ['start'], { cwd: 'settings', stdio: 'inherit' });
    cb();
}

exports.default = defaultTask;
