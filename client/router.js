const AmpersandRouter = require('ampersand-router')
const handlers = {
    home: require('./Operation')
};
const Router = AmpersandRouter.extend({
    routes: {
        '': 'home',
        'products(/:page)': 'products',
        'product/:slug': 'product',
        '*404': '404'
    }
});
module.exports = new Router();
module.exports.getHandler = function(name) {
    return handlers[name];
};