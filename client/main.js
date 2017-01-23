const React = require('react');
const ReactDOM = require('react-dom');
const Router = require('./router');
const App = require('./app');
Router.on('route', function(name, params) {
    const route = { name: name, params: params };
    ReactDOM.render(<App route={route} />, document.getElementById('ReactMount'))
});
Router.history.start({pushState: true});
