import React, { useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom';
import EstimatedProductionBlock from './estimated_production.js';
import PreproductionBlock from './preproduction.js';
import MeetingInformationNew from './meeting_information_new.js';
import MeetingSettingNew from './meeting_setting_new.js';
import CategoryBlock from './cetegory.js';
import RatioBlock from './ratio.js';
import UploadBlock from './upload_for_new';
import { Button, Container, Card, Row, Col, Form } from 'react-bootstrap';
import { ReactPainter } from 'react-painter';
import axios from 'axios';
import { Editor } from "react-draft-wysiwyg";
import "react-draft-wysiwyg/dist/react-draft-wysiwyg.css";
// SummerNote.ImportCode();
class AddDeliveryMeeting extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      datas: {
        labels: [],
        data: []
      },
      meet_setting: {

      },
      modal_data: {
        show: false,
        title: "",
        body: "",
        footer: "",
        size: "lg"
      },
      today: ""
    }
    this.chertChild = React.createRef();
    this.meetingChild = React.createRef();
    this.modalChild = React.createRef();
    this.changeDatas = this.changeDatas.bind(this);
    this.changeMeetSearch = this.changeMeetSearch.bind(this);
    this.setModal = this.setModal.bind(this);
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
    // const items = { ...this.state.search_data };
    // items['meet_date'] = today;
    this.setState({
      today: today
    });
  }
  componentDidUpdate() {

  }
  changeDatas(data) {
    this.setState({
      datas: {
        labels: data.labels,
        data: data.data
      }
    })
    this.chertChild.current.changeLabelAndData(data.labels, data.data)
  }
  changeMeetSearch(data) {
    let datas = data.data
    this.setState({
      meet_setting: datas
    })
  }
  setModal(data) {
    this.setState({
      modal_data: {
        title: data.title,
        body: data.body,
        footer: data.footer,
        size: data.size
      }
    })
    this.modalChild.current.change(data.data)
    this.modalChild.current.openModal()
  }
  render() {
    return (
      <Container fluid className="d-grid gap-2 my-3">
        <Card>
          <Card.Body className='shadow'>
            <Row className='mb-3'>
              <Col md="auto">
                <h4>????????????</h4>
              </Col>
            </Row>
            <Row className='mb-3'>
              <Col md="auto">
                <label>?????????: nknu</label>
              </Col>
              <Col md="auto">
                <label>??????: {this.state.today}</label>
              </Col>

            </Row>
            <Row className='d-flex justify-content-end'>
              <Col md="auto">
                <Button variant="primary">????????????</Button>{' '}
              </Col>
            </Row>
            <Row className='mb-3'>
              <Col md="auto">
                <Button variant="secondary">??????</Button>{' '}
              </Col>
              <Col md="auto">
                <Button variant="secondary">??????</Button>{' '}
              </Col>
              <Col md="auto">
                <Button variant="secondary">??????</Button>{' '}
              </Col>
              <Col md="auto">
                <Button variant="outline-secondary">+</Button>{' '}
              </Col>
              <Col md="auto">
                <Button variant="outline-primary">????????????</Button>{' '}
              </Col>
            </Row>
            <Row>
              <Col md="3">
                <UploadBlock />
              </Col>
              <Col md="3">
                <Form.Control as="textarea" disabled placeholder="??????" />
              </Col>
              <Col md="3">
                <Form.Control as="textarea" disabled placeholder="??????" />
              </Col>
              <Col md="3">
                <Form.Control as="textarea" placeholder="?????????" />
              </Col>
            </Row>

          </Card.Body>
        </Card>
        <Row>
          <Col md="6">
            <Card>
              <Card.Header>
                ?????????
              </Card.Header>
              <Card.Body className='shadow'>
                <ReactPainter
                  width={400}
                  height={200}
                  onSave={(blob) => console.log(blob)}
                  render={({ triggerSave, canvas, setColor }) => (
                    <>
                      <Row className='mb-3'>
                        <Col>
                          <div class="border" >{canvas}</div>
                        </Col>
                      </Row>
                      <Row className='mb-3'>
                        <Col md="auto">
                          <Button onClick={triggerSave} variant="primary">????????????</Button>{' '}
                        </Col>
                        <Col md="auto">
                          <Form.Control
                            type="color"
                            id="exampleColorInput"
                            title="Choose your color"
                            onChange={(e) => setColor(e.target.value)}
                          />
                        </Col>
                      </Row>

                    </>

                  )}
                />
              </Card.Body>
            </Card>
          </Col>
          <Col md="6">
            <Card>
              <Card.Header>
                ?????????
              </Card.Header>
              <Card.Body className='shadow'>
                <ReactPainter
                  width={400}
                  height={200}
                  onSave={(blob) => console.log(blob)}
                  render={({ triggerSave, canvas, setColor }) => (
                    <>
                      <Row className='mb-3'>
                        <Col >
                          <div class="border" >{canvas}</div>
                        </Col>
                      </Row>
                      <Row className='mb-3'>
                        <Col md="auto">
                          <Button onClick={triggerSave} variant="primary">????????????</Button>{' '}
                        </Col>
                        <Col md="auto">
                          <Form.Control
                            type="color"
                            id="exampleColorInput"
                            title="Choose your color"
                            onChange={(e) => setColor(e.target.value)}
                          />
                        </Col>
                      </Row>

                    </>

                  )}
                />
              </Card.Body>
            </Card>
          </Col>
        </Row>
        <Row>
          <Col md="12">
            <Card className='shadow'>
              <Card.Header>
                <label>????????????</label>
              </Card.Header>
              <Card.Body>
                <Editor
                  // editorState={editorState}
                  toolbarClassName="toolbarClassName"
                  wrapperClassName="wrapperClassName"
                  editorClassName="editorClassName"
                // onEditorStateChange={this.onEditorStateChange}
                />
              </Card.Body>
            </Card>
          </Col>
        </Row>


      </Container>
    );
  }
}

export default AddDeliveryMeeting;