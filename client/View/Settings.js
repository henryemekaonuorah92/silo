;
const React = require('react');
const Link = require('../Factory').Link;
const {Glyphicon} = require('react-bootstrap');

module.exports = React.createClass({

    render: function(){
        return (
            <div>
                <h3><Glyphicon glyph="cog" /> Settings</h3>
                {this.props.children}
                <hr />
                <h4>Take out</h4>
                <p>Silo makes easy for you to export its data. You can download a csv file holding the structure of the Inventory:</p>
                <p className="text-center">
                    <a className="btn btn-primary" href="/silo/inventory/export/batches" target="_self">Export Batches</a>
                    &nbsp;
                    <a className="btn btn-primary" href="/silo/inventory/export/locations" target="_self">Export Locations</a>
                </p>
            </div>
        );
    }
});
