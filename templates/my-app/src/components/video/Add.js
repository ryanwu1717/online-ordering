import React from "react";
import Row from "react-bootstrap/Row";
import Col from "react-bootstrap/Col";
import Button from "react-bootstrap/Button";
import InputGroup from "react-bootstrap/InputGroup";
import Form from "react-bootstrap/Form";
import Upload from "../Upload";
import axios from "axios";
import moment from "moment";
import UploadTable from "./UploadTable.js";
import AddVideoTypeModal from "./AddVideoTypeModal.js";
import Card from "react-bootstrap/Card";
import "./Add.css";
import { BulbFilled } from "@ant-design/icons";
import { Tooltip } from "antd";
class Add extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      video_type_id: "",
      video_type_name: "",
      video_name: "",
      user_name: "",
      fileURL: "",
      file_id: 0,
      UploadFile: true,
      type: [],
      input_datas: [],
      count: 0,
      remark: "",
      showVideoTypeModal: false,
    };
    this.handleChange = this.handleChange.bind(this);
    this.handleSelectChange = this.handleSelectChange.bind(this);
    this.handleInputChange = this.handleInputChange.bind(this);
    this.handleTextChange = this.handleTextChange.bind(this);
    this.addRow = this.addRow.bind(this);
    this.addVideoTypeModal = this.addVideoTypeModal.bind(this);
    this.updateDatas = this.updateDatas.bind(this);
    this.modalRef = React.createRef();
  }
  cleanData = () => {
    this.setState({
      video_type_id: "",
      video_type_name: "",
      video_name: "",
      remark: "",
    });
  };
  componentDidMount() {
    this.setState({ video_name: "" });
    this.getUserName();
    this.getVideoType();
  }
  handleSelectChange(event) {
    this.state.type.map((item) => {
      if (item.id == event.target.value) {
        this.setState({ video_type_name: item.name });
      }
    });
    this.setState({ video_type_id: event.target.value });
  }
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
  handleTextChange(event) {
    this.setState({ remark: event.target.value });
  }
  handleChange = (event) => {
    this.setState({ remark: event.target.value });
  };
  uploadVideo = () => {
    let postDatas = [];
    this.state.input_datas.map((items) => {
      postDatas.push({
        video_name: items.video_name,
        video_type: items.video_type_id,
        file_id: items.file_id,
        remark: items.remark,
      });
    });
    axios.post(`/develop/video/video`, postDatas).then((res) => {
      this.setState({
        video_type_id: "",
        video_name: "",
        remark: "",
        input_datas: [],
      });
      this.props.changeShowRead();
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
  getVideoType = () => {
    axios.get(`/develop/videos/video_type`).then((res) => {
      this.setState({ type: res.data });
    });
  };
  addRow = (event) => {
    if (
      this.state.video_name == "" ||
      this.state.video_type_id == 0 ||
      this.state.file_id == ""
    ) {
      alert("???????????????");
    } else {
      let input_datas_temp = [...this.state.input_datas];
      input_datas_temp.push({
        video_name: this.state.video_name,
        video_type_id: this.state.video_type_id,
        video_type_name: this.state.video_type_name,
        fileURL: this.state.fileURL,
        file_id: this.state.file_id,
        isChecked: false,
        remark: this.state.remark,
      });
      event.preventDefault();
      this.setState({
        input_datas: input_datas_temp,
        video_name: "",
        video_type_id: 0,
        UploadFile: true,
        remark: "",
      });
      this.state.remark = "";
    }
  };
  updateDatas(datas) {
    this.setState({ input_datas: datas });
  }
  componentDidUpdate(prevProps, prevState) {
    if (prevState.input_datas !== this.state.input_datas) {
      console.log(this.state.input_datas, "didupdate");
    }
  }
  addVideoTypeModal() {
    this.setState({ showVideoTypeModal: !this.state.showVideoTypeModal });
  }
  render() {
    let moment = require("moment");
    const { UploadFile } = this.state;
    let optionItems = this.state.type.map((type) => (
      <option value={type.id} name={type.name}>
        {type.name}
      </option>
    ));
    const component = (
      <>
        <Row className="justify-content-center">
          <Col md={6}>
            <Row width="100px">
              {UploadFile ? (
                <Upload
                  height="155px"
                  allowType={[
                    "video/mp4",
                    "video/avi",
                    "video/x-flv",
                    "video/x-ms-wmv",
                    "video/quicktime",
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
          <Col md={3}>
            <Row>
              <InputGroup className="mb-3">
                <InputGroup.Text id="inputGroup-sizing-default">
                  ????????????
                </InputGroup.Text>
                <Form.Select
                  aria-label="Default select example"
                  onChange={this.handleSelectChange}
                  value={
                    this.state.video_type_id == 0
                      ? "?????????"
                      : this.state.video_type_id
                  }
                >
                  <option disabled selected>
                    ?????????
                  </option>
                  {optionItems}
                </Form.Select>
              </InputGroup>
              <InputGroup className="mb-3">
                <InputGroup.Text id="inputGroup-sizing-default">
                  ????????????
                </InputGroup.Text>
                <Form.Control
                  type="text"
                  value={this.state.video_name}
                  onChange={this.handleInputChange}
                />
              </InputGroup>
              <Col>
                <Button
                  style={{ backgroundColor: "#336699", border: "#336699" }}
                  variant="primary"
                  className="mx-0 my-2 me-1"
                  onClick={this.addRow}
                >
                  ??????????????????
                </Button>
                {""}
              </Col>
            </Row>
          </Col>
          <Col>
            <Form.Group
              className="py-top"
              controlId="exampleForm.ControlTextarea1"
            >
              <Form.Label>????????????</Form.Label>
              <Form.Control
                as="textarea"
                rows={3}
                className="py-4"
                onBlur={this.handleTextChange}
                onChange={this.handleChange}
                value={this.state.remark}
              />
            </Form.Group>
          </Col>
        </Row>
      </>
    );
    return (
      <>
        <Row className="w-100 m-0">
          <Col md="12">
            <Card className="shadow mb-5 w-100">
              <Card.Title md="12" as="h3" className="badge text-center m-0 p-0">
                <Row className="m-0">
                  <Col md={"auto"} className="title">
                    <span>????????????</span>
                  </Col>
                  <Col md={"auto"}>
                    <Tooltip
                      className="my-2"
                      placement="rightTop"
                      title={"???????????????????????????"}
                    >
                      <BulbFilled className="my-2 bulb" />
                    </Tooltip>
                  </Col>
                </Row>
                <Row className="m-0">
                  <div
                    md={"auto"}
                    className="d-grid gap-2 d-md-flex justify-content-md-end"
                  >
                    <Button
                      style={{
                        backgroundColor: "#336699",
                        border: "#336699",
                      }}
                      onClick={this.addVideoTypeModal}
                    >
                      ??????????????????
                    </Button>
                  </div>
                </Row>
              </Card.Title>
              <Card.Body md="12">
                <Row className="d-flex">
                  <Col md={3}>
                    <InputGroup className="mb-3">
                      <InputGroup.Text disabled>?????????</InputGroup.Text>
                      <Form.Control
                        type="text"
                        placeholder={this.state.user_name}
                        aria-label="Disabled input example"
                        disabled
                        readOnly
                      />
                    </InputGroup>
                  </Col>
                  <Col md={3}>
                    <InputGroup className="mb-3">
                      <InputGroup.Text>????????????</InputGroup.Text>
                      <Form.Control
                        type="text"
                        placeholder={moment().format("Y-M-D")}
                        aria-label="Disabled input example"
                        disabled
                        readOnly
                      />
                    </InputGroup>
                  </Col>
                  {component}
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
        <Row className="w-100 m-0">
          <Col md="12">
            <Card className="shadow mb-5 w-100">
              <Card.Title md="12" as="h3" className="badge text-center m-0 p-0">
                {/* <span className="badge rounded position-absolute p-3 text-center" style = {{backgroundColor: "black"}}> */}
                <Row className="m-0">
                  <Col md={"auto"} className="title">
                    <span>?????????</span>
                  </Col>
                  <Col md={"auto"}>
                    <Tooltip
                      className="my-2"
                      placement="rightTop"
                      title={`???????????????????????????????????? ?????????????????????"????????????"`}
                    >
                      <BulbFilled className="my-2 bulb" />
                    </Tooltip>
                  </Col>
                  <div
                    md={"auto"}
                    className="d-grid gap-2 d-md-flex justify-content-md-end"
                  >
                    <Button
                      style={{
                        backgroundColor: "#336699",
                        border: "#336699",
                      }}
                      onClick={this.uploadVideo}
                    >
                      ????????????
                    </Button>
                    {""}
                  </div>
                </Row>
                {/* </span> */}
              </Card.Title>
              <div className="py-4">
                <UploadTable
                  input_datas={this.state.input_datas}
                  cleanData={this.cleanData}
                  updateDatas={this.updateDatas}
                ></UploadTable>
              </div>
            </Card>
          </Col>
        </Row>
        {this.state.showVideoTypeModal ? (
          <AddVideoTypeModal
            ref={this.modalRef}
            showVideoTypeModal={this.state.showVideoTypeModal}
            addVideoTypeModal={this.addVideoTypeModal}
          />
        ) : (
          <div></div>
        )}
      </>
    );
  }
}
export default Add;
