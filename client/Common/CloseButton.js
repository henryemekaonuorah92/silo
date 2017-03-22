;
const React = require('react');

module.exports = React.createClass({
    render: function() {
        return (
            <button type="button" className="close" {...this.props}><span aria-hidden="true">&times;</span></button>
        );
    }
});