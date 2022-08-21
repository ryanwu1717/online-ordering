import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Modal, FloatingLabel, FormControl, InputGroup, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';
import axios from 'axios';
import 'antd/dist/antd.css';
import { Image } from 'antd';

class Complaint extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      meet_id: this.props.meet_id,
      complaint: [

      ]
    }
    this.get_complaint = this.get_complaint.bind(this);

  }
  componentDidMount() {
    this.get_complaint();
  }
  componentDidUpdate(prevProps, prevState, snapshot) {
        if (this.props.meet_id !== prevProps.meet_id ) {
            this.get_complaint()
        }
    }
  get_complaint() {
    axios.get('/CRM/complaint/complaint/today', {
      params: { meet_id: this.props.meet_id },
  })
      .then((response) => {
        if (response.data.data.length > 0 && response.data.data !== null) {
          this.setState({
            complaint: response.data.data
          })
        }

      })
      .catch((error) => console.log(error))
  }
  /*  */
  render() {
    let complaint = this.state.complaint
    return (
      <Row>
        <div style={{ display: 'flex', overflowX: 'scroll', overflowY: 'scroll', whiteSpace: 'nowrap' }}>
          {(complaint).map((value, index) => (
            // console.log(value)
            <Col md={6} className={"mb-1 mx-1"}>
              <Card >
                <Card.Header>
                  <label>{value["subject"] || ""}</label>
                </Card.Header>
                <Card.Body>
                  <Row>
                    <Col sm={6} className={"mb-1"}>
                      <InputGroup className="mb-3">
                        <InputGroup.Text id="customer_code">客戶代號</InputGroup.Text>
                        <FormControl

                          aria-describedby="customer_code"
                          value={value["customer_code"] || ""}
                          disabled={true}
                        />
                      </InputGroup>
                    </Col>
                    <Col sm={6} className={"mb-1"}>
                      <InputGroup className="mb-3">
                        <InputGroup.Text id="img_id">客戶圖號</InputGroup.Text>
                        <FormControl

                          aria-describedby="img_id"
                          value={value["img_id"] || ""}
                          disabled={true}
                        />
                      </InputGroup>

                    </Col>
                  </Row>
                  <Row>
                    <Col md={12} className={"mb-1"}>

                      <FloatingLabel label="客訴內容">
                        <Form.Control disabled={true}
                          value={value["content"] || ""}
                          as="textarea"
                        />
                      </FloatingLabel>
                    </Col>
                  </Row>
                  <Row >
                    <Col md={12} className={"mb-1"}>
                      <Image.PreviewGroup>
                        {value["file_id"] === null ? null :
                          <div style={{ display: 'flex', overflowX: 'scroll', whiteSpace: 'nowrap' }}>
                            {value["file_id"].map((file_value, index) => (
                              <>
                                <Image
                                  thumbnail={true}
                                  img_id={file_value}
                                  src={`${axios.defaults.baseURL}/CRM/complaint/complaint/attach_file/${file_value}`}
                                  alt={file_value}
                                  className="mx-2 my-2"
                                  style={{ width: 100 }}
                                />
                              </>
                            ))
                            }
                          </div>
                        }
                      </Image.PreviewGroup>
                    </Col>
                  </Row>
                  <Row>

                  </Row>
                </Card.Body>
              </Card>
            </Col>
          ))}
        </div>
      </Row>
    );
  }
}

export default Complaint;