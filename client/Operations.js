;
const React = require('react');
const {Row, Col, Button, Glyphicon} = require('react-bootstrap');
const OperationEditor = require('./Editor/OperationEditor');
const DataStore = require('./Editor/DataStore');
const Modal = require('./Modal/OperationUploadModal');

// @todo put some proofing in operation screen (no null loca)
module.exports = React.createClass({
    getInitialState: function(){return {
        operations : new DataStore([]),
        showModal: false
    }},

    propTypes: {
        siloBasePath: React.PropTypes.string.isRequired
    },

    componentDidMount: function () {
        this.setState({
            operations: new DataStore([])
        });
        $.ajax(
            this.props.siloBasePath+"/inventory/operation/",
            {
                success: function (data) {
                    this.setState({
                        operations: new DataStore(data)
                    });
                }.bind(this),
                headers: {'Accept': 'application/json'}
            }
        );
    },

    render: function(){
        return (
            <div>
                <Row><Col xs={3}>
                    <h3>Operation</h3>
                </Col><Col xs={3}>
                    <Button bsStyle="primary" onClick={()=>{this.setState({showModal: true})}}><Glyphicon glyph="plus" /> Upload</Button>
                    <Modal
                        show={this.state.showModal}
                        onHide={()=>this.setState({showModal:false})}
                        url={this.props.siloBasePath+"/inventory/operation/import"}
                        onSuccess={()=>{
                            this.setState({showModal: false});
                            this.componentDidMount();
                        }} />

                </Col></Row>

                <OperationEditor operations={this.state.operations} onNeedRefresh={this.componentDidMount} />
            </div>
        );
    }
});
