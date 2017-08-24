;
const React = require('react');
const {Navbar,Nav,NavDropdown,NavItem, Glyphicon} = require('react-bootstrap');
const ModifierModal = require('../Modal/ModifierModal');
const StringToColor = require('../Common/StringToColor');

module.exports = React.createClass({

    displayName: 'ModifierEditor',

    getInitialState: ()=>({
        // Setting a modifier will display the ModifierModal
        modifier: null,
        // Edit mode changes slightly the modal
        edit: false
    }),

    propTypes: {
        modifiers: React.PropTypes.array.isRequired,
        onDelete: React.PropTypes.func,
        modifierFactory: React.PropTypes.any.isRequired
    },

    getDefaultProps: ()=>({
        onDelete: console.log
    }),

    handleAdd: function(){
        this.setState({modifier: {}});
    },

    handleSave: function(data){
        this.props.onSave && this.props.onSave(data);
        this.setState(this.getInitialState());
    },

    handleDelete: function(name){
        this.props.onDelete && this.props.onDelete(name);
        this.setState(this.getInitialState());
    },

    handleEdit: function(modifier){
        this.props.writable &&
        this.setState({
            modifier: modifier,
            edit: true
        });
        console.log(modifier)
    },

    render: function(){
        let {modifiers, onDelete, onSave, modifierFactory, ...rest} = this.props;
        const modifierNames = modifierFactory.listEditors();
        const usedModifiers = modifiers.map(m=>m.name);
        const canAdd = usedModifiers.length < modifierNames.length;
        const availableModifiers = modifierNames.filter(function(n) {
            return usedModifiers.indexOf(n) === -1;
        });
        return (
            <div className="panel panel-default">
                <ModifierModal show={!!this.state.modifier}
                               onHide={()=>this.setState(this.getInitialState())}
                               modifier={this.state.modifier}
                               availableModifiers={availableModifiers}
                               onSave={this.handleSave}
                               onDelete={this.handleDelete}
                               edit={this.state.edit}
                               modifierFactory={modifierFactory}
                               {...rest}/>

                <Navbar>
                    <Navbar.Header>
                        <Navbar.Brand>
                            ModifierEditor
                        </Navbar.Brand>
                    </Navbar.Header>

                        {this.props.writable && canAdd &&
                        <Nav>
                            <NavDropdown title="Action" id="basic-nav-dropdown">
                                <NavItem onClick={this.handleAdd}>Add</NavItem>
                            </NavDropdown>
                        </Nav>
                        }
                </Navbar>
                
                <table className={"table "+ (this.props.writable ? "table-hover" : "")}>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                            {modifiers.length ? modifiers.map((modifier)=>{
                                let partial = this.props.modifierFactory.getView(modifier.name);
                                return <tr key={modifier.name} onClick={this.handleEdit.bind(this, modifier)}>
                                    <td style={{backgroundColor: StringToColor(modifier.name)}}>
                                        {modifier.name}
                                    </td>
                                    <td>{partial ? React.createElement(partial, {
                                        value: modifier.value,
                                        code: this.props.location
                                    }) : null}</td>
                                </tr>
                            }) : <tr><td colSpan={2}>No Modifiers</td></tr>}
                    </tbody>
                </table>
            </div>
        );
    }
});
