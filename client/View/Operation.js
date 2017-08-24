;
const React = require('react');
const BatchEditor = require('../Editor/BatchEditor');
const Link = require('../Factory').Link;
const request = require('superagent');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: null,
            batches: null
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
                    batches: value.batches
                });
            }.bind(this))
            .refresh();
    },

    componentWillUnmount : function () {
        this.props.cache.cleanup('operation/'+this.props.id);
    },

    handleAction: function (action) {
        request
            .post(this.props.siloBasePath+"/inventory/operation/"+this.props.id+"/"+action)
            .send({})
            .end(()=>{
                // @todo if jqXHR.status != 201 then do something
                this.props.cache.refresh('operation/'+this.props.id);
            });
    },

    render: function(){
        let {data} = this.state;
        return (
            <div>
                <h3><span className="glyphicon glyphicon-transfer" /> Operation {this.props.id}
                    {data && (
                        <span>
                            {data.status.cancelledAt && <span className="label label-danger">Cancelled</span>}
                            {data.status.doneAt && <span className="label label-success">Executed</span>}
                        </span>
                    )}
                </h3>
                {data && <div>
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
                        data={this.state.batches}
                        writable={false} />
                </div>
            </div>
        );
    }
});
