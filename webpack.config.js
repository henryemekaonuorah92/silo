var webpack = require('webpack');
var path = require('path');
var relativePath = path.resolve.bind(null, __dirname);

module.exports = {
    entry: {
        app: './client/main.js',
        vendors: [
            "bootstrap",
            "fixed-data-table",
            "moment",
            "moment-timezone",
            "prop-types",
            "react",
            "react-addons-shallow-compare",
            "react-bootstrap",
            "react-bootstrap-switch",
            "react-bootstrap-typeahead",
            "react-date-range",
            "react-dom",
            "react-emoji",
            "react-measure",
            "superagent"
        ],
    },
    output: {
        filename: '[name].js',
        path: relativePath('public')
    },
    module: {
        loaders: [
            {
                exclude: /(node_modules)/,
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
        new webpack.optimize.CommonsChunkPlugin({
            name: 'vendors'
            // async: true,
            // children: true
        }),
    ]
};
