;
const React = require('react');
const {Cell} = require('fixed-data-table');

module.exports = React.createClass({
    render: function(){
        let {rowIndex, data, col} = this.props;
        return (<Cell>
            <div className="text-truncate" style={{width: (this.props.width - 16) + "px"}}>{data.getObjectAt(rowIndex)[col]}</div>
        </Cell>);
    }
});
