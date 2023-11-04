/* eslint-disable @typescript-eslint/no-var-requires */
const webpack = require( 'webpack' );
const {
	resolve,
} = require( 'path' );

/** @type {webpack.Configuration} */
module.exports = {
	entry: './src/index.tsx',
	output: {
		clean: true,
		filename: '[name].js',
		path: resolve( process.cwd(), 'build' ),
	},
	resolve: {
		extensions: [
			'.ts',
			'.tsx',
			'.js',
			'.jsx',
			'.json',
		],
	},
	module: {
		noParse: [
			/tests/,
			/vendor/,
		],
		rules: [
			{
				test: /\.ts(x?)$/,
				exclude: [
					/node_modules/,
				],
				loader: 'ts-loader',
			},
			{
				enforce: 'pre',
				test: /\.js$/,
				exclude: [
					/node_modules/,
				],
				loader: 'source-map-loader',
			},
		],
	},
};
