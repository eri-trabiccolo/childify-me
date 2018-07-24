module.exports = {
	lang : {
		src: [
			'<%= paths.lang %>*.po'
		],
		overwrite: true,
		replacements: [ {
			from: /^"Project-Id-Version.* Childify Me.*$/m,
			to: '"Project-Id-Version: Childify Me <%= pkg.version %>\\n"'
		} ]
	},
	readme : {
		src: [
			'readme.txt'
		],
		overwrite: true,
		replacements: [ {
			from: /^Stable tag:.*$/m,
			to: 'Stable tag: <%= pkg.version %>'
		} ]
	},
	main_header : {
		src: [
			'childify-me.php'
		],
		overwrite: true,
		replacements: [ {
			from: /^ \* Version: .*$/m,
			to: ' * Version: <%= pkg.version %>'
		} ]
	},
	main_class : {
		src: [
			'class-childify-me.php'
		],
		overwrite: true,
		replacements: [ {
			from: /private static \$plug_version = .*$/m,
			to: 'private static $plug_version = \'<%= pkg.version %>\';'
		} ]
	},
}