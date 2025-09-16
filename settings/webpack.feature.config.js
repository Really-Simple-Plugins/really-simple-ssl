const path = require('path');
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const featureFolders = ['two-fa']; // Add more folders as needed
const isProduction = true;
const { ProvidePlugin, Compilation } = require('webpack');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

module.exports = {
    mode: 'production',
    ...defaultConfig,
    entry: featureFolders.reduce((entries, folder) => {
        const jsPath = path.resolve(__dirname, `../security/wordpress/${folder}/assets/js/index.js`);
        const scssPath = path.resolve(__dirname, `../security/wordpress/${folder}/assets/css/${folder}.scss`);
        // check if the file exists
        if (fs.existsSync(jsPath)) {
            entries[`${folder}/assets`] = jsPath;
        } else {
            console.error(`File ${jsPath} does not exist`);
        }
        if (fs.existsSync(scssPath)) {
            entries[`${folder}/styles`] = scssPath;
        } else {
            console.error(`File ${scssPath} does not exist`);
        }
        return entries;
    }, {}),
    output: {
        path: path.resolve(__dirname, `../assets/features/`), // Output to the features directory
        filename: '[name].min.js',
        clean: false,
    },
    // disable source maps to prevent invalid JSON errors in RtlCssPlugin
    devtool: false,
    resolve: {
        ...defaultConfig.resolve,
        modules: [
            path.resolve(__dirname, '../settings/node_modules'), // Look in settings' node_modules
            path.resolve(__dirname, '../node_modules'), // Look in the root node_modules
            'node_modules', // Fallback to default node_modules
        ],
        fallback: {
            "path": require.resolve("path-browserify"),
            "stream": require.resolve("stream-browserify"),
            "buffer": require.resolve("buffer/"),
        },
    },
    module: {
        ...defaultConfig.module,
        rules: [
            // remove default CSS/SCSS rules, including those in oneOf blocks
            ...defaultConfig.module.rules.flatMap(rule => {
                if (rule.oneOf) {
                    return [{
                        ...rule,
                        oneOf: rule.oneOf.filter(r => !(r.test && (r.test.test('.css') || r.test.test('.scss')))),
                    }];
                }
                if (rule.test && (rule.test.test('.css') || rule.test.test('.scss'))) {
                    return [];
                }
                return [rule];
            }),
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: { presets: ['@babel/preset-env'] },
                },
            },
            {
                test: /\.(css|scss)$/i,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader'
                ],
            },
        ],
    },
    plugins: [
        // keep default plugins except the RtlCssPlugin to avoid source-map parsing errors
        ...defaultConfig.plugins.filter(plugin => plugin.constructor && plugin.constructor.name !== 'RtlCssPlugin'),
        new ProvidePlugin({
            Buffer: ['buffer', 'Buffer'],
            process: 'process/browser',
        }),
        new MiniCssExtractPlugin({
            filename: '[name].min.css',
        }),
    ],
    optimization: {
        ...defaultConfig.optimization,
        splitChunks: false,
        minimize: isProduction,
        minimizer: [
            '...',
            new CssMinimizerPlugin(),
        ],
    },
    stats: {
        errors: true,
        moduleTrace: true,
        errorDetails: true,
    },
};