const React = require('react');
const ReactDOM = require('react-dom');

const AmpersandRouter = require('ampersand-router');
const Cache = require('./Cache');

const Navbar = require('./Hud/Navbar');

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
            console.log("route", name);
            this.setState({currentRoute: name});
        });

        this.router.history.start({pushState: true});
    },

    render: function(){
        const Handler = this.handlers[this.state.currentRoute];
        return <div>
            <Navbar />
            <Handler route={this.state.currentRoute} cache={this.state.cache}/>
        </div>
    }
});

ReactDOM.render(<App />, document.getElementById('ReactMount'));
