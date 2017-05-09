;
const React = require('react');
const {Table, Column, Cell} = require('fixed-data-table');
const Measure = require('react-measure');
const DataStoreWrapper = require('./DataStoreWrapper');
const DownloadDataLink = require('../Common/DownloadDataLink');
const Modal = require('../Modal/BatchUploadModal');
const TextCell = require('./TextCell');
const Link = require('../Factory').Link;

/**
 * Edit a set of Batches
 * @type {*}
 */
module.exports = React.createClass({
    propTypes: {
        writable: React.PropTypes.bool,
        additionalMenu: React.PropTypes.any
    },

    getDefaultProps: () => {return{
        writable: false,
        batchColumns: ()=>null,
        additionalMenu: ()=>null
    }},

    getInitialState: () => {return {
        dimensions: {
            width: -1,
            height: -1,
        },
        data: {},
        filteredDataList: null,
        showModal: false
    }},

    _onFilterChange: function(e) {
        if (!e.target.value) {
            this.setState({
                filteredDataList: null,
            });
        }

        let filterBy = e.target.value.toLowerCase();
        let size = this.props.batches.getSize();
        let filteredIndexes = [];
        for (let index = 0; index < size; index++) {
            let {product} = this.props.batches.getObjectAt(index);
            if (product.toLowerCase().indexOf(filterBy) !== -1) {
                filteredIndexes.push(index);
            }
        }

        this.setState({
            filteredDataList: new DataStoreWrapper(filteredIndexes, this.props.batches),
        });
    },

    componentWillReceiveProps: function(nextProps) {
        // Reapply filtering after a change in the props
        if (this.state.filteredDataList && this.state.filteredDataList._indexMap) {
            this.setState({
                filteredDataList: new DataStoreWrapper(this.state.filteredDataList._indexMap, nextProps.batches),
            });
        }
    },

    render: function(){
        let batches = this.state.filteredDataList || this.props.batches;
        return (
            <div className="panel panel-default">
                <div className="panel-heading nav navbar-default">
                    <ul className="nav navbar-nav">
                        <li><h4>BatchEditor</h4></li>
                        <li className="dropdown"><a href="#" className="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">File <span className="caret" /></a>
                            <ul className="dropdown-menu">
                                { this.props.writable &&
                                    <li>
                                        <a onClick={()=>this.setState({showModal: true})}>Open CSV...</a>
                                        <Modal
                                            show={this.state.showModal}
                                            onHide={()=>this.setState({showModal:false})}
                                            url={this.props.uploadUrl}
                                            onSuccess={()=>{
                                                this.setState({ showModal: false });
                                                this.props.onNeedRefresh();
                                            }} />
                                    </li>
                                }
                                <li>
                                    <DownloadDataLink
                                        filename={this.props.exportFilename}
                                        exportFile={function(){
                                            let header = "product,sku,quantity\n";
                                            return header + batches.getAll().map(function(data){
                                                    return data.product+','+data.name+','+data.quantity
                                                }).join("\n")
                                        }}>
                                        Save CSV
                                    </DownloadDataLink>
                                </li>
                                {this.props.additionalMenu}
                            </ul>
                        </li>

                        <li><input
                            onChange={this._onFilterChange}
                            placeholder="Filter by SKU"
                            ref="filter"
                        /></li>

                        <li><span>{batches.getSize()} batches</span></li>
                    </ul>
                </div>
                {batches.getSize() > 0 ?
                    <Measure onMeasure={(dimensions)=>{this.setState({dimensions});}}>
                        <div className="panel-body panel-body_noPadding">
                            {this.state.dimensions.width != -1 && (
                                <Table
                                    width={this.state.dimensions.width} // Bootstrap 15px padding on row
                                    height={Math.min(batches.getSize() + 3, 12) * 36}
                                    headerHeight={30}
                                    offsetHeight={150}
                                    rowsCount={batches.getSize()}
                                    rowHeight={36}>
                                    {[
                                        <Column
                                            key={1}
                                            width={200}
                                            header="Product"
                                            cell={props => {
                                                let obj = batches.getObjectAt(props.rowIndex);
                                                let code = obj.product;
                                                let anchor = code;
                                                if(("name" in obj) && obj.name) {
                                                    anchor = "("+obj.name.replace(code, '')+") "+code;
                                                }
                                                return (
                                                <Cell {...props}>
                                                    <Link route="product" code={code}>{anchor}</Link>
                                                </Cell>
                                            )}}
                                        />,
                                        <Column
                                            key={2}
                                            width={200}
                                            header="Quantity"
                                            cell={<TextCell data={batches} col="quantity" />}
                                        />
                                    ].concat(this.props.batchColumns(batches))}
                                </Table>
                            )}
                        </div>
                    </Measure> : <div className="panel-body">Fetching or no data...</div>
                }
            </div>
        );
    }
});
