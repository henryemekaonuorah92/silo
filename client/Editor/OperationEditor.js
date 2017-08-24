;
const React = require('react');
const {Column, Cell} = require('fixed-data-table');


const FilterList = require('./FilterList');
const fetch = require('../Api').fetch;

const TextCell = require('./Cell/TextCell');
const OperationDirectionCell = require('./Cell/OperationDirectionCell')
const OperationStatusCell = require('./Cell/OperationStatusCell')
const LinkCell = require('./Cell/LinkCell')
const ContextCell = require('./Cell/ContextCell')

const {Editor, PanelTable} = require('./Editor');

const DataStore = require('./DataStore');

const DownloadDataLink = require('../Common/DownloadDataLink');



/**
 * Edit a set of Operations
 * @type {*}
 */
module.exports = React.createClass({

/*
    isStatic : function(){
        return false;
    },

    componentDidMount: function () {
        this.props.operationsPromise
            .then(d=>this.setState({operations: new DataStore(d)}))
            .catch(d=>this.setState({error:d}))
    },

    handleFilterChange: function(filters){
        if (this.props.router) {
            this.props.router.setParams(filters);
        }

        this.setState({filters:filters}, this.componentDidMount);
    },
*/
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

        if (this.props.filters && this.props.filters.length > 0) {
            return fetch(
                "/silo/inventory/operation/search",
                {method: "POST", body: JSON.stringify({filters: this.props.filters, limit: -1})}
            ).then(process)
        } else {
            return process(this.props.data)
        }
    },

    render: function(){
        let {data,onFilterChange, filters} = this.props;

        let menu = null;

        if (data) {
            menu = <li><DownloadDataLink
                filename="operationExport.csv"
                exportFile={this.prepareExport}
                style={{cursor: "pointer"}}>
                Save as CSV
            </DownloadDataLink></li>;
        }

        // <NavItem onClick={()=>{this.setState({showFilter: !this.state.showFilter});}}>Filter</NavItem>
/*

         111120-303-L

        */
        let store = new DataStore(data ? data : []);
        return (
            <Editor title="OperationEditor" menu={menu}>
                {onFilterChange &&
                    <FilterList onFilterChange={onFilterChange} filters={filters} />
                }
                <PanelTable data={data}>
                    <Column
                        width={80}
                        header={data ? "# " + data.length +" ops" : "No ops"}
                        cell={<LinkCell data={store} route="operation" col="id" />}
                    />
                    <Column
                        width={120}
                        header="Type"
                        cell={<TextCell data={store} col="type" />}
                    />
                    <Column
                        width={225}
                        header="Content"
                        cell={<OperationDirectionCell data={store} />}
                    />
                    {data && data.length > 0 && data[0].batches && <Column
                        width={80}
                        header="Qty"
                        cell={({rowIndex}) => (
                            <Cell>
                                {store.getObjectAt(rowIndex).batches.map(function(batch, key){
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
                        cell={<OperationStatusCell data={store} />}
                    />
                    <Column
                        width={300}
                        header="Context"
                        cell={<ContextCell data={store} />}
                    />
                </PanelTable>
            </Editor>
        );




    }


});
