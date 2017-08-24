;
const React = require('react');
const Api = require('silo-core').Api;
const promisify = require('silo-core/client/Common/connectWithPromise');
const OperationEditor = promisify(require('../Editor/OperationEditor'));

module.exports = React.createClass({
    getInitialState: function(){
        return {
            data: null,
            promise: Api.fetch("/silo/operationSet/"+this.props.id)
        };
    },
    componentDidMount: function(){
        this.state.promise.then(d=>this.setState({data: d.data}));
    },
    render: function(){
        let {data, promise} = this.state;
        return (
            <div>
                <h3>OperationSet {this.props.id}{
                    data && <span> ({data.type} {data.id})</span>
                }</h3>
                <OperationEditor promise={promise.then(d=>d.operations)} />
            </div>
        );
    }
});
