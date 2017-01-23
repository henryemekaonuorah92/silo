;
const React = require('react');
const {Cell} = require('fixed-data-table');

module.exports = React.createClass({
    render: function(){
        let {rowIndex, data, col} = this.props;
        return (<Cell>
            {data.getObjectAt(rowIndex)[col]}
        </Cell>);
    }
});
