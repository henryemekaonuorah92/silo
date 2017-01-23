;
import React from 'react';
import HandheldScanner from '../Common/HandheldScanner';

module.exports = React.createClass({

    getInitialState: function () {
        return {
            parent: null,
            childs: [],
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
                    this.state.childs.push(value);
                    this.setState({
                        childs: this.state.childs.slice()
                    });
                }
                break;
            case 2:
                if (this.state.parent === value) {
                    console.log('execute', this.state.childs.slice());
                    this.setState({
                        childs: []
                    });
                } else {
                    this.state.childs.push(value);
                    this.setState({
                        childs: this.state.childs.slice()
                    });
                }
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
        else if (this.state.childs.length === 0) {
            return 1;
        } else {
            return 2;
        }
    },

    render: function(){
        return (
            <div>
                <HandheldScanner onScan={this.handleScan} />
                <div>{this.stepHelp[this.getStep()]}</div>
                <div>Parent:{this.state.parent}</div>
                <div>Childs:
                    <ul>
                        {this.state.childs.map(function(child, key){
                            return <li key={key}>{child}</li>;
                        })}
                    </ul>
                </div>
            </div>
        );
    }
});
