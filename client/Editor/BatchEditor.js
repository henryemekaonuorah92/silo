const React = require('react');
const {Column} = require('fixed-data-table');
const {FormControl} = require('react-bootstrap');
const {Editor, PanelTable} = require('./Editor');

const DataStore = require('./DataStore');

const DownloadDataLink = require('../Common/DownloadDataLink');

const TextCell = require('./Cell/TextCell');
const ProductCell = require('./Cell/ProductCell');

/**
 * @todo additionalColumns !
 */
module.exports = React.createClass({

    displayName: "BatchEditor",

    getDefaultProps: () => ({
        menu: [],
        exportFilename: "batches.csv"
    }),

    getInitialState: () => ({
        sku: '',
        filteredData: null
    }),

    handleChangeSku(event) {
        const sku = event.target.value
        let data = this.props.data;
        if(sku.length > 0) {
            data = [];
            for(var batch of this.props.data) {
                if(batch.product.toLowerCase().indexOf(sku.toLowerCase()) != -1) {
                    data.push(batch);
                }
            }
        }
        this.setState({sku: sku, filteredData: data});
    },

    render: function() {
        const filters = [
            <FormControl
                key="sku"
                type="text"
                name="sku"
                placeholder="Filter by SKU"
                onChange={this.handleChangeSku}
                value={this.state.sku}
                />
        ];
        let {menu} = this.props;
        const data = this.state.filteredData || this.props.data;
        if (data) {
            menu = menu.slice();
            menu.push(<li key="save_as_csv"><DownloadDataLink
                filename={this.props.exportFilename}
                exportFile={this.props.customExportFile ? () => this.props.customExportFile(data) : ()=>("product,sku,quantity\n" +
                    data.map(function(d){
                        return d.product+','+d.name+','+d.quantity+"\n"
                    }).join()
                )}>
                Save as CSV
            </DownloadDataLink></li>);
        }

        let store = new DataStore(data ? data : []);
        return (
            <Editor title="BatchEditor" menu={menu} filters={filters}>
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
