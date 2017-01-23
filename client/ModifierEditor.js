;
const React = require('react');
const {Popover} = require('react-bootstrap');

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
        cache: React.PropTypes.object.isRequired
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
        let modifiers = this.state.modifiers;

        return (
            <div>
                {this.state.modifiers.map(function(modifier, key){
                    const popover = this.getPopover(modifier);
                    return (popover ?
                        <OverlayTrigger key={key} trigger="hover" placement="bottom" overlay={popoverLeft}>
                            <span className="label label-success">store</span>
                        </OverlayTrigger> :
                        <span key={key} className="label label-success">{modifier.name}</span>
                    );
                }.bind(this))}
            </div>
        );
    }
});
