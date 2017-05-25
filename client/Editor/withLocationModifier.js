;
const React = require('react');
const Api = require('../Api');

module.exports = (WrappedComponent)=>{
    return React.createClass({

        displayName: "withLocationModifier",

        propTypes: {
            cache: React.PropTypes.object.isRequired,
            location: React.PropTypes.string.isRequired,
            siloBasePath: React.PropTypes.string.isRequired,
        },

        getInitialState: ()=>({
            modifiers: []
        }),

        getUrl: function(){
            return this.props.siloBasePath+"/inventory/location/"+this.props.location+'/modifiers';
        },

        componentDidMount: function () {
            this.props.cache
                .get(this.getUrl())
                .from(this.getUrl())
                .onUpdate(function(value){
                    this.setState({
                        modifiers: value
                    });
                }.bind(this))
                .refresh();
        },

        componentWillUnmount : function () {
            this.props.cache.cleanup(this.getUrl());
        },

        handleSave: function(modifier){
            Api.fetch(this.getUrl(), {method: 'POST', body:JSON.stringify(modifier)}).then(()=>{
                this.props.cache.refresh(this.getUrl());
            })
        },

        handleDelete: function(name){
            Api.fetch(this.getUrl(), {method: 'DELETE', body:JSON.stringify({name:name})}).then(()=>{
                this.props.cache.refresh(this.getUrl());
            })
        },

        render: function(){
            const {cache, location, ...rest} = this.props;
            return <WrappedComponent onDelete={this.handleDelete}
                                     onSave={this.handleSave}
                                     modifiers={this.state.modifiers}
                                     {...rest} />;
        },
    });
};
