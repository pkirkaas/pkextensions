'use strict';

var gulp           = require('gulp');
var concat         = require('gulp-concat');
var uglify         = require('gulp-uglify');
var sass           = require('gulp-sass');
var minify         = require('gulp-minify-css');
var sourcemaps     = require('gulp-sourcemaps');
var mainBowerFiles = require('main-bower-files');
var filter         = require('gulp-filter');
var addsrc         = require('gulp-add-src');


// Run a complete build
gulp.task('default', function() {
	gulp.start('sass');
	gulp.start('js');
	gulp.start('img');
	gulp.start('fonts');
});

// Compile, minify, and sourcemap SCSS
gulp.task('sass', function() {
	gulp.src([
    "import.scss",
    "bower_components/font-awesome/scss/font-awesome.scss",
    "bower_components/jquery-ui/themes/ui-lightness/jquery-ui.css",
    'vendor/pkirkaas/PkExtensions/src/assets/css/pkextensions.scss',
    'app/resources/assets/sass/app.scss'
  ])
	.pipe(sourcemaps.init())
	.pipe(sass().on('error', sass.logError))
	//.pipe(minify())
  .pipe(concat('styles.css'))
	.pipe(sourcemaps.write())
	.pipe(gulp.dest('public/gulped/css'));	
});

// Concat Bower js with our project js, uglify and sourcemap
gulp.task('js', function() {
	var jsFiles  = [
    'vendor/pkirkaas/PkExtensions/src/assets/js/pklib.js',
    'vendor/pkirkaas/PkExtensions/src/assets/js/pksearch.js',
    'vendor/pkirkaas/PkExtensions/src/assets/js/autocompleteFramework.js',
    'vendor/pkirkaas/PkExtensions/src/assets/js/laravel-support.js',
    'vendor/pkirkaas/PkExtensions/src/assets/js/social-support.js',
    'app/resources/assets/js/*'
  ];
    var jsFilter = filter('**/*.js', {restore: false});
    gulp.src(mainBowerFiles())
		.pipe(jsFilter)
    .pipe(addsrc.append(jsFiles))
		.pipe(sourcemaps.init())
		.pipe(concat('scripts.js'))
		.pipe(sourcemaps.write())
		//.pipe(uglify())
		.pipe(gulp.dest('public/gulped/js'));
});

gulp.task('img', function() {
	gulp.src(['vendor/pkirkaas/PkExtensions/src/assets/img/*',
    'app/resources/assets/img/*'])
		.pipe(gulp.dest('public/gulped/img'));
	gulp.src(["bower_components/jquery-ui/themes/ui-lightness/images/*"])
		.pipe(gulp.dest('public/gulped/css/images'));
});


gulp.task('fonts', function() {
	gulp.src(['bower_components/font-awesome/fonts/*'])
		.pipe(gulp.dest('public/gulped/fonts'));
});

// Watch for SCSS, IMG or JS changes
gulp.task('watch', function() {
	gulp.watch(['app/resources/assets/sass/*',
    'vendor/pkirkaas/PkExtensions/src/assets/css/*'], ['sass']);

	gulp.watch( ['app/resources/assets/img/*',
    'vendor/pkirkaas/PkExtensions/src/assets/img/*' ] , ['img']);

	gulp.watch( ['app/resources/assets/js/*',
    'vendor/pkirkaas/PkExtensions/src/assets/js/*' ] , ['js']);
});