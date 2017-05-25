;
const React = require('react');

module.exports = {
    View: null,

    Editor: (props)=><div>
        <button className={"btn "+ (props.value ? "btn-primary":"btn-success")} onClick={()=>props.onChange(!!!props.value)}>Toggle</button>
    </div>
};
