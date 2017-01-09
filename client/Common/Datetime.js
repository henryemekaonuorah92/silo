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
        console.log(tz);
        return (
            <span>
                {moment.tz(this.props.children, tz).format('Y-MM-DD HH:mm z')}
            </span>
        );
    }
});
