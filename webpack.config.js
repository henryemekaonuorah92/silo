var webpack = require("webpack");

module.exports = {
    entry: {
        app: "./client/main.js",
        vendor: ["react"]
    },
    output: {
        filename: "public/bundle.js"
    },
    module: {
        loaders: [
            {
                test: /\.js$/,
                loader: 'babel'
            }
        ]
    },
    plugins: [
        new webpack.optimize.CommonsChunkPlugin("vendor", "public/vendor.bundle.js")
    ]
};
