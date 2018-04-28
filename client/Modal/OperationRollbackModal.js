;
const React = require('react');
const {Modal, Button} = require('react-bootstrap')
const AjaxButton = require('../Common/AjaxButton');
const Alerts = require('../Common/Alerts');
const FieldGroup = require('../Form/FieldGroup');
const LimitedTextarea = require('../Form/LimitedTextarea');

/**
 * Modal for creating operations by csv upload
 * @todo reset the form on success
 */
module.exports = React.createClass({

    propTypes: {
        onSuccess: React.PropTypes.func,
        url: React.PropTypes.string.isRequired
    },

    getInitialState: ()=>({
        errors: [],
        description: "",
    }),

    handleChange(e) {
        let state = this.state, name = e.target.name;
        state[name] = e.target.type === "file" ? e.target.files : e.target.value;
        this.setState(state);
    },

    render() {
        const rest = Object.assign({}, this.props);
        delete rest.onSuccess;
        delete rest.url;

        let data = new FormData();
        data.append('description', this.state.description);
        this.state.file ? data.append('file', this.state.file[0]) : null;


        return (
        <Modal {...rest}>
            <Modal.Header closeButton>
                <Modal.Title>Operation Rollback</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                Please explain why are you rollbacking:
                <LimitedTextarea
                    onChange={this.handleChange}
                    label="Description"
                    name="description"
                    placeholder="Describe what this Rollback is for..."
                    value={this.state.description}
                />

                <Alerts alerts={this.state.errors} />
            </Modal.Body>
            <Modal.Footer>
                <AjaxButton
                    className="btn btn-success"
                    data={data}
                    disabled={this.state.description === ""}
                    onSuccess={this.props.onSuccess}
                    onError={(err)=>{this.setState({errors:[err]});}}
                    processData={false}
                    type="POST"
                    url={this.props.url}>
                    Rollback
                </AjaxButton>
            </Modal.Footer>
        </Modal>
        );
    }
});
