const path = require('path');
const webpack = require("webpack");
module.exports = (env, argv) => {
  const IS_DEVELOPMENT = argv.mode === 'development';
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
        'API_URL': JSON.stringify(IS_DEVELOPMENT ? "http://localhost:3000":"https://layout-optimizer.herokuapp.com")
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
