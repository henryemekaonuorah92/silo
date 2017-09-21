const React = require('react');
const {Navbar, Nav, NavItem, NavDropdown, MenuItem,FormGroup, FormControl, Button} = require('react-bootstrap');

module.exports = React.createClass({

    activeLi: function(fragment, route, title, key) {
        const active = route === this.props.route;
        // @todo active ?
        return <MenuItem eventKey={key} onClick={()=>{this.props.onNavigate(fragment);}}>{title}</MenuItem>
    },

    render: function(){
        return (
            <Navbar fixedTop fluid>
                <Navbar.Header>
                    <Navbar.Brand>
                        <a href="#">Silo</a>
                    </Navbar.Brand>
                </Navbar.Header>
                <Nav>
                    <NavDropdown eventKey={1} title="Inventory" id="main-nav-dropdown">
                        {this.activeLi("locations", "locations", "Locations", 1.1)}
                        {this.activeLi("operations", "operations", "Operations", 1.2)}
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
