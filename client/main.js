const React = require('react');
const ReactDOM = require('react-dom');

const AmpersandRouter = require('ampersand-router');


const App = React.createClass({
    getInitialState: () => ({
        currentRoute: 'home'
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
        this.router = new AmpersandRouter.extend(this.routes3 );
        this.router.history.start({pushState: true});
    },

    render: function() {
        const Handler = Router.getHandler(this.props.route.name);
        return <Handler route={this.props.route} />
    }
});

Router.on('route', function(name, params) {
    const route = { name: name, params: params };
    ReactDOM.render(<App route={route} />, document.getElementById('ReactMount'))
});

