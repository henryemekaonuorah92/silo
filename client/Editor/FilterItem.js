;
const React = require('react');
const {Form,FormGroup,FormControl,Glyphicon} = require('react-bootstrap');
const OperationTypeSelect = require('../Form/OperationTypeSelect');
const AsyncSimpleSelect = require('../Form/AsyncSimpleSelect');
// const Switch = require('react-bootstrap-switch').default;
const DateRange = require('../Form/DateRange');

/**
 * Holds the filter state
 */
module.exports = React.createClass({

    handleChange: function(event){
        const target = event.target;
        const value = target.type === 'checkbox' ? target.checked : target.value;
        const name = target.name;
        this.props.onChange(name, value);
    },

    render: function(){
        const def = (filter)=>(this.props.definition[filter] || null)
        const type = this.props.definition._type;

        // Type decides which kind of form we display
        let valuePartial = null;
        let modePartial = "=";
        switch(type) {

            case "cancelledAt":
            case "doneAt":
            case "requestedAt":
                let initDates = {};
                if (def(type) && def(type).startDate) {
                    initDates.startDate = def(type).startDate;
                }
                if (def(type) && def(type).endDate) {
                    initDates.endDate = this.props.endDate;
                }
                console.log("initDates", initDates);
                valuePartial = <DateRange onChange={this.props.onChange.bind(this, type)}
                                          {...initDates} />
                break;
            case "cancelledBy":
            case "doneBy":
            case "requestedBy":
                valuePartial = <AsyncSimpleSelect onChange={this.props.onChange.bind(this, type)}
                                             selected={def(type) || []}
                                             url="/silo/inventory/user/search"
                                             placeholder="User..." />;
                break;
            // case "isRollbacked":
            //     valuePartial = <Switch onText="Yes" offText="No" onColor="success"
            //                       value={this.props.definition[type] || true}
            //                       onChange={this.props.onChange.bind(this, type)}/>;
            //     break;
            case "source":
            case "target":
                valuePartial = <AsyncSimpleSelect onChange={this.props.onChange.bind(this, type)}
                                                  selected={def(type) || []}
                                                  url="/silo/inventory/location/search"
                                                  placeholder="Location..." />;
                break;
            case "status":
                valuePartial = <FormControl name={type}
                                            componentClass="select" placeholder="select"
                                            onChange={this.handleChange}
                                            selected={def(type)}>
                        <option value="cancelled">cancelled</option>
                        <option value="done">done</option>
                        <option value="pending">pending</option>
                    </FormControl>
                break;
            case "type":
                valuePartial = <OperationTypeSelect onChange={this.props.onChange.bind(this, type)}
                                               selected={def(type) || []} />;
                break;
        }
        // <option value="status">status</option>
        // <option value="cancelledAt">cancelled at</option>
        // <option value="doneAt">done at</option>
        // <option value="requestedAt">requested at</option>
        return <li className="list-group-item">
                <button type="button" className="close" aria-label="Close" onClick={this.props.onRemove}><span aria-hidden="true">&times;</span></button>
                <Form inline>
                    <Glyphicon glyph="filter"/>
                    &nbsp;
                    <FormGroup controlId="formControlsSelect">
                        <FormControl componentClass="select" placeholder="Filter..." value={type} name="_type" onChange={this.handleChange}>
                            <option value="cancelledBy">cancelled by</option>
                            <option value="doneBy">done by</option>
                            <option value="requestedBy">requested by</option>
                            <option value="source">source</option>
                            <option value="target">target</option>
                            <option value="type">type</option>
                        </FormControl>
                    </FormGroup>
                    &nbsp;
                    {modePartial}
                    &nbsp;
                    <FormGroup controlId="formInlineName">
                        {valuePartial}
                    </FormGroup>
                </Form>
            </li>

    }
});
