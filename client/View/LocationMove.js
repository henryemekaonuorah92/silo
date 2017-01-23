;
const React = require('react');
const HandheldScanner = require('../Common/HandheldScanner');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            confirmation: false,
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
        $.ajax({
            type: "POST",
            url: this.props.siloBasePath+"/inventory/operation",
            headers: {'Accept': 'application/json'},
            data: {
                children: this.state.children.slice(),
                parent: this.state.parent
            }
        })
        .done(function(data, textStatus, jqXHR){
            // @todo if jqXHR.status != 201 then do something
            this.setState({
                confirmation: true
            });
        }.bind(this));

        this.setState({
            children: []
        });
    },

    clearConfirmation: function(){
        this.setState({
            confirmation: false
        });
    },

    render: function(){
        const that = this;
        return (
            <div>
                {this.state.confirmation ?
                    <div className="text-center">
                        <span style={{fontSize: "50px"}} className="glyphicon glyphicon-ok" />
                        <h3>Parent assigned</h3>
                        <button className="btn btn-block btn-success" onClick={this.clearConfirmation}>Continue</button>
                    </div>
                    :
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
