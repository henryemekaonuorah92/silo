;
const React = require('react');
const {Navbar,Nav} = require('react-bootstrap');

module.exports = {
    Navbar: props => <Navbar>
        <Navbar.Header>
            <Navbar.Brand>
                {props.title}
            </Navbar.Brand>
        </Navbar.Header>
        <Nav>
            {props.children}
        </Nav>
    </Navbar>,
    NavText: Navbar.Text,
    NavForm: Navbar.Form
};
