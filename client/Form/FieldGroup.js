;
const React = require('react');
const {FormGroup, ControlLabel, FormControl, HelpBlock} = require('react-bootstrap');

module.exports = React.createClass({
    render: function() {
        const rest = Object.assign({}, this.props);
        delete rest.id;
        delete rest.label;
        delete rest.help;
        return (
            <FormGroup controlId={this.props.id}>
                <ControlLabel>{this.props.label}</ControlLabel>
                {this.props.children || <FormControl {...rest} />}
                {this.props.help && <HelpBlock>{this.props.help}</HelpBlock>}
            </FormGroup>
        );
    }
});
