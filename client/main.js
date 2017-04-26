const React = require('react');
const ReactDOM = require('react-dom');

const AmpersandRouter = require('ampersand-router');
const Cache = require('./Cache');

const App = React.createClass({
    getInitialState: () => ({
        currentRoute: 'home',
        cache: new Cache()
    }),

    routes: {
        '': 'home',
        'products(/:page)': 'products',
        'product/:slug': 'product',
        '*404': '404'
    },

    handlers: {
        home: require('./Operation')
    },

    componentDidMount: function(){
        this.router = new (AmpersandRouter.extend({
            routes: this.routes
        }));
        this.router.on('route', (name, params) => {
            this.setState({currentRoute: name});
            console.log("Routed");
        });

        this.router.history.start({pushState: true});
    },

    render: function(){
        const Handler = this.handlers[this.state.currentRoute];
        return <Handler route={this.state.currentRoute} cache={this.state.cache}/>
    }
});

ReactDOM.render(<App />, document.getElementById('ReactMount'));
