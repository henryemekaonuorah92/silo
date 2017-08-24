const React = require('react');
const Link = require('../../Factory').Link;
const {Cell} = require('fixed-data-table');

module.exports = ({rowIndex, data, route, col, ...props}) => (
    <Cell {...props}>
        <Link route={route} code={data.getObjectAt(rowIndex)[col]} />
    </Cell>
);
