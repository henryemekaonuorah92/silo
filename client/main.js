const React = require('react');
const Router = require('./router');
const App = require('./app');
Router.on('route', function(name, params) {
    const route = { name: name, params: params };
    React.render(<App route={route} />, document.body)
});
Router.history.start({pushState: true});
