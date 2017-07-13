;
const React = require('react');
const {Table, Column, Cell} = require('fixed-data-table');
const {NavDropdown,NavItem,Glyphicon} = require('react-bootstrap');
const Measure = require('react-measure');
const DataStoreWrapper = require('./DataStoreWrapper');
const TextCell = require('./TextCell');
const Datetime = require('../Common/Datetime');
const Emoji = require('react-emoji');
const Link = require('../Factory').Link;
const FilterList = require('./FilterList');
const fetch = require('../Api').fetch;
const DataStore = require('./DataStore');
const request = require('superagent');
const DownloadDataLink = require('../Common/DownloadDataLink');
const {Navbar, NavText} = require('./Editor');

/**
 * Edit a set of Operations
 * @type {*}
 */
module.exports = React.createClass({
    getInitialState: function(){
        let params = [];
        if (this.props.router) {
            params = this.props.router.getParams()
        }

        return {
            dimensions: {
                width: -1,
                height: -1,
            },
            operations : new DataStore([]),
            filters: params,
            showFilter: false,
            wip: false,
            error: null
        }
    },

    propTypes: {
        // batches: React.PropTypes.isRequired // new DataStore
        // siloBasePath: React.PropTypes.string.isRequired
        // routerParams
    },

    isStatic : function(){
        return !!this.props.operations;
    },

    componentDidMount: function () {
        if(!this.isStatic()) {
            this.setState({wip:true, error: null});
            request
                .post("/silo/inventory/operation/search")
                .send({filters: this.state.filters})
                .set('Accept', 'application/json')
                .end((err, data) => {
                    if (err) {
                        this.setState({
                            wip: false,
                            error: true
                        });
                    } else if (data) {
                        this.setState({
                            operations: new DataStore(data.body),
                            wip:false
                        });
                    } else {
                        this.setState({
                            wip: false,
                            error: true
                        });
                    }
                });
        }
    },

    handleFilterChange: function(filters){
        if (this.props.router) {
            this.props.router.setParams(filters);
        }

        this.setState({filters:filters}, this.componentDidMount);
    },

    prepareExport: function(){
        let process = operations => {
            let header = [
                "operationId",
                "type",
                "source",
                "target",
                "location",
                "sku",
                "quantity",
                "requestedAt",
                "requestedBy",
                "cancelledAt",
                "cancelledBy",
                "doneAt",
                "doneBy",
                "contextId"
            ].join(',')+"\n";
            return header + operations.map(function(op){
                let batch = op.batches && op.batches.pop()
                return [
                    op.id,
                    op.type,
                    op.source,
                    op.target,
                    op.location,
                    batch && batch.product,
                    batch && batch.quantity,
                    op.status.requestedAt,
                    op.status.requestedBy,
                    op.status.cancelledAt,
                    op.status.cancelledBy,
                    op.status.doneAt,
                    op.status.doneBy,
                    op.contexts.map(ctx=>ctx.id).join(' ')
                ].join(',')
            }).join("\n")
        };

        if (this.state.filters.length > 0) {
            return fetch(
                "/silo/inventory/operation/search",
                {method: "POST", body: JSON.stringify({filters: this.state.filters, limit: -1})}
            ).then(process)
        } else {
            return process((this.props.operations || this.state.operations).getAll())
        }
    },

    render: function(){
        let operations = this.props.operations || this.state.operations;

        // check if batches are here or not
        let withBatches = operations.getSize() > 0 && operations.getObjectAt(0).batches;

        return (
            <div className="panel panel-default">

                <Navbar title="OperationEditor">
                    <NavDropdown title="File" id="basic-nav-dropdown">
                        <li>
                            <DownloadDataLink
                                filename="operationExport.csv"
                                exportFile={this.prepareExport}
                                style={{cursor: "pointer"}}>
                                Save as CSV
                            </DownloadDataLink>
                        </li>
                    </NavDropdown>
                    <NavItem onClick={()=>{this.setState({showFilter: !this.state.showFilter});}}>Filter</NavItem>
                    <NavText pullRight>
                        {this.state.error && <span className="text-danger"><Glyphicon glyph="warning-sign" />Error while loading</span>}
                        &nbsp;{operations.getSize()} operations
                    </NavText>
                </Navbar>

                {this.isStatic() || this.state.showFilter &&
                    <FilterList onFilterChange={this.handleFilterChange} filters={this.state.filters} />
                }
                {operations.getSize() > 0 ?
                    <Measure onMeasure={(dimensions)=>{this.setState({dimensions});}}>
                        <div className="panel-body panel-body_noPadding">
                            {this.state.dimensions.width != -1 && (
                                <Table
                                    width={this.state.dimensions.width} // Bootstrap 15px padding on row
                                    height={Math.min(operations.getSize() + 4, 12) * 36}
                                    headerHeight={36}
                                    offsetHeight={150}
                                    rowsCount={operations.getSize()}
                                    rowHeight={36}>
                                    <Column
                                        width={80}
                                        header="#"
                                        cell={({rowIndex}) => (
                                            <Cell>
                                                <Link route="operation" code={operations.getObjectAt(rowIndex)['id']} />
                                            </Cell>
                                        )}
                                    />
                                    <Column
                                        width={120}
                                        header="Type"
                                        cell={<TextCell data={operations} col="type" />}
                                    />
                                    <Column
                                        width={225}
                                        header="Content"
                                        cell={({rowIndex}) => {
                                            const data = operations.getObjectAt(rowIndex);
                                            return <Cell>
                                                {data.source ? <Link route="location" code={data.source} /> : <span className="label label-success">CREATE</span>}
                                                &nbsp;&rarr;&nbsp;
                                                {data.target ? <Link route="location" code={data.target} /> : <span className="label label-danger">DELETE</span>}
                                                &nbsp;({data.location ? <Link route="location" code={data.location} /> : 'skus'})
                                            </Cell>;
                                        }}
                                    />
                                    {withBatches && <Column
                                        width={80}
                                        header="Qty"
                                        cell={({rowIndex}) => (
                                            <Cell>
                                                {operations.getObjectAt(rowIndex).batches.map(function(batch, key){
                                                    return <span key={key}>
                                                        {batch.quantity}
                                                    </span>
                                                        ;
                                                })}
                                            </Cell>
                                        )}
                                    />}
                                    <Column
                                        width={300}
                                        header="Status"
                                        cell={({rowIndex}) => {
                                            let status = operations.getObjectAt(rowIndex)['status'];
                                            return (
                                                <Cell>
                                                    {status.isDone && <span className="text-success">
                                                    <span className="glyphicon glyphicon-ok" /> Done <Datetime>{status.doneAt}</Datetime>&nbsp;{status.doneBy}
                                                </span>}
                                                    {status.isCancelled && <span className="text-danger">
                                                    <span className="glyphicon glyphicon-remove" /> Cancelled <Datetime>{status.cancelledAt}</Datetime>&nbsp;{status.cancelledBy}
                                                </span>}
                                                    {status.isPending && <span>
                                                    <span className="glyphicon glyphicon-time" /> Pending <Datetime>{status.requestedAt}</Datetime>&nbsp;{status.requestedBy}
                                                </span>}
                                                </Cell>
                                            )}}
                                    />
                                    <Column
                                        width={300}
                                        header="Context"
                                        cell={({rowIndex}) => (
                                            <Cell>
                                                {operations.getObjectAt(rowIndex).contexts.map(function(context, key){
                                                    return <span key={key}>
                                                        <Link route="operationSet" code={context.id} />
                                                        {typeof(context.value) === "object" && "description" in context.value &&
                                                        <span>
                                                                &nbsp;({Emoji.emojify(context.value.description)})
                                                            </span>
                                                        }&nbsp;
                                                    </span>
                                                        ;
                                                })}
                                            </Cell>
                                        )}
                                    />
                                </Table>
                            )}
                        </div>
                    </Measure> : <div className="panel-body">
                        {this.state.wip ? "Loading" : "No data"}
                    </div>
                }
            </div>
        );
    }


});
