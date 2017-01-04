;
import React from 'react';
import BatchEditor from './Editor/BatchEditor';
import DataStore from './Editor/DataStore';
import Link from './Common/Link';

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
        let uploadUrl = this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches';
        return (
            <div>
                <h3>{this.props.code}</h3>
                {data ? (<div>
                        <b>Parent:</b>&nbsp;{data.parent ? <Link route="location" code={data.parent} /> : "No parent"}<br />
                        <b>Childs:</b>&nbsp;
                        {data.childs ? <ul>{data.childs.map(function(child, key){return <li key={key}>
                                <Link route="location" code={child} />
                            </li>;})}</ul> : "No child"
                        }<br />
                        <b>Batches:</b>
                    <BatchEditor batches={this.state.batches} uploadUrl={uploadUrl} />
                </div>) : "Loading data"}
            </div>
        );
    }
});
