/* eslint-disable @typescript-eslint/no-var-requires */
const path = require( 'path' );

const BellOnBundlerErrorPlugin = require( 'bell-on-bundler-error-plugin' );

module.exports = {
	mode: 'development',
	watch: true,
	resolve: {
		extensions: [
			'.ts',
			'.tsx',
			'.js',
			'.jsx',
			'.json',
		],
	},
	plugins: [
		new BellOnBundlerErrorPlugin(),
	],
	entry: './index.ts',
	output: {
		filename: 'index.js',
		libraryTarget: 'umd',
		path: path.resolve( 'build' ),
	},
	module: {
		rules: [
			{
				test: /\.ts(x?)$/,
				exclude: /node_modules/,
				loader: 'ts-loader',
			},
			{
				enforce: 'pre',
				test: /\.js$/,
				loader: 'source-map-loader',
			},
		],
	},
	externals: {
		react: 'react',
		reactDOM: 'reactDOM',
	},
};
