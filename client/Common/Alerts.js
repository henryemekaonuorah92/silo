;
const React = require('react');
const {Alert} = require('react-bootstrap');
const AlertStore = require('../Store/AlertStore');

/**
 * Display warning when needed
 */
module.exports = React.createClass({

    getInitialState: function() {
        return AlertStore.getState();
    },

    componentDidMount: function() {
        // when the assignment store says its data changed, we update
        AlertStore.onChange = ()=>{this.setState(AlertStore.getState());};
    },

    propTypes: {
        alerts: React.PropTypes.any
    },

    render: function() {
        // Deal with the legacy way of using props...
        let alerts = this.state.alerts;

        if (this.props.alerts) {
            if (!this.props.alerts instanceof Array) {
                alerts.push({level: warning, message: this.props.alerts});
            } else {
                this.props.alerts.map((alert) => {
                    alerts.push({level: warning, message: alert});
                });
            }
        }

        if (alerts.length === 0) {
            return null;
        } else {
            let filterByLevel = function(level) {
                return alerts.filter(al=>(al.level === level)).map(al=>al.message);
            };

            let renderLevel = function(alerts, title, bsStyle){
                return alerts.length ? <Alert bsStyle={bsStyle} >
                        <strong>{title}</strong>
                        <ul>{alerts.map((message, idx)=>(<li key={idx}>{message}</li>))}</ul>
                    </Alert> : null
            };

            return <div style={{margin:"10px 0"}}>
                { renderLevel(filterByLevel("success"), "Success !", "success") }
                { renderLevel(filterByLevel("warning"), "Warning !", "warning") }
                { renderLevel(filterByLevel("error"), "Error !", "danger") }
            </div>;
        }
    }
});
