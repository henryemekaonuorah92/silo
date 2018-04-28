;
const React = require('react');
const BatchEditor = require('../Editor/BatchEditor');
const Link = require('../Factory').Link;
const Api = require('../Api');
const Alerts = require('../Common/Alerts');
const AlertStore = require('../Store/AlertStore');
const Modal = require('../Modal/OperationRollbackModal')
const {Row, Col, Button, Glyphicon} = require('react-bootstrap');
const Emoji = require('react-emoji')
module.exports = React.createClass({

    getInitialState: ()=>({
        data: {
            batches: null
        },
        showModal: false
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

                    {data.status.isRollbackable &&
                        <div>
                            <Button bsStyle="warning" onClick={()=>{this.setState({showModal: true})}}>Rollback</Button>
                            <Modal
                                show={this.state.showModal}
                                onHide={()=>this.setState({showModal:false})}
                                url={this.props.siloBasePath+"/inventory/operation/"+this.props.id+"/rollback"}
                                onSuccess={()=>{
                                    this.componentDidMount()
                                }} />
                        </div>
                    }
                        <b>Type:</b>&nbsp;{data.type}<br />
                        <b>Source:</b>&nbsp;{data.source ? <Link route="location" code={data.source} /> : "No source"}<br />
                        <b>Target:</b>&nbsp;{data.target ? <Link route="location" code={data.target} /> : "No target"}<br />
                        <b>Rollback:</b>&nbsp;{data.rollback ? <Link route="operation" code={data.rollback} /> : "Not rollbacked"}<br />
                        <b>Contexts:</b>&nbsp;{data.contexts && data.contexts.length > 0 ? data.contexts.map(function(context, key){
                            let value = context.value
                            return <div key={key}>
                        <Link route="operationSet" code={context.id} />
                        {value && typeof(value) === "object" && "description" in value &&
                        <div>
                            Comment {Emoji.emojify(value.description)}
                        </div>
                        }

                        {value && typeof(value) === "object" && "magentoOrderId" in value &&
                        <div>
                            Order {value.magentoOrderId} {"incrementId" in value && ' #'+value.incrementId}
                        </div>
                        }
                    </div>;
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
