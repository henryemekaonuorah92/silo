;
const React = require('react');
const {NavItem, Glyphicon} = require('react-bootstrap');
const {Navbar} = require('./Editor');
const ModifierModal = require('../Modal/ModifierModal');

module.exports = React.createClass({

    displayName: 'ModifierEditor',

    getInitialState: ()=>({
        showModal: false,
        modifier: null // set if we are editing a modifier
    }),

    propTypes: {
        modifiers: React.PropTypes.array.isRequired,
        onDelete: React.PropTypes.func,
    },

    getDefaultProps: ()=>({
        onDelete: console.log
    }),

    onEdit: function(modifier){
        this.setState({
            modifier: modifier,
            showModal: true
        });
    },

    handleSave: function(data){
        if (this.props.onSave) {
            this.props.onSave(data);
        }
        this.setState(this.getInitialState());
    },

    render: function(){
        let {modifiers, onDelete, onSave, ...rest} = this.props;
        return (
            <div className="panel panel-default">
                <ModifierModal show={this.state.showModal}
                               onHide={()=>this.setState(this.getInitialState())}
                               modifier={this.state.modifier}
                               onSave={this.handleSave}
                               {...rest}/>

                <Navbar title="ModifierEditor">
                    {this.props.writable &&
                        <NavItem onClick={()=>{this.setState({showModal: !this.state.showModal, modifier: null});}}>Add</NavItem>
                    }
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
                                return <tr key={modifier.name}>
                                    <td>
                                        {this.props.writable &&
                                            <div className="pull-right">

                                                {false &&
                                                <Glyphicon glyph="pencil" onClick={this.onEdit.bind(this, modifier)}/>
                                                }
                                                &nbsp;
                                                <Glyphicon glyph="trash"
                                                           onClick={this.props.onDelete.bind(this, modifier.name)}/>
                                            </div>
                                        }
                                        {modifier.name}
                                    </td>
                                    <td>{partial ? React.createElement(partial, {value: modifier.value}) : null}</td>
                                </tr>
                            }) : <tr><td colSpan={2}>No Modifiers</td></tr>}
                    </tbody>
                </table>
            </div>
        );
    }
});
