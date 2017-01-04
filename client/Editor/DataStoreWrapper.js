;
class DataStoreWrapper {
    constructor(indexMap, data) {
        this._indexMap = indexMap;
        this._data = data;
    }

    getSize() {
        return this._indexMap.length;
    }

    getAll() {
        return this._indexMap.map(function(x){
            return this._data.getObjectAt(x);
        }.bind(this));
    }

    getObjectAt(index) {
        return this._data.getObjectAt(
            this._indexMap[index],
        );
    }
}

module.exports = DataStoreWrapper;
