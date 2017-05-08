;
const React = require('react');
const {Row, Col, Button, Glyphicon} = require('react-bootstrap');
const OperationEditor = require('./Editor/OperationEditor');
const Modal = require('./Modal/OperationUploadModal');
const BatchModal = require('./Modal/BatchUploadModal');

// @todo put some proofing in operation screen (no null loca)
module.exports = React.createClass({
    getInitialState: ()=>({
        showModal: false,
        showModalBis: false
    }),

    propTypes: {
        siloBasePath: React.PropTypes.string.isRequired
    },

    render: function(){
        return (
            <div>
                <Row><Col xs={3}>
                    <h3>Operation</h3>
                </Col><Col xs={9}>
                    <Button bsStyle="default" onClick={()=>{this.setState({showModal: true})}}><Glyphicon glyph="plus" /> Create Operations</Button>
                    <Modal
                        show={this.state.showModal}
                        onHide={()=>this.setState({showModal:false})}
                        url={this.props.siloBasePath+"/inventory/operation/import"}
                        onSuccess={()=>{
                            this.setState({showModal: false});
                            this.operationEditor.componentDidMount(); // refresh
                        }} />
                    <Button bsStyle="default" onClick={()=>{this.setState({showModalBis: true})}}><Glyphicon glyph="plus" /> Edit Batches</Button>
                    <BatchModal
                        withLocation
                        show={this.state.showModalBis}
                        onHide={()=>this.setState({showModalBis:false})}
                        url={this.props.siloBasePath+"/inventory/batch/import"}
                        onSuccess={()=>{
                            this.setState({showModalBis: false});
                            this.operationEditor.componentDidMount(); // refresh
                        }}
                        />
                </Col></Row>

                <OperationEditor ref={(ref)=>{this.operationEditor = ref;}} />
            </div>
        );
    }
});
