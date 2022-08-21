import React from "react";
import { Button, Modal } from 'react-bootstrap';

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
            >
                <Modal.Header closeButton>
                    <Modal.Title>
                        訊息
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    與會人與會議主旨為必填
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" onClick={this.closeModal}>
                        確定
                    </Button>
                </Modal.Footer>
            </Modal>
        )
    }
}

export default BasicModal