;
const React = require('react');

/**
 * @see https://github.com/petermoresi/react-download-link/blob/master/download-link.es6
 */
module.exports = React.createClass({

    propTypes: {
        route: React.PropTypes.string,
        code: React.PropTypes.any.isRequired
    },

    render: function(route, code) {
        return (
            <a onClick={(e)=>{
                e.stopPropagation();
                window.A.Page.Open('/silo/'+route+'/'+code);
            }} style={{cursor: 'pointer'}}
               href={'/#!/silo/'+route+'/'+code}>
                {this.props.children || this.props.code}
            </a>
        );
    }
});
