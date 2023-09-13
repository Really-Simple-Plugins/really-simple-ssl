const gulp = require('gulp');
const rtlcss = require('gulp-rtlcss');
const concat = require('gulp-concat');
const cssbeautify = require('gulp-cssbeautify');
const cssuglify = require('gulp-uglifycss');
const jsuglify = require('gulp-uglify');
const browserify = require("browserify");
const babelify = require("babelify");
const source = require("vinyl-source-stream");
const sass = require('gulp-sass')(require('sass'));
const spawn = require('child_process').spawn;
const buffer = require('vinyl-buffer');

function scssTask(cb) {
    // compile scss to css and minify
    gulp.src('./assets/css/admin.scss')
        .pipe(sass(({outputStyle: 'expanded'})).on('error', sass.logError))
        .pipe(cssbeautify())
        .pipe(gulp.dest('./assets/css'))
        .pipe(cssuglify())
        .pipe(concat('admin.min.css'))
        .pipe(gulp.dest('./assets/css'))
        .pipe(rtlcss())
        .pipe(gulp.dest('./assets/css/rtl'));
    cb();
}

exports.scss = scssTask

function scssPluginTask(cb) {
    // compile scss to css and minify
    gulp.src('./assets/css/rsssl-plugin.scss')
        .pipe(sass(({outputStyle: 'expanded'})).on('error', sass.logError))
        .pipe(cssbeautify())
        .pipe(gulp.dest('./assets/css'))
        .pipe(cssuglify())
        .pipe(concat('rsssl-plugin.min.css'))
        .pipe(gulp.dest('./assets/css'))
        .pipe(rtlcss())
        .pipe(gulp.dest('./assets/css/rtl'));
    cb();
}

exports.scssPlugin = scssPluginTask

function jsPluginTasks(cb) {
    console.log('jsPluginTasks');
    // compile js, transpile React JSX and minify
    return browserify({
        // Add eventlistener.js to the entries array
        entries: ['./assets/js/src/rsssl-plugin.jsx'],
        extensions: ['.jsx', '.js'],
        debug: true
    })
        .transform(babelify.configure({
            presets: ['@babel/preset-env', '@babel/preset-react']
        }))
        .bundle()
        .pipe(source('rsssl-plugin.js'))
        .pipe(buffer())
        .pipe(gulp.dest('./assets/js/build'))
        .pipe(concat('rsssl-plugin.min.js'))
        .pipe(jsuglify())
        .pipe(gulp.dest('./assets/js/build'))
        .on('error', function (err) {
            console.error(err);
            this.emit('end');
        });
    cb();
}


exports.jsPlugin = jsPluginTasks;

gulp.task('default', function () {
    return
});

function jsTask(cb) {
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
    gulp.watch('./assets/css/**/*.scss', {ignoreInitial: false}, scssTask);
    gulp.watch('./assets/css/**/*.scss', {ignoreInitial: false}, scssPluginTask);
    gulp.watch(['./assets/js/src/**/*.js', './assets/js/src/**/*.jsx'], function () {
        jsPluginTasks();
    });

  gulp.watch('./assets/js/**/*.js', { ignoreInitial: false }, jsTask);
    spawn('npm', ['start'], {cwd: 'settings', stdio: 'inherit'})
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