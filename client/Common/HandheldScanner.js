;
const React = require('react');
const {Popover, OverlayTrigger} = require('react-bootstrap');
module.exports = React.createClass({
    getInitialState: function(){return{
        hasFocus: false
    }},
    getDefaultProps: function(){return{
        onScan: function(value){console.log('Scan '+value);}
    }},
    _timeoutHandler: 0,
    _inputString: '',

    // This thing does not work
    evalHasFocus: function(){
        console.log('has focus');
        this.setState({
            hasFocus: $(document.activeElement).prop("tagName") === "BODY"
        });
    },

    // @todo
    /*
    on open, focus on input
    on enter, send scan
    on close, focus body
    listen to enter to display the popover
    in ideal world, should be coupled to search ("f" for find, "s" for scan, "?" for help)
    f + scan => search !
     */

    componentDidMount: function(){
        $(document.body)
            .on({
                'keypress': function(e){
                    // Ignore keypresses happening on something else than body
                    if (e.target.tagName !== 'BODY') {
                        return;
                    }
                    let charCode = (typeof e.which === "number") ? e.which : e.keyCode;
                    this._inputString += String.fromCharCode(charCode);

                    if (this._timeoutHandler) {
                        clearTimeout(this._timeoutHandler);
                    }
                    this._timeoutHandler = setTimeout(function () {
                        if (this._inputString.length <= 3) {
                            this._inputString = '';
                            return;
                        }

                        this.props.onScan(this._inputString.trim());
                        this._inputString = '';
                    }.bind(this), 100)
                }.bind(this)
            }
        );
    },

    componentWillUnmount: function(){
        $(document.body).off('keypress');
        this._timeoutHandler = null;
    },

    render: function(){
        const popoverBottom = (
            <Popover id="popover-positioned-bottom" title="Manual Barcode entry">
                <div className="input-group">
                    <input type="text" className="form-control" placeholder="Enter barcode..." />
                    <span className="input-group-btn">
                        <button className="btn btn-success" type="button">Scan</button>
                    </span>
                </div>
            </Popover>
        );
        return (
        <OverlayTrigger trigger="click" placement="bottom" overlay={popoverBottom} show={true}>
            <span className="glyphicon glyphicon-barcode" />
        </OverlayTrigger>
        );
    }
});
