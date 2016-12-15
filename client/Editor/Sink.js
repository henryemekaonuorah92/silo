;
import React from 'react';
import {Well, Panel, Button, Alert} from 'react-bootstrap';

/**
 * Edit a set of Batches
 * @type {*}
 */
module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {},
        };
    },

    getDefaultProps: function() {
        return {
            batches: []
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
        let header = (
            <div>
                Batch Editor

            </div>
        );
        return (
            <div className="panel panel-default">
                <div className="panel-heading nav navbar-default">
                    <div>
                        <div>
                            <ul className="nav navbar-nav">
                                <li><h4>BatchEditor</h4></li>
                                <li><a><strong>Price:</strong> $12,345</a></li>
                                <li className="dropdown"> <a href="#" className="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Edit <span className="caret"></span></a>
                                    <ul className="dropdown-menu">
                                        <li><a href="#" className="">Upload a CSV</a></li>
                                        <li><a href="#" className="">Remove all</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>
                <div className="panel-body">Panel content</div>
            </div>
        );
    }
});
