;
import React from 'react';
import {Cell} from 'fixed-data-table';
import Link from './../Common/Link';

module.exports = React.createClass({
    render: function(){
        let {rowIndex, data, col} = this.props;
        let location = data.getObjectAt(rowIndex)[col];
        return (<Cell>
            {location?(<Link route="location" code={location} />):''}
        </Cell>);
    }
});
