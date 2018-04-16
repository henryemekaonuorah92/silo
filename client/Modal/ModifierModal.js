;
const React = require('react');
const {Modal,FormControl,Glyphicon,Button,InputGroup,FormGroup} = require('react-bootstrap');
const PromiseButton = require('../Common/PromiseButton');
const Alerts = require('../Common/Alerts');

module.exports = React.createClass({

    displayName: 'ModifierModal',

    propTypes: {
        onSave: React.PropTypes.func,
        modifierFactory: React.PropTypes.any.isRequired,
        availableModifiers: React.PropTypes.array.isRequired,
        // modifier: React.PropTypes.any // Passed when editing a modifier
    },

    getDefaultProps: ()=>({
        onSave: console.log
    }),

    getInitialState: ()=>({
        name: null,
        value: null
    }),

    componentWillReceiveProps: function(nextProps){
        if (nextProps.modifier){
            this.setState({
                name: nextProps.modifier.name || null,
                value: nextProps.modifier.value || null
            });
        }
    },

    onChange: function(value){
        this.setState({value: value});
    },

    handleSave: function() {
        this.props.onSave({
            name: this.state.name,
            value: this.state.value
        })
    },

    handleDelete: function(){
        this.props.onDelete(this.state.name)
    },

    render: function(){
        const {modifierFactory, modifier, availableModifiers, edit, onSave, onDelete, location, writable, siloBasePath, ...rest} = this.props;
        const modifierNames = modifierFactory.listEditors();
        const name = (modifier && modifier.name) || this.state.name;

        let partial = null;
        if (name) {
            let editor = modifierFactory.getEditor(name);
            if (editor) {
                partial = React.createElement(editor, {value: this.state.value, onChange: this.onChange});
            }
        }

        return (
            <Modal {...rest}>
                <Modal.Header closeButton>
                    <Modal.Title>Modifier</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {modifierNames.length > 0 ?
                        <div>
                            {edit ?
                                <span>{modifier.name}</span>
                                :
                                <FormControl componentClass="select"
                                             onChange={(e)=>this.setState({name:e.target.value})}
                                             selected={
                                                 modifierNames.indexOf(this.state.name) > -1 ? this.state.name : null
                                             }
                                             placeholder="Source">
                                    <option value={null}>Modifier...</option>
                                    {availableModifiers.map((name, k)=><option key={k} value={name}>{name}</option>)}
                                </FormControl>
                            }

                            <hr />

                            {partial}

                            <Alerts />
                        </div>
                        :
                        "There's no Modifier defined"
                    }
                </Modal.Body>
                <Modal.Footer>
                    {edit && <Button bsStyle="danger" onClick={this.handleDelete}>Delete</Button>}
                    <Button bsStyle="success" onClick={this.handleSave} disabled={!this.state.name}>Save</Button>
                </Modal.Footer>
            </Modal>
        );
    }
});
