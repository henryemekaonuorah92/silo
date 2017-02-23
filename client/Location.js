;
const React = require('react');
const BatchEditor = require('./Editor/BatchEditor');
const DataStore = require('./Editor/DataStore');
const Link = require('./Common/Link');

module.exports = React.createClass({

    getInitialState: function () {
        return {
            data: {},
            batches: new DataStore([])
        };
    },

    getDefaultProps: function() {
        return {
            siloBasePath: null,
            code: 'root',
            writable: false
        };
    },

    propTypes: {
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
        writable: React.PropTypes.bool
    },

    componentDidMount: function () {
        this.props.cache
            .getFrom(this.props.siloBasePath+"/inventory/location/"+this.props.code)
            .onUpdate(value => {
                this.setState({data: value});
            })
            .refresh();

        this.props.cache
            .getFrom(this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches')
            .onUpdate(value => {
                this.setState({batches: new DataStore(value)});
            })
            .refresh();
    },

    refresh: function(){
        let key = 'locationBatch/'+this.props.code;
        this.props.cache.clear(key);
        this.props.cache.get(key).then(function(value){
            this.setState({
                batches: new DataStore(value)
            });
        }.bind(this));
    },

    render: function(){
        let data = this.state.data;
        let uploadUrl = this.props.siloBasePath+"/inventory/location/"+this.props.code+'/batches';
        return (
            <div>
                <h3>{this.props.code}</h3>
                {data ? (<div>
                        <b>Parent:</b>&nbsp;{data.parent ? <Link route="location" code={data.parent} /> : "No parent"}<br />
                        <b>Childs:</b>&nbsp;
                        {data.childs ? <ul>{data.childs.map(function(child, key){return <li key={key}>
                                <Link route="location" code={child} />
                            </li>;})}</ul> : "No child"
                        }<br />
                        <b>Batches:</b>
                    <BatchEditor
                        exportFilename={'location-'+this.props.code+'-batches.csv'}
                        batches={this.state.batches} uploadUrl={uploadUrl} onNeedRefresh={this.refresh} writable={this.props.writable}
                        batchColumns={this.props.batchColumns}/>
                </div>) : "Loading data"}
            </div>
        );
    }
});
