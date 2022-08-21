import React, { useState, useEffect, useCallback, useMemo } from 'react';
// import  from 'react-bootstrap/Modal'
// import Button from 'react-bootstrap/Button';
import { Modal, FloatingLabel, Image, FormControl, InputGroup, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';

// import Row from 'react-bootstrap/esm/Row';
import Search from "../Search.js"
import axios from 'axios';
import { param } from 'jquery';

class AddTrackingModal extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      show: false,
      title: "新增追蹤事項",
      body: "",
      footer: "",
      size: "md",
      search_data: {
        name: "",
        create_date: "",
        content: "",
        person_in_charge_id: ""
      },
      search_input: [],
      option: {
        module: [],
        user: [],
      }
    };
    this.hideModal = this.hideModal.bind(this);
    this.openModal = this.openModal.bind(this);
    this.change = this.change.bind(this);
    this.add_track = this.add_track.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.getAllUserModule = this.getAllUserModule.bind(this);
    this.getAllModule = this.getAllModule.bind(this);
    // this.getToday = this.getToday.bind(this);
    this.changeUserModule = this.changeUserModule.bind(this);
    this.changeUser = this.changeUser.bind(this);

  }
  componentDidMount() {
    this.getToday()
    this.getAllUserModule()
    this.getAllModule()
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
        { 'label': '標題', 'id': 'name', 'type': 'input', },
        { 'label': '說明', 'id': 'content', 'type': 'input', },

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
  add_track() {
    let data = this.state.search_data
    axios.post('/CRM/complaint/tracking',
      data
    )
      .then((response) => {
        this.hideModal()
        this.props.get_tracking()
      })
      .catch((error) => console.log(error))
  }
  getAllUserModule(module_id) {
    axios.get('/CRM/all_user', {

      params: { module_id: module_id }
    }

    ,
    )
      .then((response) => {
        let temp = this.state.option
        temp["user"] = response.data
        this.setState({
          option: temp
        })
      })
      .catch((error) => console.log(error))
  }
  getAllModule() {
    axios.get('/CRM/all_module'
    )
      .then((response) => {
        let temp = this.state.option
        temp["module"] = response.data
        this.setState({
          option: temp
        })
      })
      .catch((error) => console.log(error))
  }
  changeUserModule(e) {
    let module_id = e.target.value
    this.getAllUserModule(module_id)
  }
  changeUser(e) {
    let user_id = e.target.value
    let temp = this.state.search_data
    temp["person_in_charge_id"] = user_id
    this.setState({
      search_data: temp
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
          <Search resetData={this.handleChange} name={this.state.search_input}></Search>
          <Row>

            <Col sm={12} className="my-1">
              <FloatingLabel controlId="responsible_unit" label="權責單位">
                <Form.Select aria-label="權責單位" onChange={this.changeUserModule}>
                  <option>請選擇...</option>
                  {this.state.option.module.map((value, index) => (
                    <option value={value["id"]}>{value["name"]}</option>
                  ))}
                </Form.Select>
              </FloatingLabel>
            </Col>
            <Col sm={12} className="my-1">
              <FloatingLabel controlId="follower" label="追蹤人">
                <Form.Select aria-label="追蹤人" onChange={this.changeUser}>
                  <option>請選擇...</option>
                  {this.state.option.user.map((value, index) => (
                    <option value={value["id"]}>{value["name"]}</option>
                  ))}
                </Form.Select>
              </FloatingLabel>
            </Col>
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Row>
            <Col md={"auto"}>
              <Button variant="primary" onClick={this.add_track}>
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

export default AddTrackingModal;