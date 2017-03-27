;
const React = require('react');
const Modal = require('react-bootstrap').Modal;
const AjaxButton = require('../Common/AjaxButton');
const Alerts = require('../Common/Alerts');
const FieldGroup = require('../Form/FieldGroup');
const LimitedTextarea = require('../Form/LimitedTextarea');

module.exports = React.createClass({

    getInitialState: ()=>({
        errors: [],

        description: "",
        file: null, // FileList
        merge: "true"
    }),

    handleChange(e){
        let state = this.state, name = e.target.name;
        state[name] = e.target.type === "file" ? e.target.files : e.target.value;
        this.setState(state);
    },

    render(){
        const rest = Object.assign({}, this.props);
        delete rest.url;
        delete rest.onSuccess;

        let data = new FormData();
        data.append('description', this.state.description);
        this.state.file ? data.append('file', this.state.file[0]) : null;
        data.append('merge', this.state.merge);

        return (
            <Modal {...rest}>
                <Modal.Header closeButton>
                    <Modal.Title>Batch Mass Edit by Upload</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p>Mass edit Batches by uploading a CSV file. Expected format is:</p>
                    <pre>{this.state.merge ? "merge" : "replace"}{`
product,quantity
31-232-25,15
14-231-21,-2`}</pre>
                    <label className="sr-only" htmlFor="optionsRadios">Edit type</label>
                    <div className="radio">
                        <label>
                            <input type="radio"
                                   name="merge"
                                   onChange={this.handleChange}
                                   checked={this.state.merge === "true"}
                                   value="true" />
                            <b>Merge</b> the uploaded batches with the current set
                        </label>
                    </div>
                    <div className="radio">
                        <label>
                            <input type="radio"
                                   name="merge"
                                   onChange={this.handleChange}
                                   checked={this.state.merge === "false"}
                                   value="false" />
                            <b>Replace</b> the current set by the uploaded batches
                        </label>
                    </div>

                    <LimitedTextarea
                        onChange={this.handleChange}
                        label="Description"
                        name="description"
                        placeholder="Describe what those Batches are for..."
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
                        processData={false}
                        type="POST"
                        onSuccess={this.props.onSuccess}
                        url={this.props.url}
                        disabled={this.state.file === ""}
                        onError={(err)=>{this.setState({errors:[err]});}}>
                        Upload
                    </AjaxButton>
                </Modal.Footer>
            </Modal>
        );
    }
});
