const React = require('react');
const Link = require('react-router-dom').Link;
const {Cell} = require('fixed-data-table');

module.exports = ({rowIndex, data, ...props}) => {
    const {source, target, location} = data.getObjectAt(rowIndex);
    return <Cell {...props}>
        {source ? <Link to={"/location/"+source}>{source}</Link> : <span className="label label-success">CREATE</span>}
        &nbsp;&rarr;&nbsp;
        {target ? <Link to={"/location/"+target}>{target}</Link> : <span className="label label-danger">DELETE</span>}
        &nbsp;({location ? <Link to={"/location/"+location}>{location}</Link> : 'skus'})
    </Cell>
};
