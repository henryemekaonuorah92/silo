const React = require('react');
const ReactDOM = require('react-dom');

const AmpersandRouter = require('ampersand-router');
const Cache = require('./Cache');

const Navbar = require('./Hud/Navbar');
const Sidebar = require('./Hud/Sidebar');
const Factory = require('./Factory');

const App = React.createClass({
    getInitialState: () => ({
        currentRoute: 'home',
        currentParams: null,
        cache: new Cache()
    }),

    routes: {
        '': 'sink',
        'operations': 'operations',
        'operation/:id': 'operation',
        'location/:id': 'location',
        'product/:id': 'product',
        '*404': 'notfound'
    },

    handlers: {
        home: require('./View/Home'),
        operations: require('./Operations'),
        operation: require('./Operation'),
        location: require('./Location'),
        product: require('./Product'),
        sink: require('./View/Sink'),
        notfound: (props)=>(<div>Not found</div>)
    },

    componentDidMount: function(){
        this.router = new (AmpersandRouter.extend({
            routes: this.routes
        }));
        this.router.on('route', (name, params) => {
            console.log("route", name, params);
            this.setState({
                currentRoute: name,
                currentParams: params
            });
        });
        Factory.setLink(this.createLink);

        this.router.history.start({pushState: false});
    },

    onNavigate: function(route){
        this.router.navigate(route, {trigger: true});
    },

    createLink: function(props){
        let frag = '/'+props.route+'/'+props.code;
        return (
            <a onClick={(e)=>{
                e.stopPropagation();
                this.onNavigate(frag);
            }} style={{cursor: 'pointer'}}
               href={'/#'+frag}>
                {props.children || props.code}
            </a>
        );
    },

    render: function(){
        const Handler = this.handlers[this.state.currentRoute];
        const id = this.state.currentParams ? this.state.currentParams[0] : null
        return <div>
            <Navbar />
            <div className="container-fluid">
                <div className="row">
                    <Sidebar onNavigate={this.onNavigate} route={this.state.currentRoute} />
                    <div className="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                        <Handler siloBasePath="/silo"
                                 route={this.state.currentRoute}
                                 cache={this.state.cache}
                                 id={id}/>
                    </div>
                </div>
            </div>
        </div>
    }
});

ReactDOM.render(<App />, document.getElementById('ReactMount'));
