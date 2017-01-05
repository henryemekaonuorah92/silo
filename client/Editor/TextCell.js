;
import React from 'react';
import {Cell} from 'fixed-data-table';

module.export = ({rowIndex, data, col}) => {
    return (<Cell>
        {data.getObjectAt(rowIndex)[col]}
    </Cell>);
};
