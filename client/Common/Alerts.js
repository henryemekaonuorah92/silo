;
const React = require('react');
const {Alert} = require('react-bootstrap');

/**
 * Display warning when needed
 */
module.exports = React.createClass({

    propTypes: {
        alerts: React.PropTypes.array.isRequired
    },

    render: function() {
        //let rest = Object.assign({}, this.props);
        //delete rest.url; delete rest.onDataReceived;
        const alerts = this.props.alerts;
        if (alerts.length === 0) {
            return null;
        } else {
            return <Alert bsStyle="warning" style={{margin:"10px 0"}}>
                <strong>Holy guacamole!</strong>
                <ul>{alerts.map((error, idx)=>(<li key={idx}>{error}</li>))}</ul>
            </Alert>;
        }
    }
});
