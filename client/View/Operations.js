;
const React = require('react');
const {Row, Col, Button, Glyphicon} = require('react-bootstrap');
const Modal = require('../Modal/OperationUploadModal');
const BatchModal = require('../Modal/BatchUploadModal');
const promisify = require('../Common/connectWithPromise');
const OperationEditor = promisify(require('../Editor/OperationEditor'));
const moment = require('moment');
const Api = require('../Api');

// @todo put some proofing in operation screen (no null loca)
module.exports = React.createClass({

    propTypes: {
        siloBasePath: React.PropTypes.string.isRequired
    },

    getInitialState: function(){
        let filters = [];
        if (this.props.router) {
            filters = this.props.router.getParams();
        }
        return {
            showModal: false,
            showModalBis: false,
            filters: filters
        }
    },

    handleChangeFilter: function(filters){
        console.warn("this.props.router.setParams(filters)");
        for(var i in filters) {
            if(["cancelledAt", "doneAt", "requestedAt"].includes(filters[i].type)) {
                if(moment.isMoment(filters[i].value.startDate)) {
                    filters[i].value.startDate = filters[i].value.startDate.local().format('Y-MM-DD')
                }
                if(moment.isMoment(filters[i].value.endDate)) {
                    filters[i].value.endDate = filters[i].value.endDate.local().format('Y-MM-DD')
                }
            }
        }        
        //this.props.router.setParams(filters);
        this.setState({filters: filters, showModal: false, showModalBis: false});
    },

    render: function(){
        let promise = Api.fetch("/silo/inventory/operation/search", {
            method: "POST",
            body: JSON.stringify({filters: this.state.filters})
        });
        return (
            <div>
                <Row><Col xs={3}>
                    <h3>Operations</h3>
                </Col><Col xs={9}>
                    <Button bsStyle="default" onClick={()=>{this.setState({showModal: true})}}><Glyphicon glyph="plus" /> Create Operations</Button>
                    <Modal
                        show={this.state.showModal}
                        onHide={()=>this.setState({showModal:false})}
                        url={this.props.siloBasePath+"/inventory/operation/import"}
                        onSuccess={()=>{
                            this.handleChangeFilter(this.state.filters);
                        }} />
                    <Button bsStyle="default" onClick={()=>{this.setState({showModalBis: true})}}><Glyphicon glyph="plus" /> Edit Batches</Button>
                    <BatchModal
                        withLocation
                        show={this.state.showModalBis}
                        onHide={()=>this.setState({showModalBis:false})}
                        url={this.props.siloBasePath+"/inventory/batch/import"}
                        onSuccess={()=>{
                            this.handleChangeFilter(this.state.filters);
                        }}
                        />
                </Col></Row>

                <OperationEditor promise={promise} onFilterChange={this.handleChangeFilter} filters={this.state.filters}/>
            </div>
        );
    }
});
