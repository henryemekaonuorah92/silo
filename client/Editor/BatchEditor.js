;
import React from 'react';
import {Well, Panel, Button, Alert} from 'react-bootstrap';
import {Table, Column, Cell} from 'fixed-data-table';
import Measure from 'react-measure';
import DataStoreWrapper from './DataStoreWrapper';
import DownloadDataLink from '../Common/DownloadDataLink';

const TextCell = ({rowIndex, data, col}) => {
    return (<Cell>
        {data.getObjectAt(rowIndex)[col]}
    </Cell>);
};

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
            filteredDataList: null,
        };

        this._onFilterChange = this._onFilterChange.bind(this);
    }

    _onFilterChange(e) {
        if (this.prop)
        if (!e.target.value) {
            this.setState({
                filteredDataList: null,
            });
        }

        let filterBy = e.target.value.toLowerCase();
        let size = this.props.batches.getSize();
        let filteredIndexes = [];
        for (let index = 0; index < size; index++) {
            let {product, quantity} = this.props.batches.getObjectAt(index);
            if (product.toLowerCase().indexOf(filterBy) !== -1) {
                filteredIndexes.push(index);
            }
        }

        this.setState({
            filteredDataList: new DataStoreWrapper(filteredIndexes, this.props.batches),
        });
    }

    exportAll(){
        let batches = this.state.filteredDataList || this.props.batches;
    }

    render(){
        let batches = this.state.filteredDataList || this.props.batches;

        /*
         <div className="input-group">
         <input className="form-control" type="file" ref="file" />
         <span className="input-group-btn">
         <button onClick={this.handleClick} className="btn">Upload</button>
         </span>
         </div>

         <div className="shr"><span>or</span></div>

         <button onClick={this.handleClick} className="btn">Add</button>
         */

        return (
            <div className="panel panel-default">
                <div className="panel-heading nav navbar-default">
                    <div>
                        <div>
                            <ul className="nav navbar-nav">
                                <li><h4>BatchEditor</h4></li>
                                <li className="dropdown"> <a href="#" className="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Edit <span className="caret"></span></a>
                                    <ul className="dropdown-menu">
                                        <li><a onClick={this.props.onRemoveAll}>Remove all</a></li>
                                        <li><a href="#">Merge a CSV</a></li>
                                    </ul>
                                </li>

                                <li>
                                    <DownloadDataLink exportFile={function(){
                                        return batches._data.map(function(data){
                                            return data.product+';'+data.quantity
                                        }).join("\n")
                                    }}>
                                        Export
                                    </DownloadDataLink>
                                </li>

                                <li><input
                                    onChange={this._onFilterChange}
                                    placeholder="Filter by First Name"
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
                                    height={200}
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
                    </Measure> : <div className="panel-body">Fetching data...</div>
                }
            </div>
        );
    }
}

module.exports = BatchEditor;

BatchEditor.propTypes = {
    // batches: React.PropTypes.isRequired // new DataStore
};

