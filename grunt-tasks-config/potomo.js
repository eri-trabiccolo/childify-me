module.exports = {
	cm : {
		options: {
			poDel: false
		},
		files: [{
			expand: true,
			cwd: '<%= paths.lang %>',
			src: ['*.po'],
			dest: '<%= paths.lang %>',
			ext: '.mo',
			nonull: true
		}]
	}
}