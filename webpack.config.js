const webpack = require('webpack');

/** @type {webpack.Configuration} */
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const BellOnBundlerErrorPlugin = require( 'bell-on-bundler-error-plugin' );

/** @type {webpack.Configuration} */
module.exports = {
  ...defaultConfig,
  entry: './src/index.tsx',
  resolve: {
    extensions: [
      '.ts',
      '.tsx',
      '.js',
      '.jsx',
      '.json',
    ]
  },
  module: {
    noParse: [
      /tests/,
      /vendor/,
    ],
    rules: [
      ...defaultConfig.module.rules,
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
  plugins: [
    ...defaultConfig.plugins,
    new BellOnBundlerErrorPlugin(),
    new webpack.ProvidePlugin({
      react: 'react',
    }),
  ],
};
