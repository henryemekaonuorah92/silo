;
const React = require('react');
const FieldGroup = require('./FieldGroup');
const Emoji = require('react-emoji');

/**
 * Description field that limit entered text to maxLength
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
                    help={
                        <div>
                            <div className="pull-right"><a href="https://www.webpagefx.com/tools/emoji-cheat-sheet/" target="_blank"><span className="glyphicon glyphicon-info-sign"/> emoji</a></div>
                            {this.props.value.length + "/"+this.props.maxLength+" chars"}
                            { this.props.value &&
                                <div>Preview: {Emoji.emojify(this.props.value)}</div>
                            }
                        </div>
                    }
                    {...rest}
                />
        );
    }
});
