;
const React = require('react');
const HandheldScanner = require('../Common/HandheldScanner');
const AjaxButton = require('../Common/AjaxButton');
const Success = require('../Common/Success');
const Error = require('../Common/Error');
const Pending = require('../Common/Pending');
const CloseButton = require('../Common/CloseButton');
const {Row, Col, Well} = require('react-bootstrap');

module.exports = React.createClass({

    getInitialState: () => ({
        confirmation: false,
        error: false,
        parent: null,
        children: [],
    }),

    propTypes: {
        // Base API Path
        siloBasePath: React.PropTypes.string.isRequired
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
                    this.button.click();
                } else {
                    this.state.children.push(value);
                    this.setState({
                        children: this.state.children.slice()
                    });
                }
                break;
        }
    },

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

    clearConfirmation: function(){
        this.setState({
            confirmation: false,
            error: false
        });
    },

    render: function(){
        return (
            <div>
                {this.state.confirmation &&
                    <Success title="Parent assigned" onAck={this.clearConfirmation} />
                }
                {this.state.error &&
                    <Error title="Failure" description={this.state.error} onAck={this.clearConfirmation} />
                }
                {!this.state.confirmation && !this.state.error &&
                    <div>
                        <div className="text-center"><HandheldScanner onScan={this.handleScan} /></div>

                        <Row><Col xs={6}>
                            <Well bsSize="sm">
                                <b>SOURCE:</b>
                                <p>ANY</p>
                            </Well>
                        </Col><Col xs={6}>
                            <Well bsSize="sm">
                                <b>TARGET:</b>
                                { this.state.parent ?
                                    <p>
                                        <CloseButton onClick={this.clearParent} />
                                        {this.state.parent}
                                    </p>
                                    :
                                    <p>Scan a parent</p>
                                }
                            </Well>
                        </Col></Row>

                        { this.state.children.length === 0 ?
                            <p>Scan a child</p>
                            :
                            <div>

                                {this.state.children.map((child, key)=>{
                                    return <div className="well well-sm" key={key}>
                                        <CloseButton onClick={this.clearChild.bind(this, key)} />
                                        {child}
                                    </div>;
                                })}

                                <p>Scan more children</p>
                                <AjaxButton
                                    url={this.props.siloBasePath + "/inventory/location/" + this.state.parent + "/child"}
                                    type="PATCH"
                                    ref={ref => this.button = ref}
                                    contentType="application/json"
                                    data={JSON.stringify(this.state.children.slice())}
                                    onSuccess={() => {
                                        this.setState({
                                            confirmation: true,
                                            parent: null,
                                            children: []
                                        });
                                    }}
                                    onError={(msg) => {
                                        this.setState({
                                            error: msg,
                                            parent: null,
                                            children: []
                                        });
                                    }}
                                    className="btn btn-block btn-primary">
                                    Assign
                                </AjaxButton>
                            </div>

                        }
                        {this.props.children}
                    </div>
                }
            </div>
        );
    }
});
