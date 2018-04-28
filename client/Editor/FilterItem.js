;
const React = require('react');
const {Form,FormGroup,FormControl,Glyphicon} = require('react-bootstrap');
const OperationTypeSelect = require('../Form/OperationTypeSelect');
const AsyncSimpleSelect = require('../Form/AsyncSimpleSelect');
const DateRange = require('../Form/DateRange');

/**
 * Holds the filter state
 */
module.exports = React.createClass({

    getDefaultProps: ()=>({
        onChange: ()=>null,
        onTypeChange: ()=>null,
        type: 'cancelledAt',
        value: null,
        editable: true
    }),

    handleChange: function(event){
        const target = event.target;
        const value = target.type === 'checkbox' ? target.checked : target.value;
        this.props.onChange(value);
    },

    handleInputChange: function(value) {
        this.props.onChange([value]);
    },

    render: function(){
        const {value, type, editable} = this.props;

        // Type decides which kind of form we display
        let valuePartial = null;
        let modePartial = "=";
        switch(type) {

            case "cancelledAt":
            case "doneAt":
            case "requestedAt":
                let initDates = {};
                if (value && value.startDate){initDates.startDate = value.startDate;}
                if (value && value.endDate){initDates.endDate = value.endDate;}
                valuePartial = <DateRange onChange={this.props.onChange}
                                          {...initDates} />
                break;
            case "cancelledBy":
            case "doneBy":
            case "requestedBy":
                valuePartial = <AsyncSimpleSelect onChange={this.props.onChange} onInputChange={this.handleInputChange}
                                             selected={value || []}
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
            case "location":
                valuePartial = <AsyncSimpleSelect onChange={this.props.onChange} onInputChange={this.handleInputChange}
                                                  selected={value || []}
                                                  url="/silo/inventory/location/search"
                                                  placeholder="Location..." />;
                break;
            case "sku":
                valuePartial = <AsyncSimpleSelect onChange={this.props.onChange} onInputChange={this.handleInputChange}
                                                  selected={value || []}
                                                  url="/silo/inventory/product/search"
                                                  placeholder="Product..." />;
                break;
            case "status":
                valuePartial = <FormControl name={type}
                                            componentClass="select"
                                            onChange={this.handleChange}
                                            selected={value}
                                            placeholder="Status...">
                        <option value="cancelled">cancelled</option>
                        <option value="done">done</option>
                        <option value="pending">pending</option>
                    </FormControl>
                break;

            case "type":
                valuePartial = <OperationTypeSelect placeholder="Type..."
                                                onChange={this.props.onChange}
                                                selected={value || []} />;
                break;
        }

        return editable ? <li className="list-group-item">
                <button type="button" className="close" aria-label="Close" onClick={this.props.onRemove}><span aria-hidden="true">&times;</span></button>

                <Form inline>
                    <Glyphicon glyph="filter"/>
                    &nbsp;
                    <FormGroup controlId="formControlsSelect">
                        <FormControl componentClass="select" placeholder="Filter..." value={type} onChange={(e)=>{
                            this.props.onTypeChange(e.target.value);
                        }}>
                            <option value="cancelledAt">cancelled at</option>
                            <option value="cancelledBy">cancelled by</option>
                            <option value="doneAt">done at</option>
                            <option value="doneBy">done by</option>
                            <option value="location">location</option>
                            <option value="requestedAt">requested at</option>
                            <option value="requestedBy">requested by</option>
                            <option value="sku">sku</option>
                            <option value="source">source</option>
                            <option value="status">status</option>
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

            :

            <li className="list-group-item">
                <Glyphicon glyph="lock"/> {type + ' = ' + value}
            </li>

    }
});
