;
const React = require('react');
const {Glyphicon, OverlayTrigger, Popover, Button} = require('react-bootstrap');
const moment = require('moment');
const {defaultRanges,DateRange} = require('react-date-range');

module.exports = React.createClass({

    displayName: 'Form/DateRange',

    propTypes: {
        onChange: React.PropTypes.func,
        startDate: React.PropTypes.any,
        endDate: React.PropTypes.any
    },

    getDefaultProps: () => ({
        onChange: ()=>null,
        startDate: moment().subtract(29, 'days'),
        endDate: moment()
    }),

    handleChange: function(newRange){
        this.props.onChange(newRange);
    },

    render: function(){
        //const rest = {};
        //Object.assign({}, this.props);
        //Object.getOwnPropertyNames(this.propTypes).map((name)=>{delete rest[name];});

        let {startDate, endDate, title} = this.props;
        if (typeof(startDate) === "string") {
            startDate = moment(startDate);
        }
        if (typeof(endDate) === "string") {
            endDate = moment(endDate);
        }

        let start = startDate.format('YYYY-MM-DD');
        let end = endDate.format('YYYY-MM-DD');
        let label = start + ' - ' + end;
        if (start === end) {
            label = start;
        }

        const popover = (
            <Popover id="popover" title={title} style={{maxWidth: '90%'}}>
                <DateRange
                    startDate={startDate}
                    endDate={endDate}
                    linkedCalendars={ true }
                    ranges={ defaultRanges }
                    onChange={this.handleChange}
                    theme={{
                        Calendar : { width: 200 },
                        PredefinedRanges : { marginLeft: 10, marginTop: 10 }
                    }}

                />
            </Popover>
        );

        return (
            <OverlayTrigger trigger="click" placement="bottom" overlay={popover}>
                <Button className="selected-date-range-btn btn-sm">
                    <div className="pull-left"><Glyphicon glyph="calendar" /></div>
                    <div className="pull-right">
                        <span>{label}</span>
                        <span className="caret" />
                    </div>
                </Button>
            </OverlayTrigger>
        );
    }
});
