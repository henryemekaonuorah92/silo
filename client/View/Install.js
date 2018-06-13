const React = require('react');
const {Button} = require('react-bootstrap');

module.exports = React.createClass({
    getInitialState: ()=>({
        step: 1,
    }),
    render: function(){
        return (
            <div className="container-fluid">
                <h1>Welcome to Silo</h1>
                <p>Looks like everything is working. Now let's get to know you, connect to your database and manage some inventory !</p>
                <Button>Let's get started</Button>
            </div>
        );
    }
});
