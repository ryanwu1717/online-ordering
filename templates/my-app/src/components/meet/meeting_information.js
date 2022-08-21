import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Button from 'react-bootstrap/Button';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import Form from 'react-bootstrap/Form';
import ToggleButton from 'react-bootstrap/ToggleButton'
import ToggleButtonGroup from 'react-bootstrap/ToggleButtonGroup'
import "bootstrap/dist/js/bootstrap.bundle.js";
import "bootstrap/dist/css/bootstrap.css";
import axios from 'axios';
import MeetingSetting from './meeting_setting.js';
import Card from 'react-bootstrap/Card';
import ChangeModal from './change_modal'
class MeetingInformation extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      name: "",//text,
      meet_date: null,//timestamp,
      recorder_user_id: 7,//integer,
      cop001: '',//單別 text,
      cop002: '',//單號 text, 
      cop003: '',//序號 text,
      cop_class: '',
      create: false,
      meeting_setting: []
    };
    this.changeMeetName = this.changeMeetName.bind(this);
    this.changeCop001 = this.changeCop001.bind(this);
    this.changeCop002 = this.changeCop002.bind(this);
    this.changeCop003 = this.changeCop003.bind(this);
    this.changeCopClass = this.changeCopClass.bind(this);
    this.CreateMeet = this.CreateMeet.bind(this);
    this.handleClick = this.handleClick.bind(this);
    this.ChildElement = React.createRef();
    this.confirmCreat = this.confirmCreat.bind(this);


    // this.save = this.save.bind(this);
  }
  componentDidMount() {
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
      meet_date: today,
      recorder_user_id: 7
    });
    const recorder_user_name = ""
    const recorder_user_id = 0
    axios.get(`/CRM/user`)
      .then((response) => {
        // console.log(response.data)
      });
  }

  changeMeetName(event) {
    this.setState({
      name: event.target.value,
    });
  }
  //單別
  changeCop001(event) {
    this.setState({
      cop001: event.target.value,
    });
  }
  //單號
  changeCop002(event) {
    this.setState({
      cop002: event.target.value,
    });
  }
  //序號
  changeCop003(event) {
    this.setState({
      cop003: event.target.value,
    });
  }
  changeCopClass(event) {
    this.setState({
      cop_class: event,
    });
  }
  CreateMeet() {
    if (this.state.cop_class == "報價單") {
      let meet_date = this.state.meet_date.replaceAll("-", "/")
      let datas = new Object();
      let fk = new Object();
      datas["name"] = this.state.name
      datas["meet_date"] = meet_date
      datas["meet_type_id"] = 2
      datas["recorder_user_id"] = this.state.recorder_user_id
      fk["coptb_tb001"] = this.state.cop001
      fk["coptb_tb002"] = this.state.cop002
      fk["coptb_tb003"] = this.state.cop003
      datas["fk"] = fk
      let data = new Object();
      data[0] = datas;
      console.log(data);
      axios.post(`/CRM/delivery_meet`,
        data,
      )
        .then((response) => {
          console.log(response.data)
          if (response.data[0].meet_id != null) {
            this.setState({
              create: true,
              meeting_setting: response.data[0]
            })
          }
        });
    }
    else if (this.state.cop_class == "訂單") {
      let meet_date = this.state.meet_date.replaceAll("-", "/")
      let datas = new Object();
      let fk = new Object();
      datas["name"] = this.state.name
      datas["meet_date"] = meet_date
      datas["meet_type_id"] = 2
      datas["recorder_user_id"] = this.state.recorder_user_id
      fk["coptd_td001"] = this.state.cop001
      fk["coptd_td002"] = this.state.cop002
      fk["coptd_td003"] = this.state.cop003
      datas["fk"] = fk
      let data = new Object();
      data[0] = datas;
      console.log(data);
      axios.post(`/CRM/delivery_meet`,
        data
      )
        .then((response) => {
          if (response.data[0].meet_id != null) {
            this.setState({
              create: true,
              meeting_setting: response.data[0]
            })
          }
        });
    }
  }
  confirmCreat(){
    let notNull = true
    Object.keys(this.state).map((key) => {
      if( key!="create" && key !="meeting_setting"){
        console(this.state[key])
        if(this.state[key]=="" && this.state[key]==null){
          notNull = false
        }
      }
    })
    if(notNull){
      this.CreateMeet()
    }
  }
  handleClick() {
    const childelement = this.ChildElement.current;
    let datas = new Object();
    Object.keys(childelement.state).map((key) => {
      console.log(key + " " + childelement.state[key])
      this.setState[key] = childelement.state[key];
      datas[key] = childelement.state[key];
    })
    console.log(datas)
    // axios.patch(`/CRM/complaint/meet`,
    //   datas,
    // )
    // .then((response) => {
    //   console.log(response.data)

    // });

  }
  render() {
    return (
      <>
        <Col md="12">
          <Card className='shadow'>
            <Card.Header>
              <label> </label>
            </Card.Header>
            <Card.Body>
              <Form>
                <Row className="justify-content-end">
                  <Col className="d-flex justify-content-end">
                    <Button variant="primary" onClick={this.CreateMeet}>確定</Button>{' '}
                    <Button variant="info" onClick={this.handleClick} >保存</Button>{' '}
                  </Col>
                </Row>
                
                <Form.Group as={Row} className='row-cols-2 row-cols-lg-5'>
                  <Col>
                    <Form.Label column >交期會議 {this.state.meeting_date}</Form.Label>
                  </Col>
                  <Col>
                    <Form.Label column >紀錄人 {this.state.recorder}</Form.Label>
                  </Col>
                </Form.Group>
                <Form.Group as={Row} >
                  <Col md="auto">
                    <Form.Label>會議名稱:</Form.Label>
                    <Form.Control type="text" />
                  </Col>
                  <Col md="auto">
                    <Form.Label>單別:</Form.Label>
                    <input id="dateStart" type="text" onChange={this.changeCop001} className="form-control" />
                  </Col>
                  <Col md="auto">
                    <Form.Label>單號:</Form.Label>
                    <input id="dateStart" type="text" onChange={this.changeCop002} className="form-control" />
                  </Col>
                  <Col md="auto">
                    <Form.Label>序號:</Form.Label>
                    <input id="dateStart" type="text" onChange={this.changeCop003} className="form-control" />
                  </Col>

                  <Col md="auto">
                    <ToggleButtonGroup type="radio" name="options" defaultValue={this.state.cop_class} onChange={this.changeCopClass}>
                      <ToggleButton variant="outline-success" id="tbg-radio-1" value={'報價單'}>
                        報價單
                      </ToggleButton>
                      <ToggleButton variant="outline-success" id="tbg-radio-2" value={'訂單'}>
                        訂單
                      </ToggleButton>
                    </ToggleButtonGroup>
                  </Col>
                </Form.Group>
              </Form>
            </Card.Body>
          </Card>
        </Col>
        {this.state.create ? <MeetingSetting ref={this.ChildElement} test1={this.state.meeting_setting} /> : null}
        <ChangeModal/>
      </>
    );
  }
}
export default MeetingInformation;
