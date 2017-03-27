;
const React = require('react');
const {Table, Column, Cell} = require('fixed-data-table');
const Measure = require('react-measure');
const DataStoreWrapper = require('./DataStoreWrapper');
const TextCell = require('./TextCell');
const Datetime = require('../Common/Datetime');
const Link = require('./../Common/Link');
const Emoji = require('react-emoji');
/**
 * Edit a set of Operations
 * @type {*}
 */
class OperationEditor extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            dimensions: {
                width: -1,
                height: -1,
            },
            data: {},
            filteredDataList: null
        };

        this._onFilterChange = this._onFilterChange.bind(this);
    }

    _onFilterChange(e) {
        if (this.prop)
        if (!e.target.value) {
            this.setState({
                filteredDataList: null,
            });
        }

        let filterBy = e.target.value.toLowerCase();
        let size = this.props.operations.getSize();
        let filteredIndexes = [];
        for (let index = 0; index < size; index++) {
            let {product, quantity} = this.props.operations.getObjectAt(index);
            if (product.toLowerCase().indexOf(filterBy) !== -1) {
                filteredIndexes.push(index);
            }
        }

        this.setState({
            filteredDataList: new DataStoreWrapper(filteredIndexes, this.props.operations),
        });
    }

    render(){
        let operations = this.state.filteredDataList || this.props.operations;
        /*
         <li><input
         onChange={this._onFilterChange}
         placeholder="Filter by SKU"
         /></li>
         */
        return (
            <div className="panel panel-default">
                <div className="panel-heading nav navbar-default">
                    <div>
                        <div>
                            <ul className="nav navbar-nav">
                                <li><h4>OperationEditor</h4></li>
                                <li><span>{operations.getSize()} operations</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
                {operations.getSize() > 0 ?
                    <Measure onMeasure={(dimensions)=>{this.setState({dimensions});}}>
                        <div className="panel-body panel-body_noPadding">
                            {this.state.dimensions.width != -1 && (
                                <Table
                                    width={this.state.dimensions.width} // Bootstrap 15px padding on row
                                    height={Math.min(operations.getSize() + 4, 12) * 36}
                                    headerHeight={36}
                                    offsetHeight={150}
                                    rowsCount={operations.getSize()}
                                    rowHeight={36}>
                                    <Column
                                        width={80}
                                        header="#"
                                        cell={({rowIndex}) => (
                                            <Cell>
                                                <Link route="operation" code={operations.getObjectAt(rowIndex)['id']} />
                                            </Cell>
                                        )}
                                    />
                                    <Column
                                        width={100}
                                        header="Type"
                                        cell={<TextCell data={operations} col="type" />}
                                    />
                                    <Column
                                        width={225}
                                        header="Content"
                                        cell={({rowIndex}) => {
                                            const data = operations.getObjectAt(rowIndex);
                                            return <Cell>
                                                {data.source ? <Link route="location" code={data.source} /> : <span className="label label-success">CREATE</span>}
                                                &nbsp;&rarr;&nbsp;
                                                {data.target ? <Link route="location" code={data.target} /> : <span className="label label-danger">DELETE</span>}
                                                &nbsp;({data.location ? <Link route="location" code={data.location} /> : 'skus'})
                                                </Cell>;
                                        }}
                                    />
                                    <Column
                                        width={300}
                                        header="Status"
                                        cell={({rowIndex}) => {
                                            let status = operations.getObjectAt(rowIndex)['status'];
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
                                        )}}
                                    />
                                    <Column
                                        width={300}
                                        header="Context"
                                        cell={({rowIndex}) => (
                                            <Cell>
                                                {operations.getObjectAt(rowIndex).contexts.map(function(context, key){
                                                    return <span key={key}>
                                                        <Link route="operationSet" code={context.id} />
                                                        {typeof(context.value) === "object" && "description" in context.value &&
                                                            <span>
                                                                &nbsp;({Emoji.emojify(context.value.description)})
                                                            </span>
                                                        }&nbsp;
                                                    </span>
                                                        ;
                                                })}
                                            </Cell>
                                        )}
                                    />
                                </Table>
                            )}
                        </div>
                    </Measure> : <div className="panel-body">Fetching or no data...</div>
                }
            </div>
        );
    }
}

module.exports = OperationEditor;

OperationEditor.propTypes = {
    // batches: React.PropTypes.isRequired // new DataStore
};

