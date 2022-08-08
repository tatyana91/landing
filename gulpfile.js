const gulp = require('gulp');
const del = require('del');
const concat = require('gulp-concat');
const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');
const gulpIf = require('gulp-if');
const browserSync = require('browser-sync').create();
const gcmq = require('gulp-group-css-media-queries');
const imagemin = require('gulp-imagemin');
const uglify = require('gulp-uglify');
const less = require('gulp-less');
const smartGrid = require('smart-grid');
const path = require('path');

let isProd = process.argv.includes('--prod');
let isMap = process.argv.includes('--map');
let isSync = process.argv.includes('--sync');

function clean(){
	return del('./build/*', './public/css/main.css');
}

function html(){
	return gulp.src('./src/**/*.html')
			.pipe(gulp.dest('./build'))
			.pipe(gulpIf(isSync, browserSync.stream()));
}

function styles(){
	return gulp.src('./src/css/main.less')
			.pipe(gulpIf(isMap, sourcemaps.init()))
			.pipe(less())
			.pipe(autoprefixer())
			.pipe(gcmq())
			.pipe(cleanCSS())
			.pipe(gulpIf(isMap, sourcemaps.write()))
			.pipe(gulp.dest('./build/css'))
			.pipe(gulp.dest('./public/css'))
			.pipe(gulpIf(isSync, browserSync.stream()));
} 

function images(){
	return gulp.src('./src/images/**/*')
			.pipe(imagemin())
			.pipe(gulp.dest('./build/images'));
}

function fonts(){
	return gulp.src('./src/fonts/*')
			.pipe(gulp.dest('./build/fonts'));
}

function watch(){
	if (isSync) {
		browserSync.init({server: { baseDir: "./build/" }});		
	}
	
	gulp.watch('./src/css/**/*.less', styles);
	gulp.watch('./src/**/*.html', html);
	gulp.watch('./smartgrid.js', grid);

	return new Promise(function(resolve, reject) {resolve()});
}

function grid(done){
	delete require.cache[path.resolve('./smartgrid.js')];
	let options = require('./smartgrid.js');
	smartGrid('./src/css', options);
	done();
}

let build = gulp.parallel(html, styles, images, fonts);
let buildWithClean = gulp.series(clean, build);
let dev = gulp.series(buildWithClean, watch);

gulp.task('build', buildWithClean);
gulp.task('dev', dev);
gulp.task('grid', grid);
//npm run build
//npm rus dev
//npm rus ds