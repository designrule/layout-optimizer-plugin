const path = require('path');
const webpack = require("webpack");
const js = {
  mode: 'development',
  entry: {
    main: path.resolve(__dirname, 'js', 'main.js')
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: '[name].js'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        use: [
          {
            loader: 'babel-loader',
          }
        ]
      }
    ]
  },
  devServer: {
    publicPath: '/dist/'
  },
};

module.exports = [js]
