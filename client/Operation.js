;
import React from 'react';
import BatchEditor from './Editor/BatchEditor';
import DataStore from './Editor/DataStore';
import Link from './Common/Link';

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {
                batches: []
            },
            batches: new DataStore([])
        };
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            id: null
        };
    },

    componentDidMount: function () {

        this.props.cache.setCallbackWithUrl(
            'operation/'+this.props.id,
            this.props.siloBasePath+"/inventory/operation/"+this.props.id
        ).get('operation/'+this.props.id).then(function(value){
            this.setState({
                data: value,
                batches: new DataStore(value.batches)
            });
        }.bind(this));
        /*
        this.props.cache.setCallbackWithUrl(
            'locationBatch/'+this.props.code,
            this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches'
        );

        this.props.cache.get('locationBatch/'+this.props.code).then(function(value){
            this.setState({
                batches: new DataStore(value)
            });
        }.bind(this));
        */
    },

    render: function(){
        let data = this.state.data;
        return (
            <div>
                <h3>Operation {this.props.id}</h3>
                {data && <div>
                        <b>Source:</b>&nbsp;{data.source ? <Link route="location" code={data.source} /> : "No source"}<br />
                        <b>Target:</b>&nbsp;{data.target ? <Link route="location" code={data.target} /> : "No target"}<br />
                        {data.location &&
                        (<span><b>Moved location:</b>&nbsp;<Link route="location" code={data.location} /><br /></span>)
                        }
                        {data.batches.length > 0 && (<div>
                                <b>Batches:</b>
                                <BatchEditor batches={this.state.batches} />
                            </div>)
                        }
                </div>}
            </div>
        );
    }
});
