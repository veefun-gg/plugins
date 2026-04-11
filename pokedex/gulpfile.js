const gulp = require('gulp');
const stylus = require('gulp-stylus');
const postcss = require('gulp-postcss');
const browserSync = require('browser-sync').create();
const sourcemaps = require('gulp-sourcemaps');
const changed = require('gulp-changed');
const concat = require('gulp-concat');
const rename = require('gulp-rename');
const uglify = require('gulp-uglify');

const cssDest = './dist/css';
const cssEntry = './src/styl/pokedex.styl';
const cssWatchGlobs = './src/styl/**/*.styl';
const phpWatchGlobs = './**/*.php';

// Supported CSS contract:
//   source: src/styl/pokedex.styl
//   runtime output: dist/css/pokedex.css
function buildCss() {
	return gulp.src(cssEntry)
		.pipe(sourcemaps.init())
        .pipe(stylus())
		//.pipe(changed(cssDest))
        .pipe(postcss([
            require('autoprefixer'), 
            require('postcss-combine-media-query'), 
            require('postcss-combine-duplicated-selectors')
        ]))
		//.pipe(changed(cssDest))
		.pipe(sourcemaps.write())
        .pipe(gulp.dest(cssDest))
        .pipe(browserSync.stream())
}

function initBrowserSync() {
  browserSync.init({
    proxy: "https://primetime-pokedex.local/"
  });
}

function watchCss() {
    return gulp.watch(cssWatchGlobs, buildCss);
}

function watchFiles() {
    watchCss();
    gulp.watch(phpWatchGlobs).on('change', browserSync.reload);
}

exports.buildCss = buildCss;
exports.watchCss = watchCss;
exports.stylus = buildCss;
exports['build-css'] = buildCss;
exports['watch-css'] = watchCss;
exports['browser-sync'] = initBrowserSync;
exports.watch = watchFiles;
exports.default = gulp.parallel(buildCss, initBrowserSync, watchFiles);
