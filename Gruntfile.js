module.exports = function (grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	var pkg = grunt.file.readJSON('package.json');
	var config = {};

	config.pkg = pkg;

	config.watch = {
		options: {
			interval: 1000
		},
		css: {
			files: '**/*.scss',
			tasks: ['sass:dev']
		}
	};

	grunt.initConfig(config);

	grunt.registerTask('default', [
		'sass:dev',
		'watch'
	]);
};
