const React = require('react');
const Link = require('react-router-dom').Link;
const {Cell} = require('fixed-data-table');
const Emoji = require('react-emoji');

// @todo create a factory that returns a view/csv export based on type
module.exports = ({rowIndex, data, ...props}) => {
    return <Cell {...props}>
        {data.getObjectAt(rowIndex).contexts.map(function(context, key){
            return <span key={key}>
                <Link to={"/operation-set/"+context.id}>{context.id}</Link>

                {typeof(context.value) === "object" && "description" in context.value &&
                    <span>
                        &nbsp;(Comment {Emoji.emojify(context.value.description)})
                    </span>
                }

                {typeof(context.value) === "object" && "magentoOrderId" in context.value &&
                    <span>
                        &nbsp;(Order {context.value.magentoOrderId}
                        {"incrementId" in context.value && ' #'+context.value.incrementId}
                        )
                    </span>
                }

                &nbsp;
            </span>;
        })}
    </Cell>
};
