import React, { useState, useEffect, useCallback, useMemo } from 'react';
// import  from 'react-bootstrap/Modal'
// import Button from 'react-bootstrap/Button';
import { Modal, FloatingLabel, Image, FormControl, InputGroup, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';

// import Row from 'react-bootstrap/esm/Row';
import Search from "../Search.js"
import axios from 'axios';

class AddElseDiscussModal extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      show: false,
      title: "新增討論事項",
      body: "",
      footer: "",
      size: "md",
      search_data: {
        discuss_name: "",
        discuss_content: "",
      },
      search_input: [],
    };
    this.hideModal = this.hideModal.bind(this);
    this.openModal = this.openModal.bind(this);
    this.change = this.change.bind(this);
    this.add_discuss = this.add_discuss.bind(this);
    this.handleChange = this.handleChange.bind(this);
    // this.getFollower = this.getFollower.bind(this);
    // this.getToday = this.getToday.bind(this);
    
  }
  componentDidMount() {
    // this.change(data)
    this.getToday()
  }
  componentDidUpdate() {
    // this.getToday()
  }
  getToday() {
    const current = new Date();
    let dd = current.getDate();
    let mm = current.getMonth() + 1;
    let yyyy = current.getFullYear();
    if (dd < 10) {
      dd = "0" + dd
    }
    if (mm < 10) {
      mm = "0" + mm
    }
    let today = `${yyyy}/${mm}/${dd}`;

    this.setState({
      search_input: [
        { 'label': '建立日期', 'id': 'create_date', 'type': 'input', "value": today, 'disabled': true },
        { 'label': '標題', 'id': 'discuss_name', 'type': 'input', },
        { 'label': '說明', 'id': 'discuss_content', 'type': 'input', },

      ]
    });


  }
  openModal() {
    this.setState({
      show: true
    })
  }
  handleChange(data) {
    let temp = this.state.search_data;
    Object.keys(temp).map((key, value) => {
      if (key === data.id) {
        temp[key] = data.value.trim() === '' ? null : data.value.trim();
        return;
      }
    })
    this.setState({
      search_data: temp
    })
  }
  hideModal() {
    this.setState({
      show: false
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
  add_discuss() {
    let data = this.state.search_data
    // console.log(data)
    axios.post('/CRM/discuss',
      data
    )
      .then((response) => {
        this.hideModal()
        this.props.get_discuss()
      })
      .catch((error) => console.log(error))
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
          <Search resetData={this.handleChange} name={this.state.search_input}></Search>
        </Modal.Body>
        <Modal.Footer>
          <Row>
            <Col md={"auto"}>
              <Button variant="primary" onClick={this.add_discuss}>
                確定新增
              </Button>
            </Col >
            <Col md={"auto"}>
              <Button variant="secondary" onClick={this.hideModal}>
                取消
              </Button>
            </Col>
          </Row>

        </Modal.Footer>
      </Modal>
    );
  }
}

export default  AddElseDiscussModal;