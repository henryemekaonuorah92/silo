;
const React = require('react');
const {Glyphicon, OverlayTrigger, Popover, Button} = require('react-bootstrap');
const moment = require('moment');
const {defaultRanges,DateRange} = require('react-date-range');

module.exports = React.createClass({

    displayName: 'Form/DateRange',

    propTypes: {
        onChange: React.PropTypes.func,
        startDate: React.PropTypes.object,
        endDate: React.PropTypes.object
    },

    getDefaultProps: () => ({
        onChange: ()=>null,
        startDate: moment().subtract(29, 'days'),
        endDate: moment()
    }),

    handleChange: function(newRange){
        console.log("handleChange", newRange)
        this.props.onChange(newRange);
    },

    render: function(){
        //const rest = {};
        //Object.assign({}, this.props);
        //Object.getOwnPropertyNames(this.propTypes).map((name)=>{delete rest[name];});

        let start = this.props.startDate.format('YYYY-MM-DD');
        let end = this.props.endDate.format('YYYY-MM-DD');
        let label = start + ' - ' + end;
        if (start === end) {
            label = start;
        }

        const popover = (
            <Popover id="popover" title={this.props.title} style={{maxWidth: '90%'}}>
                <DateRange
                    startDate={this.props.startDate}
                    endDate={this.props.endDate}
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
                <Button className="selected-date-range-btn">
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
