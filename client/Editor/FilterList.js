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
    handleChange: function(filterKey, property, value){
        let w = this.state.filters;
        w[filterKey][property] = value;
        this.setState({filters: w});

        console.log(w);
    },
    handleAdd: function(){
        let w = this.state.filters;
        w.push({_type:"source"});
        this.setState({filters: w});
    },
    handleApply: function(){
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
                            onRemove={this.handleRemove.bind(this, i)}
                            definition={filter} />
            ))}
            <li className="list-group-item">
                <Button bsStyle="default" bsSize="xs" onClick={this.handleAdd}>Add filter</Button>&nbsp;
                <Button bsStyle="success" bsSize="xs" onClick={this.handleApply}>Apply</Button>
            </li>
        </ul>
    }
});
