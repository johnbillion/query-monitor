const webpack = require('webpack');
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const BellOnBundlerErrorPlugin = require( 'bell-on-bundler-error-plugin' );

module.exports = {
  ...defaultConfig,
  plugins: [
    new BellOnBundlerErrorPlugin(),
    new webpack.ProvidePlugin({
      react: 'react',
    }),
  ],
};
