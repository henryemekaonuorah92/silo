;
import React from 'react';
import BatchEditor from './Editor/BatchEditor';
import DataStore from './Editor/DataStore';
import Link from './Common/Link';
import ModifierEditor from './ModifierEditor';

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {},
            batches: new DataStore([])
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
                <h3>{this.props.code}</h3>
                {data ? (<div>
                        <b>Parent:</b>&nbsp;{data.parent ? <Link route="location" code={data.parent} /> : "No parent"}<br />
                        <b>Childs:</b>&nbsp;
                        {data.childs ? <ul>{data.childs.map(function(child, key){return <li key={key}>
                                <Link route="location" code={child} />
                            </li>;})}</ul> : "No child"
                        }<br />
                        <ModifierEditor cache={this.props.cache}
                                        endpoint={this.props.siloBasePath+"/inventory/location/"+this.props.code+'/modifiers'} /><br />
                        <b>Batches:</b>
                    <BatchEditor
                        exportFilename={'location-'+this.props.code+'-batches.csv'}
                        batches={this.state.batches} uploadUrl={uploadUrl} onNeedRefresh={this.refresh} editable/>
                </div>) : "Loading data"}
            </div>
        );
    }
});
