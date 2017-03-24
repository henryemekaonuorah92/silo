;
const React = require('react');
const {Table, Column, Cell} = require('fixed-data-table');
const Measure = require('react-measure');
const DataStoreWrapper = require('./DataStoreWrapper');
const DownloadDataLink = require('../Common/DownloadDataLink');
const UploadModal = require('./UploadModal');
const TextCell = require('./TextCell');
const Link = require('../Common/Link');

/**
 * Edit a set of Batches
 * @type {*}
 */
module.exports = React.createClass({
    propTypes: {
        writable: React.PropTypes.bool
    },

    getDefaultProps: () => {return{
        writable: false,
        batchColumns: function(){}
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
                    <div>
                        <div>
                            <ul className="nav navbar-nav">
                                <li><h4>BatchEditor</h4></li>
                                { this.props.writable &&
                                    <li className="dropdown"> <a href="#" className="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Edit <span className="caret"></span></a>
                                        <ul className="dropdown-menu">
                                            <li>
                                                <a onClick={()=>this.setState({showModal: true})}>CSV Upload</a>
                                                <UploadModal
                                                    show={this.state.showModal}
                                                    onHide={()=>this.setState({showModal:false})}
                                                    url={this.props.uploadUrl}
                                                    onSuccess={()=>{
                                                        this.setState({ showModal: false });
                                                        this.props.onNeedRefresh();
                                                    }} />
                                            </li>
                                        </ul>
                                    </li>
                                }
                                <li>
                                    <DownloadDataLink
                                        filename={this.props.exportFilename}
                                        exportFile={function(){
                                        let header = "product,quantity\n";
                                        return header + batches.getAll().map(function(data){
                                            return data.product+','+data.quantity
                                        }).join("\n")
                                    }}>
                                        Export
                                    </DownloadDataLink>
                                </li>

                                <li><input
                                    onChange={this._onFilterChange}
                                    placeholder="Filter by SKU"
                                    ref="filter"
                                /></li>

                                <li><span>{batches.getSize()} batches</span></li>
                            </ul>
                        </div>
                    </div>
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
                                            cell={props => (
                                                <Cell {...props}>
                                                    <Link route="product" code={batches.getObjectAt(props.rowIndex)["product"]} />
                                                </Cell>
                                            )}
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
