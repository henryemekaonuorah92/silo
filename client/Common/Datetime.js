;
import React from 'react';
import moment from 'moment-timezone';

module.exports = React.createClass({

    propTypes: {
        //datetime: React.PropTypes.string.required
    },

    render: function() {
        //const rest = Object.assign({}, this.props);
        //delete rest.filename; delete rest.label; delete rest.exportFile;
        let tz = moment.tz.guess();
        return (
            <span>
                {this.props.children ? moment.tz(this.props.children, "UTC").clone().tz(tz).format('Y-MM-DD HH:mm') : ''}
            </span>
        );
    }
});
