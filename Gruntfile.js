module.exports = function (grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

    var pkg = grunt.file.readJSON('package.json');
	var gig = require('gitignore-globs');
	var gag = require('gitattributes-globs');
	var ignored_gitignore = gig('.gitignore', { negate: true } ).map(function(value) {
		return value.replace(/^!\//,'!');
	});
    var ignored_gitattributes = gag( '.gitattributes', { negate: true } ).map(function(value) {
		return value.replace(/^!\//,'!');
	});
	var config = {};

	config.pkg = pkg;

	config.clean = {
		main: [
			'<%= wp_deploy.deploy.options.build_dir %>'
		]
	};

	config.copy = {
		main: {
			src: [
				'**',
				'!.*',
				'!.git/**',
				'!<%= wp_deploy.deploy.options.assets_dir %>/**',
				'!<%= wp_deploy.deploy.options.build_dir %>/**',
				'!README.md',
				'!wiki/**',
				ignored_gitignore,
				ignored_gitattributes
			],
			dest: '<%= wp_deploy.deploy.options.build_dir %>/'
		}
	};

	config.sass = {
		dist: {
			options: {
				sourcemap: 'none',
				style: 'expanded'
			},
			files: {
				'assets/query-monitor-dark.css': 'assets/query-monitor-dark.scss',
				'assets/query-monitor.css': 'assets/query-monitor.scss'
			}
		}
	};

	config.version = {
		main: {
			options: {
				prefix: 'Version:[\\s]+'
			},
			src: [
				'<%= pkg.name %>.php'
			]
		},
		readme: {
			options: {
				prefix: 'Stable tag:[\\s]+'
			},
			src: [
				'readme.txt'
			]
		},
		pkg: {
			src: [
				'package.json'
			]
		}
	};

	config.watch = {
		options: {
			interval: 1000
		},
		css: {
			files: '**/*.scss',
			tasks: ['sass']
		}
	};

	config.wp_deploy = {
		deploy: {
			options: {
				deploy_trunk: true,
				deploy_tag: true,
				plugin_slug: '<%= pkg.name %>',
				build_dir: 'build',
				assets_dir: 'assets-wp-repo'
			}
		},
		assets: {
			options: {
				deploy_trunk: false,
				deploy_tag: false,
				plugin_slug: '<%= pkg.name %>',
				build_dir: '<%= wp_deploy.deploy.options.build_dir %>',
				assets_dir: '<%= wp_deploy.deploy.options.assets_dir %>'
			}
		},
		ci: {
			options: {
				skip_confirmation: true,
				force_interactive: false,
				deploy_trunk: true,
				deploy_tag: true,
				svn_user: 'johnbillion',
				plugin_slug: '<%= pkg.name %>',
				build_dir: '<%= wp_deploy.deploy.options.build_dir %>',
				assets_dir: '<%= wp_deploy.deploy.options.assets_dir %>'
			}
		}
	};

	grunt.initConfig(config);

	grunt.registerTask('bump', function(version) {
		if ( ! version ) {
			grunt.fail.fatal( 'No version specified. Usage: bump:major, bump:minor, bump:patch, bump:x.y.z' );
		}

		grunt.task.run([
			'version::' + version
		]);
	});

	grunt.registerTask('build', [
		'clean',
		'sass',
		'copy'
	]);

	grunt.registerTask('deploy', [
		'build',
		'wp_deploy'
	]);

	grunt.registerTask('deploy:assets', [
		'build',
		'wp_deploy:assets'
	]);

	grunt.registerTask('deploy:ci', [
		'build',
		'wp_deploy:ci'
	]);

	grunt.registerTask('default', [
		'sass',
		'watch'
	]);
};
