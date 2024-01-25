const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
    ...defaultConfig,
    output: {
        ...defaultConfig.output,
        filename: '[name].js',
        chunkFilename: '[name].js',
    },
};

