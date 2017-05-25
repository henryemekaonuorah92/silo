;
const React = require('react');
const {AsyncTypeahead} = require('react-bootstrap-typeahead');
const request = require('superagent');

/**
 * Holds the filter state
 * // works well with Location and User
 */
module.exports = React.createClass({
    getInitialState: ()=>({
        options: [],
    }),

    getDefaultProps: ()=>({
        allowNew: false,
        multiple: false,
    }),

    _handleSearch: function(query) {
        if (!query) {
            return;
        }
        request.post(this.props.url)
            .send({query: query})
            .end((err, resp) => {
                this.setState({options: resp.body})
            });
    },

    _renderMenuItemChildren: function(option, props, index) {
        return (
            <div key={option}>{option}</div>
        );
    },

    render: function(){
        return <AsyncTypeahead
            options={this.state.options}
            onSearch={this._handleSearch}
            placeholder={this.props.placeholder}
            renderMenuItemChildren={this._renderMenuItemChildren}
            {...this.props}
        />
    }
});
