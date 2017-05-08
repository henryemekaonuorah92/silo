;
const React = require('react');
const {Typeahead} = require('react-bootstrap-typeahead');
const request = require('superagent');

module.exports = React.createClass({

    componentDidMount: function(){
        request.get('/silo/inventory/operation/types')
            .end((err, resp) => {
                this.setState({options: resp.body})
            });
    },

    getInitialState: ()=>({
        options: []
    }),

    render: function(){
        return <Typeahead
            options={this.state.options}
            {...this.props}
        />
    }
});
