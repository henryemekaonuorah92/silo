/**
 * Naive in memory cache implementation
 * @type {Cache}
 */

let CacheNode = function(value){
    this._value = value;
    this._refreshCb = null;
    this._cb = null;
}

CacheNode.prototype = {
    from: function(){
        let from = arguments[0];
        if (typeof from === 'function') {
            throw "not implemented yet";
        } else if (typeof from === 'string') {
            this._refreshCb = function(resolve, reject){
                $.ajax(from, {headers: {'Accept': 'application/json'}})
                    .done(function(data){resolve(data);})
                    .error(function(){console.log(arguments)});
            };
        } else {
            throw "from should have one argument, either url or callback"
        }
        return this;
    },
    onUpdate: function(callback){
        if (typeof callback !== 'function'){
            throw 'callback should be a function';
        }
        this._cb = callback;
        if (typeof this._value !== 'undefined') {
            callback(this._value);
        }
        return this;
    },
    refresh: function() {
        (new Promise(this._refreshCb)).then(function(value){
            this._value = value;
            if (typeof this._cb === 'function') {
                this._cb(value);
            }
        }.bind(this));
    },
    /**
     * Remove listeners
     */
    cleanup: function(){
        this._cb = null;
    }
};

let SmartCache = function(){
    this._nodes = [];
};

SmartCache.prototype = {
    /**
     * @param key
     */
    get: function(key){
        let node = this._nodes[key];
        if (!node) {
            this._nodes[key] = node = new CacheNode();
        }

        return node;
    },
    cleanup: function(key){
        this._nodes[key].cleanup();
        return this;
    },
    refresh: function(key){
        this._nodes[key].refresh();
        return this;
    }
};

module.exports = SmartCache;
