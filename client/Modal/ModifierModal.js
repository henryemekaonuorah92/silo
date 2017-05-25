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
        // modifier: React.PropTypes.any // Passed when editing a modifier
    },

    getDefaultProps: ()=>({
        onSave: console.log
    }),

    getInitialState: ()=>({
        name: null,
        value: {}
    }),

    componentWillReceiveProps: function(nextProps){
        if (nextProps.modifier){
            this.setState({value: nextProps.modifier.value});
        }
    },

    onChange: function(value){
        this.setState({value: value});
    },

    render: function(){
        const {modifierFactory, modifier, onSave, ...rest} = this.props;
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
                            {modifier ?
                                <span>{name}</span>
                                :
                                <FormControl componentClass="select"
                                             onChange={(e)=>this.setState({name:e.target.value})}
                                             selected={
                                                 modifierNames.indexOf(this.state.name) > -1 ? this.state.name : null
                                             }
                                             placeholder="Source">
                                    <option value={null}>Modifier...</option>
                                    {modifierNames.map((name, k)=><option key={k} value={name}>{name}</option>)}
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
                    <Button bsStyle="success" onClick={onSave.bind(this, {
                        name: name,
                        value: this.state.value
                    })}>Save</Button>
                </Modal.Footer>
            </Modal>
        );
    }
});
