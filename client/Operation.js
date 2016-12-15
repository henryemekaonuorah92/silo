;
import React from 'react';
import FieldGroup from './Form/FieldGroup';
import {Well, Button, Alert} from 'react-bootstrap';
import BatchEditor from './Editor/BatchEditor';

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {},
        };
    },

    getDefaultProps: function() {
        return {
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
        return (
            <div>
                <h3>Operation</h3>
                You can create here an Operation, the most basic movement object for Silo.

                <FieldGroup
                    type="text"
                    label="Source Location"
                    path="source"
                    configuration={this.state.data}
                    onChange={this.handleChange}
                />

                <FieldGroup
                    type="text"
                    label="Target Location"
                    path="target"
                    configuration={this.state.data}
                    onChange={this.handleChange}
                />

                <BatchEditor />

                <button className="btn btn-success">Execute</button>
            </div>
        );
    }
});
