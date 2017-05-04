const React = require('react');
const {Navbar} = require('react-bootstrap');

module.exports = React.createClass({

    render: function(){
        return (
            <Navbar fixedTop fluid>
                <Navbar.Header>
                    <Navbar.Brand>
                        <a href="#">Silo</a>
                    </Navbar.Brand>
                </Navbar.Header>
            </Navbar>
        );
    }
});
