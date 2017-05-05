;
const React = require('react');
const Link = require('./Common/Link');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {locations:[]}
        };
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            id: null
        };
    },

    componentDidMount: function () {
        this.props.cache.get('product/'+this.props.id)
            .from(this.props.siloBasePath+"/inventory/product/"+this.props.id)
            .onUpdate(function(value){
                this.setState({
                    data: value
                });
            }.bind(this))
            .refresh();
    },

    componentWillUnmount : function () {
        this.props.cache.cleanup('product/'+this.props.id);
    },

    render: function(){
        let data = this.state.data;
        return (
            <div>
                <h3><span className="glyphicon glyphicon-apple" /> Product {this.props.id}</h3>
                {this.props.children}
                {!data && (<span>Loading</span>)}
                {data && <div>
                    <table className="">
                        <thead>
                        <tr>
                            <th>Location</th>
                            <th>Quantity</th>
                            <th>Modifiers</th>
                        </tr>
                        </thead>
                        <tbody>
                        {this.state.data.locations.map((loc, key) => (loc.quantity !== 0 ?
                            <tr key={key}>
                                <td><Link route="location" code={loc.location} /></td>
                                <td>{loc.quantity}</td>
                                <td>{loc.modifiers.map((mod, key) => (<span key={key}>{mod}&nbsp;</span>))}</td>
                            </tr> : null
                        ))}
                        </tbody>
                    </table>
                </div>}
            </div>
        );
    }
});
