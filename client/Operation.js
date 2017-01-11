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
        this.props.cache.get('operation/'+this.props.id)
            .from(this.props.siloBasePath+"/inventory/operation/"+this.props.id)
            .onUpdate(function(value){
                this.setState({
                    data: value,
                    batches: new DataStore(value.batches)
                });
            }.bind(this))
            .refresh();
    },

    componentWillUnmount : function () {
        this.props.cache.cleanup('operation/'+this.props.id);
        // Clear cache

    },

    handleRollback: function () {
        console.log("rollback");
        this.props.cache.refresh('operation/'+this.props.id);
    },

    render: function(){
        let data = this.state.data;
        return (
            <div>
                <a className="btn btn-danger" onClick={this.handleRollback}>Rollback</a>
                <h3>Operation {this.props.id}</h3>
                {data && <div>
                        <b>Source:</b>&nbsp;{data.source ? <Link route="location" code={data.source} /> : "No source"}<br />
                        <b>Target:</b>&nbsp;{data.target ? <Link route="location" code={data.target} /> : "No target"}<br />
                        {data.location &&
                        (<span><b>Moved location:</b>&nbsp;<Link route="location" code={data.location} /><br /></span>)
                        }
                        {data.batches.length > 0 && (<div>
                                <b>Batches:</b>
                                <BatchEditor
                                    exportFilename={'operation-'+this.props.id+'-batches.csv'}
                                    batches={this.state.batches} />
                            </div>)
                        }
                </div>}
            </div>
        );
    }
});
