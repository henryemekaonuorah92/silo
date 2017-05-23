;
const React = require('react');
const {Modal,FormControl,Glyphicon,Button,InputGroup,FormGroup} = require('react-bootstrap');
const PromiseButton = require('../Common/PromiseButton');
const Alerts = require('../Common/Alerts');

module.exports = React.createClass({

    propTypes: {
        onSuccess: React.PropTypes.func,
        // url: React.PropTypes.string.isRequired,
        modifierFactory: React.PropTypes.any.isRequired
    },

    getInitialState: ()=>({
        name: null,
        value: {}
    }),

    onSave: function(){
        return fetch('/{code}/modifiers')
            .set()
            .send();
    },

    render: function(){
        const rest = Object.assign({}, this.props);
        delete rest.onSuccess;
        delete rest.url;
        delete rest.modifierFactory;

        const modifierNames = this.props.modifierFactory.listEditors();

        let partial = null;
        if (this.state.name) {
            partial = React.createElement(this.props.modifierFactory.getEditor(this.state.name));
        }

        return (
        <Modal {...rest}>
            <Modal.Header closeButton>
                <Modal.Title>Modifier</Modal.Title>
            </Modal.Header>
            <Modal.Body>

                <FormControl componentClass="select"
                             onChange={(e)=>this.setState({name:e.target.value})}
                             selected={
                                 modifierNames.indexOf(this.state.name) > -1 ? this.state.name : null
                             }
                             placeholder="Source">
                    <option value={null}>Modifier...</option>
                    {modifierNames.map((name, k)=><option key={k} value={name}>{name}</option>)}
                </FormControl>

                {partial}

                <Alerts />
            </Modal.Body>
            <Modal.Footer>
                <Button bsStyle="success" onClick={this.onSave}>Save</Button>
            </Modal.Footer>
        </Modal>
        );
    }
});
