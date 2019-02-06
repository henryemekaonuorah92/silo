const React = require('react');

module.exports = React.createClass({

    propTypes: {
        title: React.PropTypes.string,
        onAck: React.PropTypes.func
    },

    getDefaultProps: function() {
        return {
            onAck: function(){},
            description: null
        };
    },

    render: function(){
        let {description} = this.props
        console.log(description)
        if(Array.isArray(description) && description.length) {
            description = <ul>
                {description.map((msg) => {return <li>{msg}</li>})}
            </ul>
        }
        return (
            <div className="text-center">
                <span style={{fontSize: "50px"}} className="glyphicon glyphicon-ok" />
                {this.props.title && <h3>{this.props.title}</h3>}
                {description && <p>{description}</p>}
                <button className="btn btn-block btn-success" onClick={this.props.onAck}>Continue</button>
            </div>
        );
    }
});
