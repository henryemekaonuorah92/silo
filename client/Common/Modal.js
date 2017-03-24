;
const React = require('react');
const {Modal} = require('react-bootstrap');

/**
 * Wrapper around react-bootstrap Modal
 *
 * <Modal show={} onHide={} title="Hello">
 *     Body content here...
 * </Modal>
 */
module.exports = React.createClass({
    propTypes: {
        //title: React.propTypes.string.required
    },

    render() {
        let rest = Object.assign({}, this.props);
        delete rest.title;
        return (
                <Modal {...rest}>
                    <Modal.Header closeButton>
                        <Modal.Title>{this.props.title}</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        {this.props.children}
                    </Modal.Body>
                </Modal>
        );
    }
});
