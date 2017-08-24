const React = require('react');
const {Cell} = require('fixed-data-table');
const Datetime = require('../../Common/Datetime');

module.exports = ({rowIndex, data, ...props}) => {
    let status = data.getObjectAt(rowIndex).status;
    return (
        <Cell>
            {status.isDone && <span className="text-success">
                                        <span className="glyphicon glyphicon-ok" /> Done <Datetime>{status.doneAt}</Datetime>&nbsp;{status.doneBy}
                                    </span>}
            {status.isCancelled && <span className="text-danger">
                                        <span className="glyphicon glyphicon-remove" /> Cancelled <Datetime>{status.cancelledAt}</Datetime>&nbsp;{status.cancelledBy}
                                    </span>}
            {status.isPending && <span>
                                        <span className="glyphicon glyphicon-time" /> Pending <Datetime>{status.requestedAt}</Datetime>&nbsp;{status.requestedBy}
                                    </span>}
        </Cell>
    )
};
