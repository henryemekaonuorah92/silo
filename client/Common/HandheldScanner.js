;
const React = require('react');
const ReactDOM = require('react-dom');
const {Popover, Overlay} = require('react-bootstrap');

/**
 * Provide a handheld scanner handler component.
 * Expose an interface for manual input as a fallback.
 *
 * @todo this could be greatly improved by:
 * - detect raw keys to send commands, like "s" for scan, "?" for help
 */
module.exports = React.createClass({
    getInitialState: function(){return{
        show: false
    }},

    getDefaultProps: function(){return{
        onScan: function(value){console.log('Scan '+value);}
    }},

    _timeoutHandler: 0,
    _inputString: '',

    toggle() {
        let show = !this.state.show;
        this.setState({ show: show }, function(){
            if (show) {
                this.manualInput.focus();
            } else {
                this.manualInput.blur();
            }
        });
    },

    handleManualInput: function(){
        let data = this.manualInput.value.trim();
        if (data.length > 0) {
            console.log("Scan", data);
            this.props.onScan(data);
            this.manualInput.value = null;
        }
    },

    handleKeyPress: function(e) {
        if (e.key === 'Enter') {
            this.handleManualInput();
        }
        let charCode = (typeof e.which === "number") ? e.which : e.keyCode;
        if (String.fromCharCode(charCode) === '/') {
            this.manualInput.value = null;
            this.toggle();
        }
    },

    componentDidMount: function(){
        let handler = (e) => {
            // Ignore keypresses happening on something else than body
            if (e.target.tagName !== 'BODY') {
                return;
            }
            let charCode = (typeof e.which === "number") ? e.which : e.keyCode;
            this._inputString += String.fromCharCode(charCode);

            if (this._timeoutHandler) {
                clearTimeout(this._timeoutHandler);
            }
            this._timeoutHandler = setTimeout(() => {
                if (this._inputString.length <= 3) {
                    if (this._inputString == '/') {
                        this.toggle();
                    }

                    this._inputString = '';
                    return;
                }

                this.props.onScan(this._inputString.trim());
                this._inputString = '';
            }, 100)
        }

        document.addEventListener("keypress", handler, false);
        this.removeEventListener = () => {
            document.removeEventListener("keypress", handler, false);
        }
    },

    componentWillUnmount: function(){
        this.removeEventListener();
        this._timeoutHandler = null;
    },

    render: function(){
        return (
            <span className="glyphicon glyphicon-barcode" ref="target" onClick={this.toggle}>
                <Overlay show={this.state.show}
                         onHide={() => this.setState({ show: false })}
                         placement="bottom"
                         target={() => ReactDOM.findDOMNode(this.refs.target)}
                >
                    <Popover id="popover-positioned-bottom" title="Manual Barcode entry">
                        <div className="input-group">
                            <input type="text" ref={(input) => {this.manualInput = input;}} className="form-control" placeholder="Enter barcode..." onKeyPress={this.handleKeyPress} />
                            <span className="input-group-btn">
                                <button className="btn btn-success" type="button" onClick={this.handleManualInput}>Scan</button>
                            </span>
                        </div>
                    </Popover>
                </Overlay>
            </span>

        );
    }
});
