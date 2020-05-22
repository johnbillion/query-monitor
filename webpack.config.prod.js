const path = require( 'path' );
const BellOnBundlerErrorPlugin = require( 'bell-on-bundler-error-plugin' );

module.exports = {
  mode: 'production',
  resolve: {
    extensions: [
      '.ts',
      '.tsx',
      '.js',
      '.jsx',
      '.json',
    ]
  },
  plugins: [
    new BellOnBundlerErrorPlugin(),
  ],
  entry: './src/index.tsx',
  output: {
    filename: 'index.js',
    libraryTarget: 'commonjs2',
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
