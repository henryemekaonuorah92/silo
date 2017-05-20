;
const React = require('react');
const {NavItem} = require('react-bootstrap');
const {Navbar} = require('./Editor');
const ModifierModal = require('../Modal/ModifierModal');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            modifiers: [],
            showModal: false
        };
    },

    propTypes: {
        endpoint: React.PropTypes.string.isRequired,
        cache: React.PropTypes.object.isRequired,
        /**
         * An object that knows what are the existing modifiers
         */
        modifierFactory: React.PropTypes.object.isRequired
    },

    componentDidMount: function () {
        this.props.cache
            .get(this.props.endpoint)
            .from(this.props.endpoint)
            .onUpdate(function(value){
                this.setState({
                    modifiers: value
                });
            }.bind(this))
            .refresh();
    },

    componentWillUnmount : function () {
        this.props.cache.cleanup('operation/'+this.props.id);
    },

    render: function(){
        let modifiers = this.state.modifiers;
        return (
            <div className="panel panel-default">
                <ModifierModal show={this.state.showModal}
                               onHide={()=>this.setState({showModal:false})}
                               modifierFactory={this.props.modifierFactory}/>

                <Navbar title="ModifierEditor">
                    <NavItem onClick={()=>{this.setState({showModal: !this.state.showModal});}}>Add</NavItem>
                </Navbar>

                <table className="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                            {modifiers.length ? modifiers.map((modifier)=>{
                                let partial = this.props.modifierFactory.getView(modifier.name);
                                let rest = {};
                                if (! partial) {
                                    partial = () => <span>{modifier.name}</span>;
                                } else {
                                    rest = Object.assign({}, this.props);
                                    rest.value = modifier.value;
                                }

                                return <tr key={modifier.name}>
                                    <td>{modifier.name}</td>
                                    <td>{React.createElement(partial, rest)}</td>
                                </tr>
                            }) : <tr><td colSpan={2}>No Modifiers</td></tr>}
                    </tbody>
                </table>
            </div>
        );
    }
});
