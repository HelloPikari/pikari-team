const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const editorEntry = {
	'editor/index': path.resolve( __dirname, 'src/editor/index.js' ),
};

// With --experimental-modules, wp-scripts exports an array [scriptConfig, moduleConfig].
// We add the editor entry to the script config only (not the module config).
if ( Array.isArray( defaultConfig ) ) {
	const [ scriptConfig, ...rest ] = defaultConfig;
	module.exports = [
		{
			...scriptConfig,
			entry: {
				...( typeof scriptConfig.entry === 'function'
					? scriptConfig.entry()
					: scriptConfig.entry ),
				...editorEntry,
			},
		},
		...rest,
	];
} else {
	module.exports = {
		...defaultConfig,
		entry: {
			...( typeof defaultConfig.entry === 'function'
				? defaultConfig.entry()
				: defaultConfig.entry ),
			...editorEntry,
		},
	};
}
