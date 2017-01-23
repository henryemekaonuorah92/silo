;
const React = require('react');

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
        return (
            <span className="glyphicon glyphicon-barcode"></span>
        );
    }
});
