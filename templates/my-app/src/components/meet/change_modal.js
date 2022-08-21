import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Modal from 'react-bootstrap/Modal'
import Button from 'react-bootstrap/Button';
import { Title } from 'chart.js';
import Row from 'react-bootstrap/esm/Row';

class ChangeModal extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      show: false,
      title: "",
      body: "",
      footer: "",
      size: "md"
    };
    this.hideModal = this.hideModal.bind(this);
    this.openModal = this.openModal.bind(this);
    this.change = this.change.bind(this);

  }
  componentDidMount() {
    // this.change(data)
  }
  hideModal() {
    this.setState({
      show: false
    })
  }
  openModal() {
    this.setState({
      show: true
    })
  }
  change(data) {
    this.setState({
      title: data.title,
      body: data.body,
      footer: data.footer,
      size: data.size,
    })
  }
  render() {
    return (
      <Modal
        onHide={this.hideModal}
        show={this.state.show}
        size={this.state.size}
        dialogClassName="modal-90w"
        aria-labelledby="example-custom-modal-styling-title"
        backdrop="static"
      >
        <Modal.Header closeButton >
          <Modal.Title id="example-custom-modal-styling-title">
            {this.state.title}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {this.state.body}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={this.hideModal}>
            確定
          </Button>
        </Modal.Footer>
      </Modal>
    );
  }
}

export default ChangeModal;