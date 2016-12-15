;
const React = require('react');
const FormGroup = require('react-bootstrap').FormGroup;
const ControlLabel = require('react-bootstrap').ControlLabel;
const FormControl = require('react-bootstrap').FormControl;
const HelpBlock = require('react-bootstrap').HelpBlock;

module.exports = React.createClass({
    handleChange: function(e) {
        this.props.onChange(this.props.path, e.target.value);
    },
    render: function() {
        const rest = Object.assign({}, this.props);
        delete rest.path;
        delete rest.configuration;
        return (
            <FormGroup controlId={this.props.id}>
                <ControlLabel>{this.props.label}</ControlLabel>
                <FormControl {...rest} onChange={this.handleChange} value={this.props.configuration[this.props.path] || ""} />
                {this.props.help && <HelpBlock>{this.props.help}</HelpBlock>}
            </FormGroup>
        );
    }
});