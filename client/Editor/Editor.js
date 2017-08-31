;
const React = require('react');
const {Navbar,Nav,NavDropdown} = require('react-bootstrap');
const inPanel = require('../Common/inPanel');
const autosizeFromData = require('../Common/autosizeFromData');
const wrapHits = require('./Hits').wrapHits;
const {Table} = require('fixed-data-table');

/**
 * An Editor is a specialized Panel that receives data and displays it in an understandable way
 */
const Editor = React.createClass({
    propTypes: {
        menu: React.PropTypes.any,
        title: React.PropTypes.string
    },

    getDefaultProps: ()=>({
        menu: null,
        title: "Editor",
    }),

    /*
    <NavForm pullLeft>
        <FormGroup>
            <input
                onChange={this._onFilterChange}
                placeholder="Filter by SKU"
                ref="filter"
            />
        </FormGroup>
    </NavForm>
     */

    render: function(){
        let {children,menu,title} = this.props;
        return (
            <div className="panel panel-default">
                <Navbar>
                    <Navbar.Header>
                        <Navbar.Brand>
                            {title}
                        </Navbar.Brand>
                    </Navbar.Header>
                    <Nav>
                        {menu && menu.length > 0 &&
                        <NavDropdown title="Action" id="basic-nav-dropdown">
                            {menu}
                        </NavDropdown>
                        }
                    </Nav>
                </Navbar>
                {children}
            </div>
        );
    }
});

module.exports = {
    Editor: Editor,
    PanelTable: wrapHits(autosizeFromData(inPanel(Table)))
};

