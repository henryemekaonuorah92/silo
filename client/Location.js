;
import React from 'react';
import BatchEditor from './Editor/BatchEditor';
import DataStore from './Editor/DataStore';
import Link from './Common/Link';

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

    componentDidMount: function () {

        this.props.cache.setCallbackWithUrl(
            'location/'+this.props.code,
            this.props.siloBasePath+"/inventory/location/"+this.props.code
        ).get('location/'+this.props.code).then(function(value){
            this.setState({
                data: value
            });
        }.bind(this));

        this.props.cache.setCallbackWithUrl(
            'locationBatch/'+this.props.code,
            this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches'
        );

        this.props.cache.get('locationBatch/'+this.props.code).then(function(value){
            this.setState({
                batches: new DataStore(value)
            });
        }.bind(this));
    },

    refresh: function(){
        let key = 'locationBatch/'+this.props.code;
        this.props.cache.clear(key);
        this.props.cache.get(key).then(function(value){
            this.setState({
                batches: new DataStore(value)
            });
        }.bind(this));
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
                        <b>Batches:</b>
                    <BatchEditor batches={this.state.batches} uploadUrl={uploadUrl} onNeedRefresh={this.refresh} />
                </div>) : "Loading data"}
            </div>
        );
    }
});
