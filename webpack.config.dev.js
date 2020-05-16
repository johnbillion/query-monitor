const path = require( 'path' );

module.exports = {
  mode: 'development',
  watch: true,
  entry: './src/index.js',
  output: {
    filename: 'index.js',
    libraryTarget: 'umd',
    path: path.resolve( 'build' ),
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        loader: 'babel-loader',
      },
    ],
  },
  externals: {
    react: 'react',
    reactDOM: 'reactDOM',
  },
};
