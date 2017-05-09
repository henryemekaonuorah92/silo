;
const React = require('react');

const DateRange = require('../Form/DateRange');
const moment = require('moment');

module.exports = React.createClass({
    getInitialState: ()=>({
        startDate: moment(),
        endDate: moment()
    }),

    render: function(){
        const st = this.state;
        return (
            <DateRange startDate={st.startDate} endDate={st.endDate} onChange={(start, end)=>{
                this.setState({startDate:start, endDate:end});
            }}/>
        );
    }
});
