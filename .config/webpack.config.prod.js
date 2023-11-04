/* eslint-disable @typescript-eslint/no-var-requires */
const webpack = require( 'webpack' );

const config = require( './webpack.config.shared' );

/** @type {webpack.Configuration} */
module.exports = {
	...config,
	mode: 'production',
};
