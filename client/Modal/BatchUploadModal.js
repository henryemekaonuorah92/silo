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
        type: "merge"
    }),

    getDefaultProps: ()=>({
        withLocation: false
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
        delete rest.withLocation;

        let data = new FormData();
        data.append('description', this.state.description);
        this.state.file ? data.append('file', this.state.file[0]) : null;
        data.append('type', this.state.type);

        return (
            <Modal {...rest}>
                <Modal.Header closeButton>
                    <Modal.Title>Batch Mass Edit by Upload</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p>Mass edit Batches by uploading a CSV file. Expected format is:</p>
                    <pre>{this.state.type}{!this.props.withLocation ? `
product,quantity
31-232-25,15
14-231-21,-2` : `
location,product,quantity
MTLST,31-232-25,15
A-04-A,14-231-21,-2`
                    }</pre>
                    <label className="sr-only" htmlFor="type">Edition behavior</label>
                    <div className="radio">
                        <label>
                            <input type="radio"
                                   name="type"
                                   onChange={this.handleChange}
                                   checked={this.state.type === "merge"}
                                   value="merge" />
                            <b>Merge</b> uploaded Batches with those in the Location
                        </label>
                    </div>
                    <div className="radio">
                        <label>
                            <input type="radio"
                                   name="type"
                                   onChange={this.handleChange}
                                   checked={this.state.type === "replace"}
                                   value="replace" />
                            <b>Replace</b> uploaded Batches in the Location after wiping it
                        </label>
                    </div>
                    <div className="radio">
                        <label>
                            <input type="radio"
                                   name="type"
                                   onChange={this.handleChange}
                                   checked={this.state.type === "superReplace"}
                                   value="superReplace" />
                            <b>Super Replace</b>, like Replace but ignores Products in Location that haven't been uploaded
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
                        disabled={this.state.file === null}
                        onError={(err)=>{this.setState({errors:err});}}>
                        Upload
                    </AjaxButton>
                </Modal.Footer>
            </Modal>
        );
    }
});
