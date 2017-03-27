;
const React = require('react');

/**
 * Upload popover to be used on all screens needing an import
 */
module.exports = React.createClass({

    propTypes: {
        data: React.PropTypes.any,
        contentType: React.PropTypes.string,
        onSuccess: React.PropTypes.func,
        onError: React.PropTypes.func,
        url: React.PropTypes.string.isRequired,
        disabled: React.PropTypes.bool
    },

    getDefaultProps: ()=>({
        onSuccess: ()=>{},
        onError: (message)=>{
            console.log(message)
        },
        data: null,
        contentType: null
    }),

    getInitialState: () => ({
        wip: false
    }),

    send: function(){
        let fileInput = this.refs.file;
        if (!fileInput.files[0]) {
            this.props.onError("Please select a file.");
            return;
        }

        let formData = new FormData();
        formData.append('file', fileInput.files[0]);

        this.setState({wip: true});
        $.ajax({
            url: this.props.url,
            headers: {'Accept': 'application/json'},
            type: "POST",
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false
        })
            .done((data) => {
                this.setState({wip: false});
                if (res && res.errors) {
                    this.setState({errors: res.errors, wip:false});
                } else {
                    this.setState({errors: [], wip:false});
                    this.props.onSuccess(res);
                }

                this.props.onSuccess(data);
            })
            .fail((jqXHR) => {
                let message = "Error while communicating";
                if (jqXHR.status === 500 && jqXHR.readyState === 4 && jqXHR.responseText) {
                    let data = JSON.parse(jqXHR.responseText);
                    if (data.hasOwnProperty("message")) {
                        message = data.message;
                    }
                }

                this.setState({wip: false});
                this.props.onError(message);
            });
    },

    render: function(){
        const rem = Object.assign({}, this.props);
        delete rem.url;
        delete rem.onSuccess;

        return (
            <div className="input-group">
                <input className="form-control" type="file" ref="file" />
                <span className="input-group-btn">
                    <button onClick={this.send} className="btn btn-primary" disabled={this.state.wip || this.props.disabled}>Upload</button>
                </span>
            </div>
        );
    }
});
