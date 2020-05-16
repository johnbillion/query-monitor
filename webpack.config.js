const webpack = require('webpack');
const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  plugins: [
    new webpack.ProvidePlugin({
      react: 'react',
    }),
  ],
};
