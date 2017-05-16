const React = require('react');

module.exports = React.createClass({

    propTypes: {
        title: React.PropTypes.string,
        onAck: React.PropTypes.func,
        description: React.PropTypes.string
    },

    getDefaultProps: function() {
        return {
            onAck: function(){},
            description: null
        };
    },

    render: function(){
        return (
            <div className="text-center">
                <span style={{fontSize: "50px"}} className="glyphicon glyphicon-remove" />
                {this.props.title && <h3>{this.props.title}</h3>}
                {this.props.description && <p>{this.props.description}</p>}
                <button className="btn btn-block btn-default" onClick={this.props.onAck}>Continue</button>
            </div>
        );
    }
});
