;
import React from 'react';

/**
 * @see https://github.com/petermoresi/react-download-link/blob/master/download-link.es6
 */
module.exports = React.createClass({

    propTypes: {
        route: React.PropTypes.string,
        code: React.PropTypes.string,
    },

    handleClick: function(e){
        e.stopPropagation();
        // Legacy F+O code for links :/
        window.A.Page.Open('/silo/'+this.props.route+'/'+this.props.code);
    },

    render: function() {
        return (
            <a onClick={this.handleClick} style={{cursor: 'pointer'}}>
                {this.props.code}
            </a>
        );
    }
});
