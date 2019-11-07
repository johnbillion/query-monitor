// var webpack = require("webpack");

module.exports = {
  entry : "./app.js",
  output : {
    filename: "public/bundle.js"
  },
  module:{
    rules: [
        {
          exclude: /node_modules/,
          loader: 'babel-loader',
          options: {
            presets: [
              '@babel/preset-env',
              '@babel/react',{'plugins': ['@babel/plugin-proposal-class-properties']}
              ]
          }
        }
    ]
  }
};
