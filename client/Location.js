;
import React from 'react';
import BatchEditor from './Editor/BatchEditor';
import DataStore from './Editor/DataStore';

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {},
            batches: new DataStore([])
        };
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            code: 'root'
        };
    },

    componentDidMount: function () {
        $.ajax(
            this.props.siloBasePath+"/inventory/location/"+this.props.code,
            {
                success: function (data) {
                    this.setState({
                        data: data
                    });
                }.bind(this),
                headers: {'Accept': 'application/json'}
            }
        );

        $.ajax(
            this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches',
            {
                success: function (data) {
                    this.setState({
                        batches: new DataStore(data)
                    });
                }.bind(this),
                headers: {'Accept': 'application/json'}
            }
        );
    },


    render: function(){
        let data = this.state.data;
        return (
            <div>
                <h3>{this.props.code}</h3>
                {data ? (<div>
                    Parent: {data.parent}<br />

                    Childs: <ul>{data.childs && data.childs.map(function(child, key){return <li key={key}>{child}</li>;})}</ul>
                    Batches:
                    <BatchEditor batches={this.state.batches} />
                </div>) : "Loading data"}
            </div>
        );
    }
});
