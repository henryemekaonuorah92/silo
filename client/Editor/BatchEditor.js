;
const React = require('react');
const {Table, Column, Cell} = require('fixed-data-table');
const Measure = require('react-measure');
const DataStoreWrapper = require('./DataStoreWrapper');
const DownloadDataLink = require('../Common/DownloadDataLink');
const UploadModalMenu = require('./UploadModal');
const TextCell = require('./TextCell');

/**
 * Edit a set of Batches
 * @type {*}
 */
class BatchEditor extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            dimensions: {
                width: -1,
                height: -1,
            },
            data: {},
            filteredDataList: null
        };

        this._onFilterChange = this._onFilterChange.bind(this);
    }

    _onFilterChange(e) {
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
    }

    componentWillReceiveProps(nextProps) {
        // Reapply filtering after a change in the props
        if (this.state.filteredDataList && this.state.filteredDataList._indexMap) {
            this.setState({
                filteredDataList: new DataStoreWrapper(this.state.filteredDataList._indexMap, nextProps.batches),
            });
        }
    }

    render(){
        let batches = this.state.filteredDataList || this.props.batches;
        return (
            <div className="panel panel-default">
                <div className="panel-heading nav navbar-default">
                    <div>
                        <div>
                            <ul className="nav navbar-nav">
                                <li><h4>BatchEditor</h4></li>
                                { this.props.editable &&
                                    <li className="dropdown"> <a href="#" className="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Edit <span className="caret"></span></a>
                                        <ul className="dropdown-menu">
                                            <li><UploadModalMenu url={this.props.uploadUrl} onSuccess={this.props.onNeedRefresh} /></li>
                                        </ul>
                                    </li>
                                }
                                <li>
                                    <DownloadDataLink
                                        filename={this.props.exportFilename}
                                        exportFile={function(){
                                        let header = "sku,quantity\n";
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
                                    height={400}
                                    headerHeight={0}
                                    offsetHeight={150}
                                    rowsCount={batches.getSize()}
                                    rowHeight={36}>
                                    <Column
                                        width={200}
                                        cell={<TextCell data={batches} col="product" />}
                                    />
                                    <Column
                                        width={200}
                                        cell={<TextCell data={batches} col="quantity" />}
                                    />
                                </Table>
                            )}
                        </div>
                    </Measure> : <div className="panel-body">Fetching or no data...</div>
                }
            </div>
        );
    }
}

module.exports = BatchEditor;

BatchEditor.propTypes = {
    // batches: React.PropTypes.isRequired // new DataStore
};
