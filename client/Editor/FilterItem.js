;
const React = require('react');
const {Button,Form, FormGroup,FormControl, ControlLabel,Glyphicon} = require('react-bootstrap');
const OperationTypeSelect = require('../Form/OperationTypeSelect');
const AsyncSimpleSelect = require('../Form/AsyncSimpleSelect');
const Switch = require('react-bootstrap-switch').default;

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
        const type = this.props.definition._type;

        // Type decides which kind of form we display
        let partial = null;
        switch(type) {
            case "source":
            case "target":
                partial = <AsyncSimpleSelect onChange={this.props.onChange.bind(this, type)}
                                             selected={this.props.definition[type] || []}
                                             url="/silo/inventory/location/search"
                                             placeholder="Location..." />;
                break;
            case "requestedAt":
            case "cancelledAt":
            case "doneAt":
                partial = "DateRange form";
                break;
            case "requestedBy":
            case "cancelledBy":
            case "doneBy":
                partial = <AsyncSimpleSelect onChange={this.props.onChange.bind(this, type)}
                                             selected={this.props.definition[type] || []}
                                             url="/silo/inventory/user/search"
                                             placeholder="User..." />;
                break;
            case "isRollbacked":
                partial = <Switch onText="Yes" offText="No" onColor="success"
                                  value={this.props.definition[type] || true}
                                  onChange={this.props.onChange.bind(this, type)}/>;
                break;
            case "isType":
                partial = <OperationTypeSelect onChange={this.props.onChange.bind(this, type)}
                                               selected={this.props.definition[type] || []} />;
                break;
        }

        return <li className="list-group-item">
                <button type="button" className="close" aria-label="Close" onClick={this.props.onRemove}><span aria-hidden="true">&times;</span></button>
                <Form inline>
                    <Glyphicon glyph="filter"/>
                    &nbsp;
                    <FormGroup controlId="formControlsSelect">
                        <FormControl componentClass="select" placeholder="Filter..." value={type} name="_type" onChange={this.handleChange}>
                            <option value="source">source</option>
                            <option value="target">target</option>
                            <option value="requestedAt">requested at</option>
                            <option value="requestedBy">requested by</option>
                            <option value="doneAt">done at</option>
                            <option value="doneBy">done by</option>
                            <option value="cancelledAt">cancelled at</option>
                            <option value="cancelledBy">cancelled by</option>
                            <option value="isRollbacked">is rollbacked</option>
                            <option value="isType">is type</option>
                        </FormControl>
                    </FormGroup>
                    &nbsp;=&nbsp;
                    <FormGroup controlId="formInlineName">
                        {partial}
                    </FormGroup>
                </Form>
            </li>

    }
});
