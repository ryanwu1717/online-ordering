import React from "react";
import Row from "react-bootstrap/Row";
import Col from "react-bootstrap/Col";
import Modal from "react-bootstrap/Modal";
import Button from "react-bootstrap/Button";
import InputGroup from "react-bootstrap/InputGroup";
import Form from "react-bootstrap/Form";
import Upload from "../Upload";
import Select from "react-select";
import axios from "axios";
import moment from "moment";
import UploadTable from "./UploadTable.js";
import { BrowserRouter as Router, Route, Routes  } from "react-router-dom";


// const customStyles = {
//     option: (provided, state) => ({
//       ...provided,
//       borderBottom: '1px dotted pink',
//       color: state.isSelected ? 'white' : 'blue',
//       padding: 20,
//     }),
//     control: base => ({
//         ...base,
//         height: 40,
//         minHeight: 40
//     }),
//     singleValue: (provided, state) => {
//       const opacity = state.isDisabled ? 0.5 : 1;
//       const transition = 'opacity 300ms';

//       return { ...provided, opacity, transition };
//     }
// }

class UploadModal extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      show: false,
      video_type_id: "",
      video_type_name: "",
      video_name: "",
      user_name: "",
      fileURL: "",
      file_id: 0,
      UploadFile: true,
      type: [],
      input_datas: [],
    };
    this.handleChange = this.handleChange.bind(this);
    this.handleInputChange = this.handleInputChange.bind(this);
    this.addRow = this.addRow.bind();
    this.ref = React.createRef();
  }

  handleClose = () => {
    this.setState({ show: false });
    this.setState({ selectValue: "" });
  };
  handleShow = () => {
    this.setState({ video_name: "" });
    this.setState({ show: true });
    this.getUserName();
    this.getVideoType();
  };
  handleChange(event) {
    this.setState({ video_type_id: event.target.value });
    this.setState({ video_type_name: event.target.name });
  }
  uploadVideo = () => {
    const data = {
      video_type: this.state.video_type_id,
      video_name: this.state.video_name,
      file_id: this.state.file_id,
    };
    axios.post(`/develop/video/video`, { data }).then((res) => {
      this.setState({ show: false });
      this.setState({ video_type_id: "" });
    });
  };
  getUserName = () => {
    axios
      .get(`/develop/video/upload/user_name`)
      .then((res) => {
        this.setState({ user_name: res.data[0].name });
      })
      .catch((e) => {
        console.error(e);
      });
  };
  handleInputChange(event) {
    this.setState({ video_name: event.target.value });
  }
  handleCallback = (response) => {
    axios
      .get(`/develop/video/preview_video_or_file/${response.file_id}`)
      .then((res) => {
        this.setState({
          file_id: response.file_id,
          fileURL: res.config.url,
          UploadFile: !this.state.UploadFile,
          video_name: response.clientFileName,
        });
      });
  };
  exitModal = () => {
    this.setState({ UploadFile: true });
    this.props.datatableCallBack();
  };
  getVideoType = () => {
    axios.get(`/develop/videos/video_type`).then((res) => {
      this.setState({ type: res.data });
    });
  };
  addRow = () => {
    this.state.input_datas.push({
      video_name: this.state.video_name,
      video_type_id: this.state.video_type_id,
      video_type_name: this.state.video_type_name,
      user_name: this.state.user_name,
      upload_time: moment().format("Y-M-D"),
    });
    // this.ref.current.test(this.state.input_datas);
  };
  render() {
    let moment = require("moment");
    const { UploadFile } = this.state;
    let optionItems = this.state.type.map((type) => (
      <option value={type.id}>{type.name}</option>
    ));
    return (
      <>
        <Row>
          {/* <Button variant="primary" onClick={this.handleShow} className="mb-2">
            上傳
          </Button> */}

          <Modal
            show={this.state.show}
            onHide={this.handleClose}
            onExited={this.exitModal}
            size="lg"
            aria-labelledby="contained-modal-title-vcenter"
            centered
          >
            <Modal.Body>
              <Row
                style={{
                  display: "flex",
                  justifyContent: "center",
                  alignItems: "center",
                }}
              >
                <Col md={6}>
                  <Row>
                    <InputGroup className="mb-3">
                      <InputGroup.Text id="inputGroup-sizing-default">
                        影片類別
                      </InputGroup.Text>
                      <Col md={6}>
                        <Form.Select
                          aria-label="Default select example"
                          onChange={this.handleChange}
                        >
                          <option disabled selected>
                            請選擇
                          </option>
                          {optionItems}
                        </Form.Select>
                      </Col>
                    </InputGroup>
                    <InputGroup className="mb-3">
                      <InputGroup.Text id="inputGroup-sizing-default">
                        影片名稱
                      </InputGroup.Text>
                      <Col md={6}>
                        <Form.Control
                          type="text"
                          placeholder={this.state.video_name}
                          onChange={this.handleInputChange}
                        />
                      </Col>
                    </InputGroup>
                    <InputGroup className="mb-3">
                      <InputGroup.Text id="inputGroup-sizing-default" disabled>
                        上傳者
                      </InputGroup.Text>
                      <Col md={6}>
                        <Form.Control
                          type="text"
                          placeholder={this.state.user_name}
                          aria-label="Disabled input example"
                          disabled
                          readOnly
                        />
                      </Col>
                    </InputGroup>
                    <InputGroup className="mb-3">
                      <InputGroup.Text id="inputGroup-sizing-default">
                        上傳日期
                      </InputGroup.Text>
                      <Col>
                        <Form.Control
                          type="text"
                          placeholder={moment().format("Y-M-D")}
                          aria-label="Disabled input example"
                          disabled
                          readOnly
                        />
                      </Col>
                    </InputGroup>
                  </Row>
                </Col>
                <Col md={6}>
                  <Row>
                    {UploadFile ? (
                      <Upload
                        allowType={[
                          "video/mp4",
                          "AVI/mp4",
                          "FLV/mp4",
                          "WMV/mp4",
                          "MOV/mp4",
                          "MP4/mp4",
                        ]}
                        request_data={""}
                        API_location={"/develop/video/preview_video_or_file"}
                        parentCallback={this.handleCallback}
                      />
                    ) : (
                      <video controls>
                        <source src={this.state.fileURL} type="video/mp4" />
                      </video>
                    )}
                  </Row>
                </Col>
              </Row>
              <Row>
                <Col className={3}>
                  <Button
                    variant="light"
                    className="mb-2"
                    onClick={this.addRow}
                  >
                    新增
                  </Button>
                </Col>
              </Row>
              <Row>
                <UploadTable ref={this.ref}></UploadTable>
              </Row>
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onClick={this.handleClose}>
                取消
              </Button>
              <Button variant="primary" onClick={this.uploadVideo}>
                確定
              </Button>
            </Modal.Footer>
          </Modal>
        </Row>

        {/* <Row>
          <Button
            variant="primary"
            className="mb-2"
            onClick={(e) => {
              e.preventDefault();
              window.location.href = "http://google.com";
            }}
          >
            {" "}
            新增首件
          </Button>
        </Row> */}
      </>
    );
  }
}

export default UploadModal;
