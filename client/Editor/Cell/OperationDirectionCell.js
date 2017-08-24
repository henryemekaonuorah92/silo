const React = require('react');
const Link = require('../../Factory').Link;
const {Cell} = require('fixed-data-table');

module.exports = ({rowIndex, data, ...props}) => {
    const {source, target, location} = data.getObjectAt(rowIndex);
    return <Cell {...props}>
        {source ? <Link route="location" code={source} /> : <span className="label label-success">CREATE</span>}
        &nbsp;&rarr;&nbsp;
        {target ? <Link route="location" code={target} /> : <span className="label label-danger">DELETE</span>}
        &nbsp;({location ? <Link route="location" code={location} /> : 'skus'})
    </Cell>
};
