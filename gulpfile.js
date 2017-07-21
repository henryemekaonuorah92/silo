var gulp = require('gulp');
var less = require('gulp-less');

gulp.task('less', function () {
    gulp.src('./less/base.less')
        .pipe(less())
        .pipe(gulp.dest('./public'));
});

gulp.task('less-watch', function () {
    gulp.watch(['./less/**/*.less'], ['less'])
});

gulp.task('fonts', function() {
    gulp.src('./node_modules/bootstrap/fonts/**/*.{ttf,woff,woff2,eof,svg}')
        .pipe(gulp.dest('./public'));
});

gulp.task('watch', ['less', 'less-watch'], function(){});

gulp.task('build', ['fonts', 'less'], function(){});
