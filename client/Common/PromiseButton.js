;
const React = require('react');
const {Button} = require('react-bootstrap');

/**
 * Button that awaits for a Promise to return...
 */
module.exports = React.createClass({

    propTypes: {
        onClick: React.PropTypes.func,
        disabled: React.PropTypes.bool
    },

    getInitialState: () => ({
        wip: false
    }),

    handleClick: function(e){
        this.setState({wip: true}, ()=>{
            const promise = this.props.onClick(e);
            const done = ()=>{
                this.setState({wip: false});
            };
            promise.then(done).catch(done);
        });
    },

    render: function() {
        let rest = Object.assign({}, this.props);
        delete rest.disabled; delete rest.onClick;
        return (
            <Button onClick={this.handleClick}  disabled={this.state.wip || this.props.disabled} {...rest}>
                {this.state.wip && <span className="glyphicon glyphicon-refresh spinning" />} {this.props.children}
            </Button>
        );
    }
});