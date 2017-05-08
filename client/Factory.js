const React = require('react');

let defs = {
    Link: (props)=>(<span>{props.code}</span>)
};

module.exports = {
    Link: (props)=>(defs.Link(props)),
    setLink: (def)=>{defs.Link = def;}
};
