module.exports = function(grunt) {

	/* !edit me! */
	var globalConfig = {
		theme: 'mytheme',
		destination_dev: 'path/to/wp-content',
		destination_live: 'path/to/wp-content',
		host_dev: 'mywebsite.com',
		host_live: 'mywebsite.com'
	};
	/* !edit me! */

	grunt.initConfig({
		globalConfig: globalConfig,
		pkg: grunt.file.readJSON('package.json'),
		compass: {
			local: {
				options: {
					sassDir: 'source/wp-content/themes/<%= globalConfig.theme %>/assets/styles/sass',
					cssDir: 'source/wp-content/themes/<%= globalConfig.theme %>/assets/styles',
					imagesDir: 'source/wp-content/themes/<%= globalConfig.theme %>/assets/images',
					generatedImagesDir: 'source/wp-content/themes/<%= globalConfig.theme %>/assets/images'

				}
			}
		},
		watch: {
			compass: {
				files: [
					'source/wp-content/themes/<%= globalConfig.theme %>/assets/styles/**/*.{scss,sass}'
				],
				tasks: ['compass']
			}
		},
		copy: {
			target: {
				files: [
					{src: 'source/favicon.ico', dest: 'runtime/favicon.ico'},
					{src: 'source/favicon.png', dest: 'runtime/favicon.png'},
					{src: 'source/tileicon.png', dest: 'runtime/tileicon.png'},
					{src: 'source/wp-content/index.php', dest: 'runtime/wp-content/index.php'},
					{expand: true, cwd: 'source/wp-content/plugins/', src: ['**'], dest: 'runtime/wp-content/plugins'},
					{src: 'source/wp-content/themes/index.php', dest: 'runtime/wp-content/themes/index.php'},
					{expand: true, cwd: 'source/wp-content/themes/<%= globalConfig.theme %>/', src: ['**','!**/*Thumbs.db','!**/*.sass-cache','!**/*DS_Store'], dest: 'runtime/wp-content/themes/<%= globalConfig.theme %>/'}
				]
			}
		},
		concat: {
			js: {
				/* edit me! */
				src: [
					'source/wp-content/themes/<%= globalConfig.theme %>/assets/js/libraries/jquery.js',
					'source/wp-content/themes/<%= globalConfig.theme %>/assets/js/scripts/common.js',
					'source/wp-content/themes/<%= globalConfig.theme %>/assets/js/scripts/init.js'
				],
				/* edit me! */
				dest: 'runtime/wp-content/themes/<%= globalConfig.theme %>/assets/js/app.js'
			}
		},
		uglify: {
			target: {
				files: {
					'runtime/wp-content/themes/<%= globalConfig.theme %>/assets/js/app.min.js': ['runtime/wp-content/themes/<%= globalConfig.theme %>/assets/js/app.js'],
				}
			}
		},
		cssmin: {
			combine: {
				files: {
					'runtime/wp-content/themes/<%= globalConfig.theme %>/assets/styles/screen.min.css': ['source/wp-content/themes/<%= globalConfig.theme %>/assets/styles/screen.css']
				}
			}
		},
		usemin: {
			html: ['runtime/wp-content/themes/<%= globalConfig.theme %>/footer.php', 'runtime/wp-content/themes/<%= globalConfig.theme %>/header.php'],
			options: {
				dirs: ['runtime/wp-content/themes/<%= globalConfig.theme %>']
			}
		},
		imagemin: {
			main: {
				files: [{
					expand: true,
					cwd: 'source/wp-content/themes/<%= globalConfig.theme %>/assets/',
					src: ['**/*.{png,jpg,gif}'],
					dest: 'runtime/wp-content/themes/<%= globalConfig.theme %>/assets/'
				}]
			}
		},
		clean: [
			"runtime/wp-content/themes/<%= globalConfig.theme %>/assets/js/app.js",
			"runtime/wp-content/themes/<%= globalConfig.theme %>/assets/styles/sass/",
			"runtime/wp-content/themes/<%= globalConfig.theme %>/assets/styles/screen.css",
			"runtime/wp-content/themes/<%= globalConfig.theme %>/assets/js/scripts"
		],
		'ftp-deploy': {
			dev: {
				auth: {
					host: '<%= globalConfig.host_dev %>',
					port: 21,
					authKey: 'dev'
				},
				src: 'runtime/wp-content/',
				dest: '<%= globalConfig.destination_dev %>',
				exclusions: ['runtime/wp-content/uploads/**','runtime/**/.DS_Store', 'runtime/**/Thumbs.db']
			},
			live: {
				auth: {
					host: '<%= globalConfig.host_live %>',
					port: 21,
					authKey: 'live'
				},
				src: 'runtime/wp-content/',
				dest: '<%= globalConfig.destination_live %>',
				exclusions: ['runtime/wp-content/uploads/**','runtime/**/.DS_Store', 'runtime/**/Thumbs.db']
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-usemin');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-ftp-deploy');
	grunt.loadNpmTasks('grunt-gitinfo');
	grunt.loadNpmTasks('grunt-contrib-imagemin');

	grunt.registerTask('build', ['gitinfo','compass:local','copy','concat','uglify','cssmin','usemin','imagemin:main','clean']);
	grunt.registerTask('deploy', ['gitinfo','compass:local','copy','concat','uglify','cssmin','usemin','imagemin:main','clean','ftp-deploy:dev']);
	grunt.registerTask('launch', ['gitinfo','compass:local','copy','concat','uglify','cssmin','usemin','imagemin:main','clean','ftp-deploy:live']);

};