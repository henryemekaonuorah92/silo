;
const React = require('react');

/**
 * Perfect for buttons that trigger actions on the backend via ajax call
 */
module.exports = React.createClass({

    propTypes: {
        data: React.PropTypes.any,
        contentType: React.PropTypes.any,
        onSuccess: React.PropTypes.func,
        onError: React.PropTypes.func,
        type: React.PropTypes.string,
        url: React.PropTypes.string.isRequired,
        disabled: React.PropTypes.bool,
        processData: React.PropTypes.bool
    },

    getDefaultProps: ()=>({
        onSuccess: ()=>{},
        onError: (message)=>{
            console.log(message)
        },
        type: "GET",
        data: null,
        contentType: false,
        processData: false
    }),

    getInitialState: () => ({
            wip: false
    }),

    send: function(){
        this.setState({wip: true});
        $.ajax({
            url: this.props.url,
            headers: {'Accept': 'application/json'},
            type: this.props.type,
            data: this.props.data,
            contentType: this.props.contentType,
            processData: this.props.processData
        })
            .done((data) => {
                this.setState({wip: false});
                if (typeof(data) === "object" && "errors" in data) {
                    this.props.onError(data.errors);
                } else {
                    this.props.onSuccess(data);
                }
            })
            .fail((jqXHR) => {
                let message = "Error while communicating";
                if ((jqXHR.status == 500 || jqXHR.status == 400) && jqXHR.readyState === 4 && jqXHR.responseText) {
                    let data = JSON.parse(jqXHR.responseText);
                    if (data.hasOwnProperty("message") && data.message !== "") {
                        message = data.message;
                    }
                    if (data.hasOwnProperty("errors")) {
                        message = data.errors;
                    }
                }

                this.setState({wip: false});
                this.props.onError(message);
            });
    },

    render: function() {
        let rest = Object.assign({}, this.props);
        delete rest.url; delete rest.onSuccess; delete rest.onError; delete rest.type; delete rest.data;
        delete rest.disabled; delete rest.contentType; delete rest.processData;
        return (
            <button onClick={this.send} {...rest} disabled={this.state.wip || this.props.disabled}>
                {this.state.wip && <span className="glyphicon glyphicon-refresh spinning" />} {this.props.children}
            </button>
        );
    }
});