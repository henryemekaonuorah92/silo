;
import React from 'react';
import {Alert} from 'react-bootstrap';

/**
 * Upload popover to be used on all screens needing an import
 */
module.exports = React.createClass({
    getInitialState: function(){return {
        errors: []
    }},
    propTypes: {
        /**
         * URL where to send the file
         */
        url: React.PropTypes.string.isRequired,
        /**
         * Callback used when download has been succesfull
         */
        onSuccess: React.PropTypes.func
    },
    getDefaultProps: function(){return {

    }},
    handleClick: function(){
        let fileInput = this.refs.file;
        if (!fileInput.files[0]) {
            this.setState({errors: ["Please select a file."]});
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
                    this.props.onSuccess(res);
                }
            }.bind(this),
            error: function () {
                this.setState({errors: ["Error while uploading."]});
            }.bind(this)
        });
    },
    render: function(){
        const rem = Object.assign({}, this.props);
        delete rem.url;
        delete rem.onSuccess;

        return (
            <div {...rem}>
                <div className="input-group">
                    <input className="form-control" type="file" ref="file" />
                    <span className="input-group-btn">
                        <button onClick={this.handleClick} className="btn btn-primary">Upload</button>
                    </span>
                </div>

                {this.state.errors.length > 0 && (
                    <Alert bsStyle="warning" style={{margin:"10px 0"}}>
                        <strong>Holy guacamole!</strong>
                        <ul>{this.state.errors.map((error, idx)=>(<li key={idx}>{error}</li>))}</ul>
                    </Alert>
                )}

                {this.props.children}
            </div>
        );
    }
});
