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
const {Glyphicon} = require('react-bootstrap')
const DownloadDataLink = require('../Common/DownloadDataLink');
const FilterItem = require('./FilterItem');
/**
 * Edit a set of Operations
 * @type {*}
 */
module.exports = React.createClass({

    prepareExport: function(){
        let process = operations => {
            let data = [
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
                "contextId",
                "contextDescription"
            ].join(',')+"\n";

            operations.map(op => {
                if (op.batches && op.batches.length > 0) {
                    op.batches.map(b => {
                        data += [
                            op.id,
                            op.type,
                            op.source,
                            op.target,
                            op.location,
                            b.product,
                            b.quantity,
                            op.status.requestedAt,
                            op.status.requestedBy,
                            op.status.cancelledAt,
                            op.status.cancelledBy,
                            op.status.doneAt,
                            op.status.doneBy,
                            op.contexts.map(ctx=>ctx.id).join(' '),
                            op.contexts.map(ctx=>ctx.value ?
                                (ctx.value.description || ctx.value.magentoOrderId) : ''
                            ).join(' ')
                        ].join(',') + "\n"
                    })
                } else {
                    data += [
                        op.id,
                        op.type,
                        op.source,
                        op.target,
                        op.location,
                        '',
                        '',
                        op.status.requestedAt,
                        op.status.requestedBy,
                        op.status.cancelledAt,
                        op.status.cancelledBy,
                        op.status.doneAt,
                        op.status.doneBy,
                        op.contexts.map(ctx=>ctx.id).join(' '),
                        op.contexts.map(ctx=>ctx.value ?
                            (ctx.value.description || ctx.value.magentoOrderId) : ''
                        ).join(' ')
                    ].join(',') + "\n"
                }
            })
            return data
        };

        if (this.props.filters && this.props.filters.length > 0) {
            return fetch(
                "/silo/inventory/operation/search",
                {method: "POST", body: JSON.stringify({filters: this.props.filters, limit: -1, forceBatches: 1})}
            ).then(process)
        } else {
            return process(this.props.data)
        }
    },

    render: function(){
        let {data, onFilterChange, filters} = this.props;

        let menu = [
            <li><DownloadDataLink
                filename="operationExport.csv"
                exportFile={this.prepareExport}
                style={{cursor: "pointer"}}>
                {filters && filters.length > 0 ? <span>Save All as CSV <Glyphicon glyph='hourglass' /></span> : 'Save as CSV'}
            </DownloadDataLink></li>
        ];

        let store = new DataStore(data ? data : []);
        return (
            <Editor title="OperationEditor" menu={menu}>
                {onFilterChange &&
                    <FilterList onFilterChange={onFilterChange} filters={filters} item={FilterItem} default="cancelledAt"/>
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
