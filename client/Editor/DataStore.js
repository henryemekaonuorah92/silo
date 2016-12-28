;
class DataStore {
    constructor(data){
        this.size = data.length;
        this._data = data;
    }

    getObjectAt(/*number*/ index) /*?object*/ {
        if (index < 0 || index > this.size){
            return undefined;
        }
        if (this._data[index] === undefined) {
            throw new Error('Cannot access element '+index);
        }
        return this._data[index];
    }

    getSize() {
        return this.size;
    }
}

module.exports = DataStore;
