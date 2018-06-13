;
const React = require('react');

module.exports = React.createClass({

    propTypes: {
        transformations: React.PropTypes.array,
        onScan: React.PropTypes.func,
        noTransform: React.PropTypes.bool
    },

    componentWillMount: function(){
        window.scanner.on('scan', this.props.onScan); // {onScan: , noTransform:true}
    },

    componentWillUnmount: function(){
        window.scanner.off('scan', this.props.onScan);
    },

    render: function(){
        return null;
    }
});