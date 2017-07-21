const webpack = require('webpack');
const path = require('path');

const configuration = {
    entry: {
        'vendors': [
            "bootstrap",
            "fixed-data-table",
            "jsbarcode",
            "marked",
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
        ]
    },

    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'public'),

        // The name of the global variable which the library's
        // require() function will be assigned to
        library: '[name]_lib',
    },

    plugins: [
        new webpack.DllPlugin({
            // The path to the manifest file which maps between
            // modules included in a bundle and the internal IDs
            // within that bundle
            path: path.resolve(__dirname, 'public')+'/[name]-manifest.json',
            // The name of the global variable which the library's
            // require function has been assigned to. This must match the
            // output.library option above
            name: '[name]_lib'
        }),
    ],
}

const NODE_ENV = process.env.NODE_ENV;
if (NODE_ENV === "production") {
    configuration.plugins.push(new webpack.DefinePlugin({
        'process.env': {
            NODE_ENV: JSON.stringify('production')
        }
    }));
    configuration.plugins.push(new webpack.optimize.UglifyJsPlugin());
}

module.exports = configuration;
