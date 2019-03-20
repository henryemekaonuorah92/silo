;
const React = require('react');

const BatchEditor = require('../Editor/BatchEditor');
const promisify = require('../Common/connectWithPromise');
const OperationEditor = promisify(require('../Editor/OperationEditor'));
const withLocationModifier = require('../Editor/withLocationModifier');
const ModifierEditor = withLocationModifier(require('../Editor/ModifierEditor'));
const Modal = require('../Modal/BatchUploadModal');
const Link = require('../Factory').Link;
const Api = require('../Api');
const DownloadDataLink = require('../Common/DownloadDataLink');
const {Label} = require('react-bootstrap')
const AjaxButton = require('../Common/AjaxButton');

module.exports = React.createClass({

    getInitialState: function () {
        let filters = [];
        if (this.props.router) {
            filters = this.props.router.getParams();
            if (!filters) {filters = []}
        }
        filters.push({
            type:"location",
            value:[this.props.code],
            editable: false
        })
        return {
            data: {},
            batches: null,
            filters: filters
        };
    },

    handleChangeFilter: function(filters){
        const fil = filters.filter(a => a.type !== 'location')
        if (this.props.router) {
            this.props.router.setParams(fil);
        }
        this.setState({filters: filters});
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            code: 'root',
            writable: false,
            endpoint: '%siloBasePath%/inventory/location/%code%',
            batchEndpoint: '%siloBasePath%/inventory/location/%code%/batches',
            menu: ()=>null
        };
    },

    propTypes: {
        cache: React.PropTypes.object.isRequired,
        /**
         * URL where to send the file
         */
        url: React.PropTypes.string,
        /**
         * Callback used when download has been succesfull
         */
        onSuccess: React.PropTypes.func,
        /**
         * @todo this is very bad ACL design, change that
         */
        writable: React.PropTypes.bool,

        batchEndpoint: React.PropTypes.string,

        menu: React.PropTypes.any,

        modifierFactory: React.PropTypes.any.isRequired
    },

    componentDidMount: function () {
        function replace(str) {
            return str.replace('%siloBasePath%', this.props.siloBasePath).replace('%code%', this.props.code);
        };
        this.locationCache = this.props.cache
            .getFrom(replace.apply(this, [this.props.endpoint]))
            .onUpdate(value => {
                this.setState({
                    data: value
                });
            })
            .refresh();

        this.batchCache = this.props.cache
            .getFrom(replace.apply(this, [this.props.batchEndpoint]))
            .onUpdate(value => {
                this.setState({batches: value});
            })
            .refresh();
    },

    refresh: function(){
        this.batchCache.refresh();
    },

    // handleDelete: function(){
    //     Api.fetch(this.props.siloBasePath+"/inventory/location/"+this.props.code, {method: "DELETE"});
    // },

    componentWillUnmount : function () {
        this.locationCache.cleanup();
        this.batchCache.cleanup();
    },

    render: function(){
        let data = this.state.data;
        let menus = Array.isArray(this.props.menu) ? this.props.menu : [this.props.menu];
        if(!this.props.customInclusiveBatches) {
            menus.push(
                <li>
                    <DownloadDataLink
                        filename={'location-'+this.props.code+'-inclusiveBatches.csv'}
                        exportFile={()=>{
                            return Api.fetch('/silo/inventory/location/'+this.props.code+'/inclusiveBatches').then(data=>{
                                let header = "product,sku,quantity\n";
                                return header + data.map(function(data){
                                    return data.sku+','+data.name+','+data.quantity
                                }).join("\n")
                            })
                        }}>
                        Save Inclusive CSV
                    </DownloadDataLink>
                </li>
            );
        } else {
            menus.push(<li>{this.props.customInclusiveBatches}</li>)
        }
        if (this.props.writable) {
            menus.push(<li>
                <a onClick={()=>this.setState({showModal: true})}>Open CSV...</a>
                <Modal
                    show={this.state.showModal}
                    onHide={()=>this.setState({showModal:false})}
                    url={this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches'}
                    onSuccess={()=>{
                        this.setState({ showModal: false });
                        this.refresh();
                    }} />
            </li>);
        }

        // <button className="btn btn-danger" onClick={this.handleDelete}>Delete</button>
        let promise = Api.fetch("/silo/inventory/operation/search", {
            method: "POST",
            body: JSON.stringify({filters: this.state.filters})
        });

        let isDeleted = data && data.isDeleted

        let h3Style = {
            textDecoration: isDeleted ? 'line-through' : ''
        }

        return (
            <div>
                <h3><span className="glyphicon glyphicon-map-marker" />
                    <span style={h3Style}>Location {this.props.code}</span> {isDeleted && <Label bsStyle="danger">DELETED</Label>}</h3>

                {data ? (<div>
                    <b>Parent:</b>&nbsp;{data.parent ? <Link route="location" code={data.parent} /> : "No parent"}<br />
                    <b>Childs:</b>&nbsp;
                    {data.childs ? <ul>{data.childs.sort().map(function(child, key){return <li key={key}>
                            <Link route="location" code={child} />
                        </li>;})}</ul> : "No child"
                    }<br />
                    {this.props.children}


                </div>) : "Loading data"}

                {isDeleted && <AjaxButton
                    url={this.props.siloBasePath + "/inventory/location/" + this.props.code + "/respawn"}
                    type="POST"
                    onSuccess={() => {
                        this.refresh()
                    }}
                    onError={(msg) => {
                        window.alert(msg)
                    }}
                    className="btn btn-warning">
                    Respawn Location
                </AjaxButton>}

                <ModifierEditor cache={this.props.cache}
                                siloBasePath={this.props.siloBasePath}
                                location={this.props.code}
                                modifierFactory={this.props.modifierFactory}
                                writable={this.props.writable}
                />

                <BatchEditor
                    bindedMethodLink={this.props.bindedMethodLink}
                    bindedMethod={this.props.bindedMethod}
                    exportFilename={'location-'+this.props.code+'-batches.csv'}
                    data={this.state.batches}
                    menu={menus}
                    error={null}
                    customExportFile={this.props.customExportFile}
                    />

                <OperationEditor promise={promise} onFilterChange={this.handleChangeFilter} filters={this.state.filters} />
            </div>
        );
    }
});
