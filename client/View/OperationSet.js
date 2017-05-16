;
const React = require('react');
const OperationEditor = require('../Editor/OperationEditor');
const DataStore = require('../Editor/DataStore');

module.exports = React.createClass({
    getInitialState: function() {
        return {
            data: null,
            operations: new DataStore([])
        };
    },
    componentDidMount: function(){
        $.ajax(
            "/silo/operationSet/"+this.props.id,
            {
                success: function(data){
                    this.setState({
                        data: data,
                        operations: new DataStore(data.operations)
                    });
                }.bind(this),
                headers: {'Accept': 'application/json'}
            }
        );
    },
    render: function(){
        return (
            <div>
                <h3>OperationSet {this.props.id}{
                    this.state.data && <span> ({this.state.data.type} {this.state.data.id})</span>
                }</h3>
                <OperationEditor operations={this.state.operations} />
            </div>
        );
    }
});
