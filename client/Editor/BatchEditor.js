;
import React from 'react';
import {Well, Panel, Button, Alert} from 'react-bootstrap';
import {Table, Column, Cell} from 'fixed-data-table';
import Measure from 'react-measure';

/**
 * Edit a set of Batches
 * @type {*}
 */
module.exports = React.createClass({

    getInitialState: function () {
        return {
            dimensions: {
                width: -1,
                height: -1,
            },
            data: {},
        };
    },

    getDefaultProps: function() {
        return {
            batches: [],
            onRemoveAll: function(){}
        };
    },

    handleChange: function(path, newValue){
        var data = this.state.data;
        data[path] = newValue;
        this.setState({data: data});

        //if (this.timeout) {
        //    clearTimeout(this.timeout);
        //}
        //this.timeout = setTimeout(this.sendToServer, 1000);
    },

    render: function(){

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
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <Measure onMeasure={(dimensions)=>{this.setState({dimensions});}}>
                    <div className="panel-body panel-body_noPadding">
                        {this.state.dimensions.width != -1 && (
                            <Table
                                width={this.state.dimensions.width} // Bootstrap 15px padding on row
                                height={200}
                                headerHeight={0}
                                offsetHeight={150}
                                rowsCount={20}
                                rowHeight={36}>
                                <Column
                                    width={200}
                                    cell={props => (
                                        <Cell {...props}>
                                            SKU-123-SKU
                                        </Cell>
                                    )}
                                />
                                <Column
                                    width={200}
                                    cell={props => (
                                        <Cell {...props}>
                                            12
                                        </Cell>
                                    )}
                                />
                            </Table>
                        )}
                    </div>
                </Measure>
            </div>
        );
    }
});
