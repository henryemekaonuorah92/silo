const React = require('react');
const ReactDOM = require('react-dom');

const AmpersandRouter = require('ampersand-router');
const Cache = require('./Cache');

const Navbar = require('./Hud/Navbar');
const Sidebar = require('./Hud/Sidebar');

const App = React.createClass({
    getInitialState: () => ({
        currentRoute: 'home',
        cache: new Cache()
    }),

    routes: {
        '': 'home',
        'operations': 'operations',
        'products(/:page)': 'products',
        'product/:slug': 'product',
        '*404': '404'
    },

    handlers: {
        home: require('./View/Home'),
        operations: require('./Operations')
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

    onNavigate: function(route){
        this.router.navigate(route, {trigger: true});
    },

    render: function(){
        const Handler = this.handlers[this.state.currentRoute];
        return <div>
            <Navbar />
            <div className="container-fluid">
                <div className="row">
                    <Sidebar onNavigate={this.onNavigate} route={this.state.currentRoute} />
                    <div className="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                        <Handler route={this.state.currentRoute}
                                 cache={this.state.cache} />
                    </div>
                </div>
            </div>
        </div>
    }
});

ReactDOM.render(<App />, document.getElementById('ReactMount'));
