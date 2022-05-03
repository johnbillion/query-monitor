module.exports = function (grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	var pkg = grunt.file.readJSON('package.json');
	var config = {};

	config.pkg = pkg;

	const sassFiles = {
		'assets/query-monitor.css': 'assets/query-monitor.scss'
	};
	const sassOptions = {
		implementation: require('sass'),
		sourceMap: false,
		outputStyle: 'expanded'
	};

	config.sass = {
		dev: {
			files: sassFiles,
			options: {
				...sassOptions,
				sourceMap: true,
			}
		},
		prod: {
			files: sassFiles,
			options: sassOptions,
		},
	};

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
