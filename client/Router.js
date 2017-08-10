const AmpersandRouter = require('ampersand-router');

module.exports = AmpersandRouter.extend({
    initialize: function (options, mount){
        AmpersandRouter.prototype.initialize.apply(this, arguments)
        if (typeof(mount) !== 'function') {
            throw "SiloRouter should have a mount function passed as second argument"
        }
        this.mount = mount; //(component, title)=>{...}
    }
});