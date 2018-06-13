;
const React = require('react');
const Api = require('../Api');
const promisify = require('../Common/connectWithPromise');
const OperationEditor = promisify(require('../Editor/OperationEditor'));
const Emoji = require('react-emoji');

module.exports = React.createClass({
    getInitialState: function(){
        return {
            data: null,
            promise: Api.fetch("/silo/operationSet/"+this.props.id)
        };
    },
    componentDidMount: function(){
        this.state.promise.then(d=>this.setState({data: d}));
    },
    render: function(){
        let {data, promise} = this.state;
        let value = data ? data.value : null
        console.log(value, data)
        return (
            <div>
                <h3>OperationSet {this.props.id}{
                    data && data.data && <span> ({data.data.type} {data.data.id})</span>
                }</h3>

                {value && typeof(value) === "object" && "description" in value &&
                    <div>
                        Comment {Emoji.emojify(value.description)}
                    </div>
                }

                {value && typeof(value) === "object" && "magentoOrderId" in value &&
                    <div>
                        Order {value.magentoOrderId} {"incrementId" in value && ' #'+value.incrementId}
                    </div>
                }

                <OperationEditor promise={promise.then(d=>d.operations)} />
            </div>
        );
    }
});
