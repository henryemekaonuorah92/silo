const React = require('react');
const Link = require('../../Factory').Link;
const {Cell} = require('fixed-data-table');
const Emoji = require('react-emoji');

module.exports = ({rowIndex, data, ...props}) => {
    return <Cell {...props}>
        {data.getObjectAt(rowIndex).contexts.map(function(context, key){
            return <span key={key}>
                <Link route="operationSet" code={context.id} />
                {typeof(context.value) === "object" && "description" in context.value &&
                <span>
                    &nbsp;({Emoji.emojify(context.value.description)})
                </span>
                }&nbsp;
            </span>;
        })}
    </Cell>
};
