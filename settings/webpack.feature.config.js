const path = require('path');
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const featureFolders = ['two-fa']; // Add more folders as needed
const isProduction = process.env.NODE_ENV === 'production';
const { ProvidePlugin, Compilation } = require('webpack');
const fs = require('fs');

module.exports = {
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
            ...defaultConfig.module.rules,
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                    },
                },
            },
        ],
    },
    plugins: [
        ...defaultConfig.plugins,
        new ProvidePlugin({
            Buffer: ['buffer', 'Buffer'],
            process: 'process/browser',
        }),
    ],
    optimization: {
        ...defaultConfig.optimization,
        minimize: isProduction,
    },
    stats: {
        errors: true,
        moduleTrace: true,
        errorDetails: true,
    },
};