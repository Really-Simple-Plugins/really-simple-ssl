const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  output: {
    ...defaultConfig.output,
    filename: '[name].[contenthash].js',
    chunkFilename: '[name].[contenthash].js',
  },
};