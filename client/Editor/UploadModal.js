;
import React from 'react';
import {Button, Modal} from 'react-bootstrap';

module.exports = React.createClass({
    getInitialState() {
        return { showModal: false };
    },
    /*
    propTypes: {
        modal: React.propTypes.required
    },
    */
    close() {
        this.setState({ showModal: false });
    },

    open() {
        this.setState({ showModal: true });
    },

    render() {
        return (
            <a onClick={this.open}>
                CSV Upload

                <Modal show={this.state.showModal} onHide={this.close}>
                    <Modal.Header closeButton>
                        <Modal.Title>Modal heading</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button onClick={this.close}>Close</Button>
                    </Modal.Footer>
                </Modal>
            </a>
        );
    }
});
