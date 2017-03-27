;
const React = require('react');
const Modal = require('react-bootstrap').Modal;
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
        // FileList
        file: null
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
                <Modal.Title>Operation Creation by Upload</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                You can create here an Operation. Use the following format:
                <pre>{`source,target,sku,quantity
VOID,MTLST,something,2
MTLST,VOID,some-other-thing,2
OTTST,MTLST,sku2,4`}</pre>

                The first line is a <b>creation</b> of product.
                The second line is a <b>deletion</b> of product.
                The third line is a movement from OTTST to MTLST.

                <LimitedTextarea
                    onChange={this.handleChange}
                    label="Description"
                    name="description"
                    placeholder="Describe what those Operations are for..."
                    value={this.state.description}
                />

                <FieldGroup
                    onChange={this.handleChange}
                    type="file"
                    label="File"
                    name="file"
                />

                <Alerts alerts={this.state.errors} />
            </Modal.Body>
            <Modal.Footer>
                <AjaxButton
                    className="btn btn-success"
                    data={data}
                    disabled={this.state.file === ""}
                    onSuccess={this.props.onSuccess}
                    onError={(err)=>{this.setState({errors:[err]});}}
                    processData={false}
                    type="POST"
                    url={this.props.url}>
                    Upload
                </AjaxButton>
            </Modal.Footer>
        </Modal>
        );
    }
});
