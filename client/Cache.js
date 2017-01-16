/**
 * Naive in memory cache implementation
 * @type {Cache}
 */
var Cache = function(){
    this._cached = [];
    this._cb = [];
};

Cache.prototype = {
    has: function(key){
        return key in this._cached;
    },
    /**
     * @param key
     * @param cb If provided, will be used to set the data if its null
     * @returns Promise
     */
    get: function(key, cb){
        return new Promise(
            function(resolve, reject) {
                let value = this._cached[key];
                if (typeof value === 'undefined' && typeof cb === 'function') {
                    (new Promise(cb)).then(function(value){
                        this.set(key, value);
                        resolve(value);
                    }.bind(this));
                } else if (typeof value === 'undefined' && typeof this._cb[key] === 'function') {
                    (new Promise(this._cb[key])).then(function(value){
                        this.set(key, value);
                        resolve(value);
                    }.bind(this));
                } else {
                    resolve(value);
                }
            }.bind(this)
        );
    },
    clear: function(key) {
        delete this._cached[key];
    },
    set: function(key, value){
        console.log('CACHE '+key);
        this._cached[key] = value;
    },
    setCallback: function(key, callback){
        this._cb[key] = callback;
        return this;
    },
    setCallbackWithUrl: function(key, url){
        return this.setCallback(key, function(resolve, reject){
            $.ajax(
                url,
                {
                    success: function (data) {
                        resolve(data);
                    },
                    headers: {'Accept': 'application/json'}
                }
            );
        })
    }
};

module.exports = Cache;
