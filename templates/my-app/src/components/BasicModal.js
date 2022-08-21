import React from "react";
import { Modal } from 'react-bootstrap';

class BasicModal extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            show: this.props.show,
        }
        this.openModal = this.openModal.bind(this);
        this.closeModal = this.closeModal.bind(this);
    }
    openModal = () => {
        this.setState({
            show: true,
        })
    }

    closeModal() {
        this.setState({
            show: false,
        })
    }


    render() {
        return (
            <Modal
                show={this.state.show}
                size={this.props.size !== undefined ? this.props.size : "lg"}
                scrollable={true}
                onHide={this.closeModal}
                style={{ height: this.props.height !== "undefined" ? this.props.height : "280px" }}
            >
                <Modal.Header closeButton={this.props.close_button !== undefined ? this.props.close_button : true}>
                    <Modal.Title>
                        {this.props.modal_title}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <h3>{this.props.modal_body}</h3>
                </Modal.Body>
                <Modal.Footer>
                    {this.props.modal_footer}
                </Modal.Footer>
            </Modal>
        )
    }
}

export default BasicModal