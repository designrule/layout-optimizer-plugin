const path = require('path');
const webpack = require("webpack");
module.exports = (env, argv) => {
  const IS_PRODUCTION = argv.mode === 'production';
  return {
    entry: {
      main: path.resolve(__dirname, 'js', 'main.js')
    },
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: '[name].js'
    },
    plugins: [
      new webpack.DefinePlugin({
        'API_URL': JSON.stringify(IS_PRODUCTION ? "https://layout-optimizer.herokuapp.com": "http://localhost:3000")
      })
      // webpack.optimize.UglifyJsPluginを削除
    ],
    module: {
      rules: [
        {
          test: /\.js$/,
          use: [
            {
              loader: 'babel-loader',
              options: {
                presets: ['babel-preset-env']
              }
            }
          ]
        }
      ]
    },
    devServer: {
      publicPath: '/dist/'
    }
  };
};
