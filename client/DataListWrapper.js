;

class DataListWrapper {
    constructor(indexMap, data) {
        this._indexMap = indexMap;
        this._data = data;
    }

    getSize() {
        return this._indexMap ? this._indexMap.length : this._data.length;
    }

    /*
     DataListWrapper can have another layer of DataListWrapper inside the data for when you want to filter.
     This insure the level at which the data is good so you always get the right level.
     */
    getData(){
        var localData;
        if (this._data !== undefined) {
            if (this._data.constructor == DataListWrapper) {
                localData = this._data._data;
            } else {
                localData = this._data;
            }
        }
        return localData;
    }

    getObjectAt(index) {
        if (this._indexMap) {
            return this._data.getObjectAt(
                this._indexMap[index]
            );
        } else {
            return this._data[index];
        }
    }
}

module.exports = DataListWrapper;
