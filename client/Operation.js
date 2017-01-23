;
const React = require('react');
const BatchEditor = require('./Editor/BatchEditor');
const DataStore = require('./Editor/DataStore');
const Link = require('./Common/Link');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: null,
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
    },

    handleAction: function (action) {
        $.post(
            this.props.siloBasePath+"/inventory/operation/"+this.props.id+"/"+action,
            {headers: {'Accept': 'application/json'}}
        )
            .done(function(data, textStatus, jqXHR){
                // @todo if jqXHR.status != 201 then do something
                this.props.cache.refresh('operation/'+this.props.id);
            }.bind(this));

    },

    render: function(){
        /*
        // hide this temporarily
         <div className="pull-right">
         {data.status.isRollbackable &&
         <a className="btn btn-danger" onClick={this.handleAction}>Rollback</a>
         }
         {data.rollback &&
         <span>Rollbacked: <Link route="operation" code={data.rollback} /></span>
         }
         </div>
         */
        let data = this.state.data;
        return (
            <div>
                <h3><span className="glyphicon glyphicon-transfer" /> Operation {this.props.id}
                    {data && data.status.cancelledAt && <span className="label label-danger">Cancelled</span>}
                    {data && data.status.doneAt && <span className="label label-success">Executed</span>}
                </h3>
                {!data && (<span>Loading</span>)}
                {data && <div>
                    {data.status.isPending && <div>
                        <button className="btn btn-success" onClick={this.handleAction.bind(this, 'execute')}>Execute</button>
                        <button className="btn btn-danger" onClick={this.handleAction.bind(this, 'cancel')}>Cancel</button>
                    </div>}

                        <b>Source:</b>&nbsp;{data.source ? <Link route="location" code={data.source} /> : "No source"}<br />
                        <b>Target:</b>&nbsp;{data.target ? <Link route="location" code={data.target} /> : "No target"}<br />
                        <b>Rollback:</b>&nbsp;{data.rollback ? <Link route="operation" code={data.rollback} /> : "Not rollbacked"}<br />
                        <b>Contexts:</b>&nbsp;{data.contexts && data.contexts.length > 0 ? data.contexts.map(function(context){
                            return context.name+' '+context.value;
                        }).join(', ') : "No context"}<br />
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
