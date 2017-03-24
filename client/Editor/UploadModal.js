;
const React = require('react');
const Modal = require('../Common/Modal');
const UploadField = require('./UploadField');

module.exports = React.createClass({
    getInitialState() {
        return {
            merge: true,
            showModal: false
        };
    },
    propTypes: {
        //url: React.propTypes.string.required
    },
    getDefaultProps: function(){return {
        onSuccess: function(){}
    }},

    handleSuccess() {
        this.setState({ showModal: false });
        this.props.onSuccess();
    },

    handleChange(flag) {
        this.setState({merge: flag});
    },

    render() {
        let startWith = this.state.merge ? "merge" : "replace";
        return (
            <a onClick={()=>this.setState({showModal: true})}>
                CSV Upload

                <Modal show={this.state.showModal} onHide={()=>this.setState({showModal:false})} title="CSV Upload">
                    <p>Mass edit those batches by uploading a CSV. You can either:</p>
                    <div className="radio">
                        <label>
                            <input type="radio" name="optionsRadios"
                                   onChange={this.handleChange.bind(this, true)}
                                   checked={this.state.merge} />
                            <b>Merge</b> the uploaded batches with the current set
                        </label>
                    </div>
                    <div className="radio">
                        <label>
                            <input type="radio" name="optionsRadios"
                                   onChange={this.handleChange.bind(this, false)}
                                   checked={!this.state.merge} />
                            <b>Replace</b> the current set by the uploaded batches
                        </label>
                    </div>
                    <p>Please note this will create a single Operation performing the requested action.</p>
                    <p>Expected format is:</p>
                    <pre>{startWith}{`
product,quantity
31-232-25,15
14-231-21,-2`}</pre>
                    <UploadField url={this.props.url+'?merge='+(this.state.merge ? 'true' : 'false')} onSuccess={this.handleSuccess} />
                </Modal>
            </a>
        );
    }
});
