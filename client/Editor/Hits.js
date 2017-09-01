import React, {Component} from 'react';
import PropTypes from 'prop-types';
import {Glyphicon} from 'react-bootstrap';

class Hits extends Component {
    render() {
        let {data,error,children} = this.props;
        let partial = null;
        switch(true) {
            case !!error:
                partial = <h3 className="text-center"><Glyphicon glyph="warning-sign"/> Error while loading</h3>;
                break;
            case !data:
                partial = <h3 className="text-center"><Glyphicon glyph="refresh" className="spinning"/> Loading</h3>;
                break;
            case data.length === 0:
                partial =  <h3 className="text-center"><Glyphicon glyph="remove" /> Nothing found</h3>;
                break;
        }
        return partial ?
            <div className="panel-body">{partial}</div> :
            children
            ;
    }
}

const withHits = (WrappedComponent) => (({children, data, error,...props})=> (
    <Hits data={data} error={error}><WrappedComponent data={data} {...props}>{children}</
        pedComponent></Hits>
));

Hits.propTypes = {
    data: PropTypes.any,
    error: PropTypes.any,
    columns: PropTypes.any
};
Hits.defaultProps = {};

const wrapHits = (WrappedComponent) => (({children, data, error,...props})=>(
    <Hits data={data} error={error}><WrappedComponent data={data} {...props}>{children}</WrappedComponent></Hits>
));

export default Hits;
export {withHits};
export {wrapHits as wrapHits};
