;
const React = require('react');
const {Alert} = require('react-bootstrap');
const OperationEditor = require('./Editor/OperationEditor');
const DataStore = require('./Editor/DataStore');

// @todo put some proofing in operation screen (no null loca)
module.exports = React.createClass({
    getInitialState: function(){return {
        errors: [],
        success: [],
        operations : new DataStore([]),
        wip: false
    }},
    propTypes: {
        /**
         * URL where to send the file
         */
        url: React.PropTypes.string,
        /**
         * Callback used when download has been succesfull
         */
        onSuccess: React.PropTypes.func
    },
    getDefaultProps: function(){return {
        title: "Upload",
        url: "/silo/inventory/operation/import",
        onSuccess: function(){}
    }},

    componentDidMount: function () {
        this.refresh();
    },

    refresh: function(){
        this.setState({
            operations: new DataStore([])
        });
        $.ajax(
            this.props.siloBasePath+"/inventory/operation/",
            {
                success: function (data) {
                    this.setState({
                        operations: new DataStore(data)
                    });
                }.bind(this),
                headers: {'Accept': 'application/json'}
            }
        );
    },

    handleClick: function(){
        this.setState({
            success: [],
            errors: [],
            wip: true
        });

        let fileInput = this.refs.file;
        if (!fileInput.files[0]) {
            this.setState({errors: ["Please select a file"]});
            return;
        }

        let formData = new FormData();
        formData.append('file', fileInput.files[0]);

        $.ajax({
            url: this.props.url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.errors) {
                    this.setState({errors: res.errors, wip: false});
                } else {
                    this.setState({success: ["Operation successful"], wip: false});
                    this.refresh();
                    this.props.onSuccess(res);
                }
            }.bind(this),
            error: function () {
                this.setState({errors: ["Error while uploading"], wip: false});
            }.bind(this)
        });
    },

    render: function(){
        return (
            <div>
                <h3>Operation</h3>
                <OperationEditor operations={this.state.operations} onNeedRefresh={this.refresh} />

                <hr />
                You can create here an Operation, the most basic movement object for Silo.

                Use the following format:
                <pre>{`source,target,sku,quantity
VOID,MTLST,something,2
MTLST,VOID,some-other-thing,2
OTTST,MTLST,sku2,4`}</pre>

                The first line is a <b>creation</b> of product.
                The second line is a <b>deletion</b> of product.
                The third line is a movement from OTTST to MTLST.

                <div className="input-group">
                    <input className="form-control" type="file" ref="file" />
                    <span className="input-group-btn">
                        <button onClick={this.handleClick} className="btn btn-primary" disabled={this.state.wip}>Upload</button>
                    </span>
                </div>

                {this.state.success.length > 0 && (
                    <Alert bsStyle="success" style={{margin:"10px 0"}}>
                        <strong>Holy guacamole!</strong>
                        <ul>{this.state.success.map((success, idx)=>(<li key={idx}>{success}</li>))}</ul>
                    </Alert>
                )}

                {this.state.errors.length > 0 && (
                    <Alert bsStyle="warning" style={{margin:"10px 0"}}>
                        <strong>Ooops!</strong>
                        <ul>{this.state.errors.map((error, idx)=>(<li key={idx}>{error}</li>))}</ul>
                    </Alert>
                )}
            </div>
        );
    }
});
