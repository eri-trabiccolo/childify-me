module.exports = {
	cm: {
		options: {
			type: 'wp-plugin',
			exclude: [ 'node_modules/.*' ],
			updatePoFiles: true, // Whether to update PO files in the same directory as the POT file.
			potHeaders: {
				'report-msgid-bugs-to': 'https://github.com/eri-trabiccolo/childify-me/\n',
				'last-translator': 'Rocco',
				'language-team': 'Childify-Me translation contributors\n'
			}
		}
	}
}