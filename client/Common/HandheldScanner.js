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
                'keyup': function(e){
                    // Ignore keyups happening on something else than body
                    if (e.target.tagName !== 'BODY') {
                        return;
                    }
                    this._inputString += String.fromCharCode(e.which);

                    if (this._timeoutHandler) {
                        clearTimeout(this._timeoutHandler);
                    }
                    this._timeoutHandler = setTimeout(function () {
                        if (this._inputString.length <= 3) {
                            this._inputString = '';
                            return;
                        }

                        this.props.onScan(this._inputString);
                        this._inputString = '';
                    }.bind(this), 500)
                }.bind(this)
            }
        );
    },

    render: function(){
        return (
            <span className="glyphicon glyphicon-barcode"></span>
        );
    }
});
