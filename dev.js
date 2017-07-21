const webpack = require("webpack");
const compiler = webpack(require("./app.webpack.config"), (err, stats) => {
    if (err) {
        console.error(err.stack || err);
        if (err.details) {
            console.error(err.details);
        }
        return;
    }

    const info = stats.toJson();

    if (stats.hasErrors()) {
        console.error(info.errors);
    }

    if (stats.hasWarnings()) {
        console.warn(info.warnings)
    }
});
const watching = compiler.watch({}, (err, stats) => {
    // Print watch/build result here...
    console.log(stats.endTime - stats.startTime);
    if (err) {
        console.error(err)
    }
});