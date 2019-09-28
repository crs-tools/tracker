/* eslint global-require: 'off' */
const CopyWebpackPlugin = require('copy-webpack-plugin');
const ExtraneousFileCleanupPlugin = require('webpack-extraneous-file-cleanup-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const webpack = require('webpack');

const env = (process.env.NODE_ENV || 'development');
const isDevelopment = (env === 'development');

module.exports = {
  mode: env,
  context: path.resolve(__dirname),
  entry: {
    main: [
      './Styles/index.scss'
    ],
  },
  output: {
    // filename: 'js/[name].js',
    path: path.resolve(__dirname, '../Public'),
    publicPath: ''
  },
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              importLoaders: 1,
              sourceMap: true,
              url: false
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              plugins: () => [
                require('autoprefixer')()
              ]
            }
          },
          {
            loader: 'sass-loader',
            options: {
              prependData: `$DEBUG: ${JSON.stringify(isDevelopment)};`
            }
          }
        ]
      }
    ]
  },
  plugins: [
    new CopyWebpackPlugin([
      {from: './Styles/images', to: 'images'}
    ]),
    new ExtraneousFileCleanupPlugin({
      extensions: ['.js', '.js.map']
    }),
    new MiniCssExtractPlugin({
      filename: 'css/[name].css'
    }),
    new webpack.DefinePlugin({
      DEBUG: JSON.stringify(isDevelopment),
      'process.env.NODE_ENV': JSON.stringify(env)
    }),
    new webpack.LoaderOptionsPlugin({
      minimize: !isDevelopment
    })
  ],
  devtool: (!isDevelopment) ?
    'nosources-source-map' :
    'cheap-module-source-map'
};
