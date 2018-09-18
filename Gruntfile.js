module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		sass: {
			dist: {
				options: {
					style: 'expanded'
				},
				files: {
					'assets/query-monitor-dark.css': 'assets/query-monitor-dark.scss',
					'assets/query-monitor.css': 'assets/query-monitor.scss'
				}
			}
		},
		watch: {
			options: {
				interval: 1000
			},
			css: {
				files: '**/*.scss',
				tasks: ['sass']
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.registerTask('default', [
		'watch'
	]);
};
