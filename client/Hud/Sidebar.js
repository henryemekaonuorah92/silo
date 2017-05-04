const React = require('react');



module.exports = React.createClass({

    activeLi: function(fragment, route, title) {
        const active = route === this.props.route;
        return <li className={active && "active"}>
            <a onClick={()=>{this.props.onNavigate(fragment);}}>
                {title}
                {active && <span className="sr-only">(current)</span>}
            </a>
        </li>;
    },

    render: function(){
        console.log(this.props.route);
        return (
            <div className="col-sm-3 col-md-2 sidebar">
                <ul className="nav nav-sidebar">
                    {this.activeLi("", "home", "Overview")}
                    {this.activeLi("operations", "operations", "Operations")}
                </ul>
                <ul className="nav nav-sidebar">
                    <li><a href="">Nav item</a></li>
                </ul>
            </div>
        );
    }
});
