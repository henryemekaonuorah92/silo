const React = require('react');
const {Cell} = require('fixed-data-table');
const Link = require('../../Factory').Link;

/**
 * Represents a Product
 */
module.exports = ({rowIndex, data, col, ...props}) => {
    let obj = data.getObjectAt(rowIndex);
    let code = obj.product;
    let anchor = code;
    return (
        <Cell {...props}>
            <Link route="product" code={code}>{anchor}</Link>
        </Cell>
    )
};

