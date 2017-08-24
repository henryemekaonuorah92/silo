const React = require('react');

module.exports = (WrappedComponent)=>{
    return React.createClass({

        displayName: "connectWithPromise",

        getInitialState: ()=>({
            data: null,
            error: null
        }),

        componentDidMount: function () {
            this.props.promise.then(data=>this.setState({data, error: null}), error=>this.setState({data: null, error}));
        },

        componentWillReceiveProps: function (nextProps) {
            this.setState(this.getInitialState());
            nextProps.promise.then(data=>this.setState({data, error: null}), error=>this.setState({data: null, error}));
        },

        render: function(){
            const {promise, ...rest} = this.props;
            return <WrappedComponent {...rest} {...this.state}>{rest.children}</WrappedComponent>
        },
    });
};
