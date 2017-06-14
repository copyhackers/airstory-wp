module.exports = function(grunt) {

	grunt.initConfig({
		copy: {
			main: {
				src: [
					'assets/**',
					'includes/**',
					'languages/**',
					'airstory.php',
					'readme.txt',
					'uninstall.php',
					'composer.json',
					'CHANGELOG.md',
					'LICENSE.md',
				],
				dest: 'dist/'
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: 'languages/',
					exclude: [
						'dist',
						'node_modules',
						'tests',
						'vendor',
					],
					mainFile: 'airstory.php',
					type: 'wp-plugin',
					updateTimestamp: false,
					updatePoFiles: true
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-composer');

	grunt.registerTask('build', ['composer:install', 'i18n', 'copy']);
	grunt.registerTask('i18n', ['makepot']);
};
