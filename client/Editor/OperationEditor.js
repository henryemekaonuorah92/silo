;
const React = require('react');
const {Table, Column, Cell} = require('fixed-data-table');
const {NavDropdown,MenuItem,Navbar,Nav,NavItem} = require('react-bootstrap');
const Measure = require('react-measure');
const DataStoreWrapper = require('./DataStoreWrapper');
const TextCell = require('./TextCell');
const Datetime = require('../Common/Datetime');
const Emoji = require('react-emoji');
const Link = require('../Factory').Link;
const FilterList = require('./FilterList');
const DataStore = require('./DataStore');
const request = require('superagent');
const DownloadDataLink = require('../Common/DownloadDataLink');

/**
 * Edit a set of Operations
 * @type {*}
 */
module.exports = React.createClass({
    getInitialState: ()=>({
        dimensions: {
            width: -1,
            height: -1,
        },
        operations : new DataStore([]),
        filters: [],
        showFilter: false
    }),

    propTypes: {
        // batches: React.PropTypes.isRequired // new DataStore
        // siloBasePath: React.PropTypes.string.isRequired
    },

    isStatic : function(){
        return !!this.props.operations;
    },

    componentDidMount: function () {
        if(!this.isStatic()) {
            request
                .post("/silo/inventory/operation/search")
                .send({filters: this.state.filters})
                .set('Accept', 'application/json')
                .end((err, data) => {
                    if (data.ok) {
                        this.setState({
                            operations: new DataStore(data.body)
                        });
                    }
                });
        }
    },

    handleFilterChange: function(filters){
        this.setState({filters:filters}, this.componentDidMount);
    },

    render: function(){
        let operations = this.props.operations || this.state.operations;

        // check if batches are here or not
        let withBatches = operations.getSize() > 0 && operations.getObjectAt(0).batches;

        return (
            <div className="panel panel-default">

                <Navbar>
                    <Navbar.Header>
                        <Navbar.Brand>
                            OperationEditor
                        </Navbar.Brand>
                    </Navbar.Header>

                    <Nav>
                        <NavDropdown title="File" id="basic-nav-dropdown">
                            <li>
                                <DownloadDataLink
                                    filename={this.props.exportFilename}
                                    exportFile={function(){
                                        let header = "product,sku,quantity\n";
                                        return header + batches.getAll().map(function(data){
                                                return data.product+','+data.name+','+data.quantity
                                            }).join("\n")
                                    }}
                                    style={{cursor: "pointer"}}>
                                    Save CSV
                                </DownloadDataLink>
                            </li>
                        </NavDropdown>
                        <NavItem onClick={()=>{this.setState({showFilter: !this.state.showFilter});}}>Filter</NavItem>
                        <Navbar.Text pullRight>
                            {operations.getSize()} operations
                        </Navbar.Text>
                    </Nav>
                </Navbar>

                {this.isStatic() || this.state.showFilter &&
                    <FilterList onFilterChange={this.handleFilterChange} />
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
                    </Measure> : <div className="panel-body">Fetching or no data...</div>
                }
            </div>
        );
    }


});
