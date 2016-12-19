;
import React from 'react';

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: null
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
    },


    render: function(){
        const data = this.state.data;
        return (
            <div>
                <h3>{this.props.code}</h3>
                {data ? (<div>
                    Parent: {data.parent}<br />

                    Childs: <ul>{data.childs.map(function(child, key){return <li key={key}>{child}</li>;})}</ul>
                    Batches: <br />
                    <table className="table table-striped">
                        <thead><tr><th>SKU</th><th>Quantity</th></tr></thead><tbody>
                        {data.batches.map(function(batch, key){return <tr key={key}>
                            <td>{batch.product}</td><td>{batch.quantity}</td>
                        </tr>;})}
                    </tbody></table>
                </div>) : "Loading data"}
            </div>
        );
    }
});
