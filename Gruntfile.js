/* eslint-disable */

module.exports = function (grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

	var pkg = grunt.file.readJSON('package.json');
	var config = {};

	config.pkg = pkg;

	config['convert-svg-to-png'] = {
		normal: {
			options: {
				size: {
					w: '128px',
					h: '128px'
				}
			},
			files: [
				{
					expand: true,
					src: [
						'.wordpress-org/icon.svg'
					],
					dest: '.wordpress-org/128'
				}
			]
		},
		retina: {
			options: {
				size: {
					w: '256px',
					h: '256px'
				}
			},
			files: [
				{
					src: [
						'.wordpress-org/icon.svg'
					],
					dest: '.wordpress-org/256'
				}
			]
		}
	};

	config.clean = {
		icons: Object.keys(config['convert-svg-to-png']).map(function(key){
			return config['convert-svg-to-png'][ key ].files[0].dest;
		})
	};

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

	config.rename = {
		icons:{
			expand: true,
			src: [
				'.wordpress-org/*/icon.png'
			],
			rename: function (dest,src) {
				return src.replace(/.wordpress-org\/(\d+)\/icon.png/,'.wordpress-org/icon-$1x$1.png');
			}
		}
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

	grunt.registerTask('icons', [
		'convert-svg-to-png',
		'rename:icons',
		'clean:icons'
	]);

	grunt.registerTask('default', [
		'sass:dev',
		'watch'
	]);
};
