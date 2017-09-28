;
const React = require('react');
const {Modal} = require('react-bootstrap');
const fetch = require('../Api').fetch;

module.exports = React.createClass({
    getInitialState: function(){
        return {
            wip: false,
            value: '',
            candidates: []
        };
    },
    handleChange: function(event) {
        this.setState({value: event.target.value});
    },
    handleSubmit: function(event){
        event.preventDefault();
        this.setState({wip:true});
        fetch("/silo/search", {body:JSON.stringify({query:this.state.value}), method: "POST"}).then((data)=>{
            this.setState({wip:false});
            if (data.candidates.length === 0) {
                alert("Nothing found");
            } else if (data.candidates.length === 1) {
                this.openCandidate(data.candidates.pop());
            } else {
                this.setState({candidates:data.candidates});
            }
        })

        ;
    },
    openCandidate: function(candidate){
        this.setState({
            candidates: [],
            value: ""
        });
        console.log("OPEN "+candidate.url)
    },
    close: function(){
        this.setState({candidates: []});
    },
    render: function(){
        return (
            <form className="navbar-form navbar-left" onSubmit={this.handleSubmit}>
                <div className="form-group">
                    <input type="text" value={this.state.value} onChange={this.handleChange} className="form-control" placeholder="Search" />
                </div>
                <button className="btn btn-default btn-info" type="submit"
                        value="Submit" disabled={this.state.wip}
                onClick={this.handleSubmit}>Find</button>

                <Modal show={this.state.candidates.length > 0} onHide={this.close}>
                    <Modal.Body>
                        {this.state.candidates.map((cd, key) => (
                        <button type="button"
                                className="btn btn-default btn-lg btn-block"
                                key={key}
                                onClick={this.openCandidate.bind(this, cd)}
                        >
                            {cd.anchor}
                        </button>
                        ))}
                    </Modal.Body>
                </Modal>

            </form>
        );
    }
});