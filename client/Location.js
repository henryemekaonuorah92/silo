;
const React = require('react');
const BatchEditor = require('./Editor/BatchEditor');
const OperationEditor = require('./Editor/OperationEditor');
const DataStore = require('./Editor/DataStore');
const Link = require('./Common/Link');
const ModifierEditor = require('./ModifierEditor');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {},
            batches: new DataStore([]),
            operations: new DataStore([]),
        };
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            code: 'root'
        };
    },

    propTypes: {
        cache: React.PropTypes.object.isRequired
    },

    componentDidMount: function () {
        this.props.cache.get('location/'+this.props.code)
            .from(this.props.siloBasePath+"/inventory/location/"+this.props.code)
            .onUpdate(function(value){
                this.setState({
                    data: value
                });
            }.bind(this))
            .refresh();

        this.props.cache.get('locationBatch/'+this.props.code)
            .from(this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches')
            .onUpdate(function(value){
                this.setState({
                    batches: new DataStore(value)
                });
            }.bind(this))
            .refresh();

        this.props.cache.get('locationOperation/'+this.props.code)
            .from(this.props.siloBasePath+"/inventory/operation/", {data: {location: this.props.code}})
            .onUpdate(function(value){
                this.setState({
                    operations: new DataStore(value)
                });
            }.bind(this))
            .refresh();
    },

    refresh: function(){
        this.props.cache.refresh('locationBatch/'+this.props.code);
    },

    componentWillUnmount : function () {
        this.props.cache.cleanup('locationBatch/'+this.props.code);
        this.props.cache.cleanup('location/'+this.props.code);
    },

    render: function(){
        let data = this.state.data;
        let uploadUrl = this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches';

        return (
            <div>
                <h3><span className="glyphicon glyphicon-map-marker" />Location {this.props.code}</h3>
                {data ? (<div>
                    <b>Parent:</b>&nbsp;{data.parent ? <Link route="location" code={data.parent} /> : "No parent"}<br />
                    <b>Childs:</b>&nbsp;
                    {data.childs ? <ul>{data.childs.map(function(child, key){return <li key={key}>
                            <Link route="location" code={child} />
                        </li>;})}</ul> : "No child"
                    }<br />

                    <ModifierEditor cache={this.props.cache}
                                    siloBasePath={this.props.siloBasePath}
                                    endpoint={this.props.siloBasePath+"/inventory/location/"+this.props.code+'/modifiers'}
                                    modifierFactory={this.props.modifierFactory}
                                    code={this.props.code}
                    />
                    <BatchEditor
                        exportFilename={'location-'+this.props.code+'-batches.csv'}
                        batches={this.state.batches} uploadUrl={uploadUrl} onNeedRefresh={this.refresh} editable/>

                    <OperationEditor operations={this.state.operations} />

                </div>) : "Loading data"}
            </div>
        );
    }
});
