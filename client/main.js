import Home from "./View/Home";

const React = require('react');
const ReactDOM = require('react-dom');
const Navbar = require('./Hud/Navbar');
const Factory = require('./Factory');

require('../less/base.less');

import {
    BrowserRouter as Router,
    Route,
    Link,
    Switch
} from 'react-router-dom'

import Routes from './Routes'

const App = React.createClass({

    componentDidMount: function(){
        Factory.setLink(({route, code, children})=>
            <Link to={'/'+route+'/'+code} style={{cursor: 'pointer'}}>{children || code}</Link>
        );
    },

    render: function(){
        return <Router>
            <div>
                <Navbar />
                <div className="container-fluid">
                    <div className="row">
                        <div className="col-sm-12 main">
                            <Switch>
                                <Route exact path="/" component={Home} />
                                {Routes}
                                <Route path="*" render={(props)=>(<div>Not found</div>)} />
                            </Switch>
                        </div>
                    </div>
                </div>
            </div>
        </Router>
    }
});

ReactDOM.render(<App />, document.getElementById('ReactMount'));
