;
const React = require('react');
const BatchEditor = require('../Editor/BatchEditor');
const Link = require('../Factory').Link;
const Api = require('../Api');
const Alerts = require('../Common/Alerts');
const AlertStore = require('../Store/AlertStore');

module.exports = React.createClass({

    getInitialState: ()=>({
        data: {
            batches: null
        }
    }),

    componentDidMount: function () {
        AlertStore.clear();
        Api.fetch(this.props.siloBasePath+"/inventory/operation/"+this.props.id)
            .then(data=>this.setState({data: data}))
            .catch(msg=>AlertStore.error(msg.message))
    },

    handleAction: function (action) {
        AlertStore.clear();
        Api.fetch(
            this.props.siloBasePath+"/inventory/operation/"+this.props.id+"/"+action,
            {method: "POST", body:"null"}
        )
            .then(this.componentDidMount.bind(this))
            .catch(msg=>AlertStore.error(msg.message))
    },

    render: function(){
        let {data} = this.state;
        return (
            <div>
                <Alerts />
                <h3><span className="glyphicon glyphicon-transfer" /> Operation {this.props.id}
                    {data.status && (
                        <span>
                            {data.status.cancelledAt && <span className="label label-danger">Cancelled</span>}
                            {data.status.doneAt && (
                                data.rollback ? <span className="label label-warning">Rollbacked</span>: <span className="label label-success">Executed</span>
                            )}
                        </span>
                    )}
                </h3>
                {data.status && <div>
                    {data.status.isPending && <div>
                        <button className="btn btn-success" onClick={this.handleAction.bind(this, 'execute')}>Execute</button>
                        <button className="btn btn-danger" onClick={this.handleAction.bind(this, 'cancel')}>Cancel</button>
                    </div>}

                    {data.status.isRollbackable &&<div>
                        <button className="btn btn-warning" onClick={this.handleAction.bind(this, 'rollback')}>Rollback</button>
                    </div>
                    }

                        <b>Source:</b>&nbsp;{data.source ? <Link route="location" code={data.source} /> : "No source"}<br />
                        <b>Target:</b>&nbsp;{data.target ? <Link route="location" code={data.target} /> : "No target"}<br />
                        <b>Rollback:</b>&nbsp;{data.rollback ? <Link route="operation" code={data.rollback} /> : "Not rollbacked"}<br />
                        <b>Contexts:</b>&nbsp;{data.contexts && data.contexts.length > 0 ? data.contexts.map(function(context, key){
                            return <Link key={key} route="operationSet" code={context.id} />;
                        }) : "No context"}<br />
                        {data.location &&
                        (<span><b>Moved location:</b>&nbsp;<Link route="location" code={data.location} /><br /></span>)
                        }
                </div>}
                <div>
                    <BatchEditor
                        exportFilename={'operation-'+this.props.id+'-batches.csv'}
                        data={data.batches} />
                </div>
            </div>
        );
    }
});
