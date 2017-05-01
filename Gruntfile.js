module.exports = function(grunt) {

	grunt.initConfig({
		makepot: {
			target: {
				options: {
					domainPath: 'languages/',
					exclude: [
						'node_modules',
						'tests',
						'vendor'
					],
					mainFile: 'airstory.php',
					type: 'wp-plugin',
					updateTimestamp: false,
					updatePoFiles: true
				}
			}
	}
	});

	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask('i18n', ['makepot']);
};
