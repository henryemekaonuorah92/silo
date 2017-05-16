const React = require('react');

module.exports = React.createClass({

    propTypes: {
        title: React.PropTypes.string,
        onAck: React.PropTypes.func
    },

    getDefaultProps: function() {
        return {
            onAck: function(){}
        };
    },

    render: function(){
        return (
            <div className="text-center">
                <span style={{fontSize: "50px"}} className="glyphicon glyphicon-ok" />
                {this.props.title && <h3>{this.props.title}</h3>}
                <button className="btn btn-block btn-success" onClick={this.props.onAck}>Continue</button>
            </div>
        );
    }
});
