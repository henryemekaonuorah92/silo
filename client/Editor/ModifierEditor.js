;
const React = require('react');
const {Popover} = require('react-bootstrap');
const {Navbar} = require('./Editor');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            modifiers: [],
        };
    },

    getDefaultProps: function() {
        return {

        };
    },

    propTypes: {
        endpoint: React.PropTypes.string.isRequired,
        cache: React.PropTypes.object.isRequired,
        modifierFactory: React.PropTypes.object.isRequired
    },

    componentDidMount: function () {
        this.props.cache
            .get(this.props.endpoint)
            .from(this.props.endpoint)
            .onUpdate(function(value){
                this.setState({
                    modifiers: value
                });
            }.bind(this))
            .refresh();
    },

    componentWillUnmount : function () {
        this.props.cache.cleanup('operation/'+this.props.id);
    },

    getPopover: function() {
        return null;
        return (<Popover id="popover-positioned-left" title="Add a new Modifier">
            Select one of the following modifier:<br />
        <button className="btn btn-default">Store</button><br />
            <button className="btn btn-default">USink</button><br />
            <button className="btn btn-default">USource</button>
            </Popover>);
    },

    render: function(){
        return (
            <div className="panel panel-default">
                <Navbar title="ModifierEditor"/>

                <div className="panel-body">
                    {this.state.modifiers.map(function(modifier, key){
                        return (
                            <div key={key}>
                                {this.props.modifierFactory.make(modifier.name, modifier.value, this.props)}
                            </div>
                        );
                    }.bind(this))}
                </div>
            </div>
        );
    }
});
