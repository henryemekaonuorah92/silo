import React from 'react'
import {Navbar, Nav, NavItem, NavDropdown, MenuItem} from 'react-bootstrap'
import {Link} from 'react-router-dom'
import {LinkContainer} from 'react-router-bootstrap'

module.exports = React.createClass({

    menu: function(route, title, key) {
        return <LinkContainer to={route}><MenuItem eventKey={key}>{title}</MenuItem></LinkContainer>
    },

    render: function(){
        return (
            <Navbar fixedTop fluid>
                <Navbar.Header>
                    <Navbar.Brand>
                        <Link to="/">Silo</Link>
                    </Navbar.Brand>
                </Navbar.Header>
                <Nav>
                    <NavDropdown eventKey={1} title="Inventory" id="main-nav-dropdown">
                        {this.menu("/operations", "Operations", 1.1)}
                    </NavDropdown>
                    <NavItem eventKey={2} href="#">Work</NavItem>
                </Nav>
                <Nav pullRight>
                    <NavDropdown eventKey={3} title="An" id="user-nav-dropdown">
                        <MenuItem eventKey={3.1}>Administration</MenuItem>
                        <MenuItem eventKey={3.2}>Help</MenuItem>
                        <MenuItem eventKey={3.3}>About Silo</MenuItem>
                        <MenuItem divider />
                        <MenuItem eventKey={3.4}>Log out</MenuItem>
                    </NavDropdown>
                </Nav>

            </Navbar>
        );
    }
});
