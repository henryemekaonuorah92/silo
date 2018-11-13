;
const React = require('react');
import {Modal, Alert, Table, FormControl} from 'react-bootstrap';
const AjaxButton = require('../Common/AjaxButton');
const Alerts = require('../Common/Alerts');
const FieldGroup = require('../Form/FieldGroup');
const LimitedTextarea = require('../Form/LimitedTextarea');

module.exports = React.createClass({

    getInitialState: ()=>({
        errors: [],
        pendingOperations: {},
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

    updateAction(e) {
        let pendingOperations = this.state.pendingOperations;
        let type = e.target.name;
        pendingOperations[type]['action'] = e.target.value;
        this.setState({pendingOperations});
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
        data.append('pendingOperations', JSON.stringify(this.state.pendingOperations));

        let pendingOperationsTable = [];
        for(var optype in this.state.pendingOperations) {
            pendingOperationsTable.push(
                <tr>
                    <td>{this.state.pendingOperations[optype]['qty']}</td>
                    <td>{optype}</td>
                    <td>
                        <FormControl name={optype} componentClass="select" placeholder="select" onChange={this.updateAction}>
                            <option key={optype+"-ignore"} value="ignore">Ignore</option>
                            <option key={optype+"-execute"} value="execute">Execute</option>
                            <option key={optype+"-cancel"} value="cancel">Cancel</option>
                        </FormControl>
                    </td>
                </tr>
            )
        }

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
                            <b>Replace</b> uploaded Batches in the Location
                        </label>
                    </div>
                    <div className="radio">
                        <label>
                            <input type="radio"
                                   name="type"
                                   onChange={this.handleChange}
                                   checked={this.state.type === "superReplace"}
                                   value="superReplace" />
                            <b>Super Replace</b>, like Replace but wipe the Location entirely before
                        </label>
                    </div>


                    <LimitedTextarea
                        onChange={this.handleChange}
                        label="Description"
                        name="description"
                        placeholder="Describe what those Batches are for..."
                        value={this.state.description}
                    />

                    {(this.state.pendingOperations && Object.keys(this.state.pendingOperations).length > 0) &&
                        <Alert bsStyle="warning">
                            <strong>There are pending operations related to this batch adjustment</strong>
                            <p>What would you like to do with them?</p>
                            <Table bordered condensed>
                                <thead>
                                    <tr>
                                        <th>Qty</th>
                                        <th>Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pendingOperationsTable}
                                </tbody>
                            </Table>
                        </Alert>
                    }

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
                        onSuccess={() => {
                            this.props.onSuccess()
                            this.setState(this.getInitialState())
                            }
                        }
                        url={this.props.url}
                        disabled={this.state.file === null}
                        onError={(err)=> {
                                if(Object.keys(err).includes('pendingOperations')) {
                                    let pendingOperations = err.pendingOperations;
                                    for(var optype in pendingOperations) {
                                        pendingOperations[optype]['action'] = 'ignore';
                                    }
                                    this.setState({pendingOperations:err.pendingOperations});
                                } else {
                                    this.setState({errors:[err]});
                                }
                            }
                        }>
                        Upload
                    </AjaxButton>
                </Modal.Footer>
            </Modal>
        );
    }
});
