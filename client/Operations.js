;
const React = require('react');
const {Alert, Row, Col} = require('react-bootstrap');
const OperationEditor = require('./Editor/OperationEditor');
const DataStore = require('./Editor/DataStore');
const PopoverUploadButton = require('./Common/PopoverUploadButton');

// @todo put some proofing in operation screen (no null loca)
module.exports = React.createClass({
    getInitialState: function(){return {
        errors: [],
        success: [],
        operations : new DataStore([]),
        wip: false
    }},
    propTypes: {
        /**
         * URL where to send the file
         */
        url: React.PropTypes.string
    },
    getDefaultProps: function(){return {
        title: "Upload",
        url: "/silo/inventory/operation/import"
    }},

    componentDidMount: function () {
        this.refresh();
    },

    refresh: function(){
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
                <Row><Col md={3}>
                    <h3>Operation</h3>
                </Col><Col md={3}>
                    <PopoverUploadButton url={this.props.url} onSuccess={this.refresh} title="Create">
                        You can create here an Operation, the most basic movement object for Silo.

                        Use the following format:
                        <pre>{`source,target,sku,quantity
VOID,MTLST,something,2
MTLST,VOID,some-other-thing,2
OTTST,MTLST,sku2,4`}</pre>

                        The first line is a <b>creation</b> of product.
                        The second line is a <b>deletion</b> of product.
                        The third line is a movement from OTTST to MTLST.
                    </PopoverUploadButton>
                </Col></Row>

                <OperationEditor operations={this.state.operations} onNeedRefresh={this.refresh} />
            </div>
        );
    }
});
