;
const React = require('react');
const FieldGroup = require('./FieldGroup');

/**
 * Description field that limit entered text to maxLength
 * @todo Emoji support and preview
 */
module.exports = React.createClass({

    getDefaultProps: ()=>({
        onChange: ()=>null,
        maxLength: 140
    }),

    handleChange(e) {
        if (e.target.value.length > this.maxLength) {
            return;
        }
        this.props.onChange(e);
    },

    render() {
        const rest = Object.assign({}, this.props);
        if ("componentClass" in this.props) {
            throw "Cannot define componentClass on LimitedTextarea";
        }
        if ("help" in this.props) {
            throw "Cannot define help on LimitedTextarea";
        }
        delete rest.onChange;

        return (
                <FieldGroup
                    onChange={this.handleChange}
                    componentClass="textarea"
                    help={this.props.value.length + "/"+this.props.maxLength+" chars"}
                    {...rest}
                />
        );
    }
});
