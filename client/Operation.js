;
import React from 'react';
import {Alert} from 'react-bootstrap';

module.exports = React.createClass({
    getInitialState: function(){return {
        errors: [],
        success: []
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
    handleClick: function(){
        this.setState({success: []});

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
                    this.setState({errors: res.errors});
                } else {
                    this.setState({success: ["Operation successful"]});
                    this.props.onSuccess(res);
                }
            }.bind(this),
            error: function () {
                this.setState({errors: ["Error while uploading"]});
            }.bind(this)
        });
    },

    render: function(){
        return (
            <div>
                <h3>Operation</h3>
                You can create here an Operation, the most basic movement object for Silo.

                Use the following format:
                <pre>{`source;target;sku;quantity
;MTLST;something;2
MTLST;some-other-thing;2
OTTST;MTLST;sku2;4`}</pre>

                The first line is a <b>creation</b> of product.
                The second line is a <b>deletion</b> of product.
                The third line is a movement from OTTST to MTLST.

                <div className="input-group">
                    <input className="form-control" type="file" ref="file" />
                    <span className="input-group-btn">
                        <button onClick={this.handleClick} className="btn btn-primary">Upload</button>
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
