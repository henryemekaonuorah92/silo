const React = require('react');
const Api = require('../Api');

module.exports = (WrappedComponent)=>({
    // savenged
    _onFilterChange: function(e) {
        if (!e.target.value) {
            this.setState({
                filteredDataList: null,
            });
        }

        let filterBy = e.target.value.toLowerCase();
        let size = this.props.batches.getSize();
        let filteredIndexes = [];
        for (let index = 0; index < size; index++) {
            let {product} = this.props.batches.getObjectAt(index);
            if (product.toLowerCase().indexOf(filterBy) !== -1) {
                filteredIndexes.push(index);
            }
        }

        this.setState({
            filteredDataList: new DataStoreWrapper(filteredIndexes, this.props.batches),
        });
    },

    componentWillReceiveProps: function(nextProps) {
        // Reapply filtering after a change in the props
        if (this.state.filteredDataList && this.state.filteredDataList._indexMap) {
            this.setState({
                filteredDataList: new DataStoreWrapper(this.state.filteredDataList._indexMap, nextProps.batches),
            });
        }
    },
});