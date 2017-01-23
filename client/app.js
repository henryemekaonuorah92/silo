const React = require('react');
const Router = require('./router');

module.exports = React.createClass({
    render: function() {
        const Handler = Router.getHandler(this.props.route.name);
        return <Handler route={this.props.route} />
    }
});