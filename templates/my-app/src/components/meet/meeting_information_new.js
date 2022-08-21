import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Card from 'react-bootstrap/Card';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import Form from 'react-bootstrap/Form';
import Button from 'react-bootstrap/Button';
import SERACH from '../Search';
import ToggleButton from 'react-bootstrap/ToggleButton'
import ToggleButtonGroup from 'react-bootstrap/ToggleButtonGroup'
import axios from 'axios';


class MeetingInformationNew extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      meet_id: null,
      recorder_user_name: "",
      search_data: {
        name: "",
        meet_date: "",
        recorder_user_id: "",
        meet_type_id: 2,
        cop001: "",
        cop002: "",
        cop003: "",
        cop_class: "",
      },
      search_input: [
        { 'label': '會議名稱:', 'id': 'name', 'type': 'input', 'name': 'search_input', 'isinvalid': false },
        { 'label': '單別:', 'id': 'cop001', 'type': 'input', 'name': 'search_input', 'isinvalid': false },
        { 'label': '單號:', 'id': 'cop002', 'type': 'input', 'name': 'search_input', 'isinvalid': false },
        { 'label': '序號:', 'id': 'cop003', 'type': 'input', 'name': 'search_input', 'isinvalid': false },
      ]
    }
    this.handleChange = this.handleChange.bind(this);
    this.confirmCreate = this.confirmCreate.bind(this);
    this.createMeet = this.createMeet.bind(this);
    this.changeCopClass = this.changeCopClass.bind(this);
    this.getMeetSetting = this.getMeetSetting.bind(this);
    this.getRecorder = this.getRecorder.bind(this);
  }

  componentDidMount() {
    this.getRecorder()
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
    const items = { ...this.state.search_data };
    items['meet_date'] = today;
    this.setState({
      search_data: items
    });

  }
  handleChange(data) {
    const temp = this.state.search_data;
    // const request = this.state.datatables.request;
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

  createMeet() {
    // let meet_date = this.state.meet_date.replaceAll("-", "/")
    let datas = new Object();
    let fk = new Object();
    let data = new Object();
    datas["name"] = this.state.search_data.name
    datas["meet_date"] = this.state.search_data.meet_date
    datas["meet_type_id"] = this.state.search_data.meet_type_id
    datas["recorder_user_id"] = this.state.search_data.recorder_user_id
    if (this.state.search_data.cop_class == "報價單") {
      fk["coptb_tb001"] = this.state.search_data.cop001
      fk["coptb_tb002"] = this.state.search_data.cop002
      fk["coptb_tb003"] = this.state.search_data.cop003
    }
    else if (this.state.search_data.cop_class == "訂單") {
      fk["coptd_td001"] = this.state.search_data.cop001
      fk["coptd_td002"] = this.state.search_data.cop002
      fk["coptd_td003"] = this.state.search_data.cop003
    }
    datas["fk"] = fk
    data[0] = datas;
    if (this.confirmCreate(datas)) {
      axios.post(`/CRM/delivery_meet`,
        data,
      )
        .then((response) => {
          if (response.data[0].meet_id != null) {
            this.setState({
              meet_id: response.data[0].meet_id
            })
            this.getMeetSetting(response.data[0])
            this.props.setModal({
              data: {
                title: "訊息",
                body: "已新增一筆交期會議",
              }
            });
          }
        });
    }
    else {
      this.props.setModal({
        data: {
          title: "訊息",
          body: "請輸入完整資料",
        }
      });
      let temp = this.state.search_input
      let i = 0
      for (var item of document.getElementsByName("search_input")) {
        if (item.value == "" || item.value == null) {
          temp[i]["isinvalid"] = true
        }
        else {
          temp[i]["isinvalid"] = false
        }
        i++
      }
    }

  }
  changeCopClass(event) {
    const items = { ...this.state.search_data };
    items['cop_class'] = event;
    this.setState({
      search_data: items
    });

  }
  confirmCreate(data) {
    let notNull = true
    Object.keys(data).map((key) => {
      if (data[key] == "" || data[key] == null) {
        notNull = false
      }
      else if (key == "fk") {
        if (Object.keys(data.fk).length <= 0) {
          notNull = false
        }
      }
    })
    return notNull
  }
  getMeetSetting(datas) {
    let data = new Object();
    Object.keys(datas).map((key) => {
      let item = datas[key]
      if (key == "content") {
        Object.keys(item[0]).map((item_key) => {
          data[item_key] = item[0][item_key]
        })
      }
      else if (key == "meet_id") {
        data[key] = datas[key]
      }
    })
    this.props.resetData({
      data: data
    });
  }
  getRecorder() {
    axios.get(`/CRM/user`)
      .then((response) => {
        let user = response.data[0]
        const items = { ...this.state.search_data };
        items['recorder_user_id'] = user.id;
        this.setState({
          recorder_user_name: user.name,
          search_data: items
        });
      });
  }
  render() {
    return (
      <Card className='shadow'>
        <Card.Header>
          <label> </label>
        </Card.Header>
        <Card.Body>
          <Row className='d-flex justify-content-end'>
            <Col sm="auto" >
              {!this.state.meet_id ? <Button variant="outline-primary" onClick={this.createMeet}>新增</Button> : null}
            </Col>
            <Col sm="auto">
              <Button variant="outline-warning" type="submit" onClick={this.handleClick} >保存</Button>{' '}
            </Col>
          </Row >
          <Form.Group as={Row} className='row-cols-2 row-cols-lg-5'>
            <Col>
              <Form.Label column >交期會議 {this.state.search_data.meet_date}</Form.Label>
            </Col>
            <Col>
              <Form.Label column >紀錄人: {this.state.recorder_user_name}</Form.Label>
            </Col>
          </Form.Group>
          <Form.Group onSubmit={this.handleSubmit} as={Row} controlId="formGridState" className='gy-3 row-cols-2 row-cols-lg-5'>
            <SERACH id="search_inputs" resetData={this.handleChange} name={this.state.search_input}></SERACH>
            <Col md="auto">
              <ToggleButtonGroup type="radio" name="options" defaultValue={this.state.search_data.cop_class} onChange={this.changeCopClass}>
                <ToggleButton variant="outline-success" id="tbg-radio-1" value={'報價單'}>
                  報價單
                </ToggleButton>
                <ToggleButton variant="outline-success" id="tbg-radio-2" value={'訂單'}>
                  訂單
                </ToggleButton>
              </ToggleButtonGroup>
            </Col>
          </Form.Group>
        </Card.Body>
      </Card>
    );
  }
}
export default MeetingInformationNew;
