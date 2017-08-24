const React = require('react');
const {Column} = require('fixed-data-table');

const {Editor, PanelTable} = require('./Editor');

const DataStore = require('./DataStore');

const DownloadDataLink = require('../Common/DownloadDataLink');

const TextCell = require('./Cell/TextCell');
const ProductCell = require('./Cell/ProductCell');

/**
 * @todo additionalColumns !
 * @todo filter by SKU
 */
module.exports = React.createClass({

    displayName: "BatchEditor",

    getDefaultProps: () => ({
        batchColumns: ()=>null,
        additionalMenu: ()=>null // {.concat(this.props.batchColumns(batches))}
    }),

    render: function(){
        let {data} = this.props;
        let menu = null;

        if (data) {
            menu = <li><DownloadDataLink
                filename={this.props.exportFilename}
                exportFile={()=>("product,sku,quantity\n" +
                    data.map(function(d){
                        return d.product+','+d.name+','+d.quantity+"\n"
                    }).join()
                )}>
                Save as CSV
            </DownloadDataLink></li>;
        }

        let store = new DataStore(data ? data : []);
        return (
            <Editor title="BatchEditor" menu={menu}>
                <PanelTable data={data}>
                    <Column
                        width={200}
                        header="Product"
                        cell={<ProductCell data={store} />}
                    />
                    <Column
                        width={200}
                        header="Quantity"
                        cell={<TextCell data={store} col="quantity" />}
                    />
                </PanelTable>
            </Editor>
        );
    }
});
