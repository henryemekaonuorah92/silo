;
const React = require('react');
const {OverlayTrigger, Button, Popover, Alert} = require('react-bootstrap');
const Alerts = require('./Alerts');
/**
 * Upload popover to be used on all screens needing an import
 */
module.exports = React.createClass({
    getInitialState: function(){return {
        errors: [],
        busy: false
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
        title: "Upload"
    }},
    handleClick: function(){
        let fileInput = this.refs.file;
        if (!fileInput.files[0]) {
            this.setState({errors: ["Please select a file."]});
            return;
        }

        let formData = new FormData();
        formData.append('file', fileInput.files[0]);
        this.setState({busy: true});
        $.ajax({
            url: this.props.url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (res) {
                if (res && res.errors) {
                    this.setState({errors: res.errors, busy: false});
                } else {
                    this.setState({busy: false, errors: []});
                    this.refs.popover.hide();
                    this.props.onSuccess(res);
                }
            }.bind(this),
            error: function () {
                this.setState({errors: ["Error while uploading."], busy: false});
            }.bind(this)
        });
    },
    render: function(){
        const popover = (
            <Popover id="popover-positioned-right" className="popover_wider">
                <div className="row"><div className="col-lg-12">
                    <div className="input-group">
                        <input className="form-control" type="file" ref="file" />
                        <span className="input-group-btn">
                        <button onClick={this.handleClick} className="btn btn-primary" disabled={this.state.busy}>Upload</button>
                      </span>
                    </div>

                    <Alerts alerts={this.state.errors} />

                    {this.props.children}
                </div></div>
            </Popover>
        );
        return (
            <OverlayTrigger trigger="click" placement="bottom" ref="popover" overlay={popover}>
                <Button><span className="glyphicon glyphicon-plus" /> {this.props.title}</Button>
            </OverlayTrigger>
        );
    }
});
