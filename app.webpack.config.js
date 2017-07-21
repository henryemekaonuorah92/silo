var webpack = require('webpack');
var path = require('path');
var relativePath = path.resolve.bind(null, __dirname);

module.exports = {
    entry: {
        app: './client/main.js'
    },
    output: {
        filename: '[name].js',
        path: relativePath('public')
    },
    module: {
        loaders: [
            {
                test: /\.js$/,
                loader: 'babel-loader',
                include: [
                    relativePath("client")
                ]
            }
        ]
    },
    plugins: [
        new webpack.DllReferencePlugin({
            context: '.',
            manifest: relativePath('public')+'/vendors-manifest.json',
        }),
    ]
};
