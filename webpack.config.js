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
            },
            {
                test: /\.less$/,
                use: ['style-loader', 'css-loader', 'less-loader'],
            },
            // @todo Meh this part is not working for some reason :/
            {
                test: /\.(ttf|eot|woff|woff2)$/,
                loader: 'file-loader',
                options: {
                    name: 'fonts/[name].[ext]',
                },
            },
        ]
    },
    plugins: [
        new webpack.DllReferencePlugin({
            context: '.',
            manifest: relativePath('public')+'/vendors-manifest.json',
        }),
    ]
};
