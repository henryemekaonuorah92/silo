const React = require('react');
const Link = require('react-router-dom').Link;
const {Cell} = require('fixed-data-table');

module.exports = ({rowIndex, data, route, col, ...props}) => (
    <Cell {...props}>
        <Link to={"/"+route+"/"+data.getObjectAt(rowIndex)[col]}>{data.getObjectAt(rowIndex)[col]}</Link>
    </Cell>
);
