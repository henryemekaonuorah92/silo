const React = require('react');

module.exports = React.createClass({

    propTypes: {
        title: React.PropTypes.string,
        onAck: React.PropTypes.func
    },

    getDefaultProps: function() {
        return {
            onAck: function(){},
            title: "Pending..."
        };
    },

    render: function(){
        return (
            <div className="text-center">
                <span style={{fontSize: "50px"}} className="glyphicon glyphicon-hourglass" />
                <h3>{this.props.title}</h3>
            </div>
        );
    }
});
