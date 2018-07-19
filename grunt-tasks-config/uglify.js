module.exports = {
	options: {
		compress: {
			global_defs: {
				'DEBUG' : false
			},
			dead_code: true,
		},
	},
	back_js: {
		options : {
			output : {
				comments : '/^!/'
			}
		},
		files : {
			'<%= paths.back_assets %>js/cm-customizer.min.js' : [ '<%= paths.back_assets %>js/cm-customizer.js' ]
		}
	}
}