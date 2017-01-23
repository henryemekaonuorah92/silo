;
const React = require('react');
const {Cell} = require('fixed-data-table');
const Link = require('./../Common/Link');

module.exports = React.createClass({
    render: function(){
        let {rowIndex, data, col} = this.props;
        let location = data.getObjectAt(rowIndex)[col];
        return (<Cell>
            {location?(<Link route="location" code={location} />):''}
        </Cell>);
    }
});
