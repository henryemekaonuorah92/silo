;
const React = require('react');
const HandheldScanner = require('../Common/HandheldScanner');
const Success = require('./Success');
const Error = require('./Error');
const Pending = require('./Pending');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            confirmation: false,
            error: false,
            parent: null,
            children: [],
        };
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            id: null
        };
    },

    handleScan: function(value){
        switch (this.getStep()) {
            case 0:
                this.setState({
                    parent: value
                });
                break;
            case 1:
                if (this.state.parent === value) {
                    console.log('Empty move is not allowed');
                } else {
                    this.state.children.push(value);
                    this.setState({
                        children: this.state.children.slice()
                    });
                }
                break;
            case 2:
                if (this.state.parent === value) {
                    this.execute();
                } else {
                    this.state.children.push(value);
                    this.setState({
                        children: this.state.children.slice()
                    });
                }
                break;
        }
    },

    stepHelp: [
        "Scan Parent",
        "Scan a Child",
        "Rescan Parent to execute"
    ],

    getStep: function(){
        if (!this.state.parent) {
            return 0;
        }
        else if (this.state.children.length === 0) {
            return 1;
        } else {
            return 2;
        }
    },

    clearParent: function(){
        this.setState({
            parent: null
        });
    },

    clearChild: function(index){
        this.state.children.splice(index, 1);
        this.setState({
            children: this.state.children.slice()
        });
    },
    
    execute: function(){
        $.post(
            this.props.siloBasePath+"/inventory/operation",
            {
                children: this.state.children.slice(),
                parent: this.state.parent
            })
        .done(function(data, textStatus, jqXHR){
            // @todo if jqXHR.status != 201 then do something
            this.setState({
                confirmation: true
            });
        }.bind(this))
        .fail(function(data, textStatus, jqXHR){
            // @todo if jqXHR.status != 201 then do something
            const error = JSON.parse(data.responseText);
            this.setState({
                error: error ? error.message : 'No message'
            });
        }.bind(this));

        this.setState({
            parent: null,
            children: []
        });
    },

    clearConfirmation: function(){
        this.setState({
            confirmation: false,
            error: false
        });
    },

    render: function(){
        const that = this;
        return (
            <div>
                {this.state.confirmation &&
                    <Success title="Parent assigned" onAck={this.clearConfirmation} />
                }
                {this.state.error &&
                    <Error title="Failure" onAck={this.clearConfirmation} />
                }
                {!this.state.confirmation && !this.state.error &&
                    <div>
                        <div className="panel panel-default">
                            <div className="panel-body text-center">{this.stepHelp[this.getStep()]} <HandheldScanner onScan={this.handleScan} /></div>
                        </div>
                        <div className="btn btn-block btn-primary" onClick={this.clearParent}>{this.state.parent ? "TO " + this.state.parent : "NO TARGET"}</div>

                        <ul>
                            {this.state.children.map(function(child, key){
                                return <div className="btn btn-block btn-default" key={key} onClick={that.clearChild.bind(that, key)}>{child}</div>;
                            })}
                        </ul>

                        {this.state.children.length > 0 &&
                            <div className="btn btn-block btn-primary" onClick={this.execute}>EXECUTE</div>
                        }
                    </div>
                }
            </div>
        );
    }
});
