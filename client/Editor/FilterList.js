;
const React = require('react');
const {Button,Form, FormGroup,FormControl, ControlLabel,Glyphicon} = require('react-bootstrap');
const FilterItem = require('./FilterItem');

/**
 * Holds the filter state
 */
module.exports = React.createClass({

    getInitialState: ()=>({
        filters: []
    }),

    handleRemove: function(filterKey){
        let w = this.state.filters;
        w.splice(filterKey, 1);
        this.setState({filters: w});
    },
    handleChange: function(filterKey, value){
        let w = this.state.filters;
        w[filterKey]['value'] = value;
        this.setState({filters: w});
    },
    handleTypeChange: function(filterKey, type){
        let w = this.state.filters;
        w[filterKey]['type'] = type;
        this.setState({filters: w});
    },
    handleAdd: function(){
        let w = this.state.filters;
        w.push({type:"cancelledAt", value:null});
        this.setState({filters: w});
    },
    handleApply: function(){
        console.log("Filtering", this.state.filters);
        this.props.onFilterChange(this.state.filters);
    },

    render: function(){
        const filters = this.state.filters;
        return <ul className="list-group">
            {filters.length === 0 &&
            <li className="list-group-item">No filter</li>
                }
            {filters.length > 0 && filters.map((filter, i)=>(
                <FilterItem key={i}
                            onChange={this.handleChange.bind(this, i)}
                            onTypeChange={this.handleTypeChange.bind(this, i)}
                            onRemove={this.handleRemove.bind(this, i)}
                            type={filter.type}
                            value={filter.value} />
            ))}
            <li className="list-group-item">
                <Button bsStyle="default" bsSize="xs" onClick={this.handleAdd}>Add filter</Button>&nbsp;
                <Button bsStyle="success" bsSize="xs" onClick={this.handleApply}>Apply</Button>
            </li>
        </ul>
    }
});
