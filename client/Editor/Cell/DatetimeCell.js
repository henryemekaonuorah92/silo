const React = require('react');
const {Cell} = require('fixed-data-table');
const Datetime = require('../../Common/Datetime');

module.exports = ({rowIndex, data, col, ...props}) => {
    let status = data.getObjectAt(rowIndex).status;
    return (
        <Cell>
            <Datetime>{data.getObjectAt(rowIndex)[col]}</Datetime>
        </Cell>
    )
};