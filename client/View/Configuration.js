;
const React = require('react');
const fetch = require('silo-core/client/Api').fetch;
const {Button} = require('react-bootstrap');

module.exports = React.createClass({
    getInitialState: function() {
        return {
            data: {},
            saved: false
        };
    },
    componentDidMount: function() {
        fetch('/silo/config').then(data=>{
            this.setState({data})
        })
    },
    sendToServer: function(){
        fetch('/silo/config', {method: "POST", body:JSON.stringify(this.state.data)})
            .catch(console.log)
    },
    handleChange: function(path, newValue){

        if(typeof(newValue) === 'object'){
           newValue = newValue.target.value;
        }
        var data = Object.assign({}, this.state.data);
        data[path] = newValue;
        this.setState({data: data});
        /*
        window.onbeforeunload = function(){
            return 'Are you sure you want to leave?';
        };
        */
    },

    render: function(){
        let {sections} = this.props;
        return (
            <div>
                {sections.map((section, key)=>React.createElement(section, {
                    key,
                    onChange:this.handleChange,
                    data: this.state.data
                }))}

                <Button bsStyle="success" onClick={this.sendToServer}>Save</Button>

                <h3>Take out</h3>
                <p>Silo makes easy for you to export its data. You can download a csv file holding the structure of the Inventory:</p>
                <p>
                    <a className="btn btn-primary" href="/silo/inventory/export/batches" target="_self">Export Batches</a>
                    &nbsp;
                    <a className="btn btn-primary" href="/silo/inventory/export/locations" target="_self">Export Locations</a>
                </p>
            </div>
        );
    }
});
