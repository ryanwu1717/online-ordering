import React from "react";
import axios from "axios";
import { Form, InputGroup, Card, Col, Row } from "react-bootstrap";
class Video extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      baseURL: [],
      fileName: "",
      videoType: "",
      name: "",
      remark: "",
    };
  }
  getVideo(file) {
    axios
      .get(`/develop/video/video_information`, {
        params: {
          video_id: file,
        },
      })
      .then((response) => {
        this.setState({ remark: response.data[0]["remark"] });
        this.setState({ fileName: response.data[0]["video_file_name"] });
        this.setState({ videoType: response.data[0]["video_type_name"] });
        this.setState({ name: response.data[0]["name"] });
        let baseURL = [
          `${axios.defaults.baseURL}/develop/video/preview_specific_video_or_file/${response.data[0]["video_file_name"]}`,
        ];
        this.setState({ baseURL: [] });
        this.setState({ baseURL: baseURL });
      });
  }
  render() {
    return (
      <>
        <Col>
          <Card className="shadow">
            <Card.Header>影片</Card.Header>
            <Card.Body>
              <Row>
                <Col>
                  <InputGroup className="mb-3">
                    <InputGroup.Text disabled>影片名稱</InputGroup.Text>
                    <Form.Control
                      type="text"
                      value={this.state.fileName}
                      aria-label="Disabled input example"
                      disabled
                      readOnly
                    />
                  </InputGroup>
                </Col>
              </Row>
              <Row>
                <Col md = {"auto"}>
                  <InputGroup className="mb-3">
                    <InputGroup.Text disabled>影片分類</InputGroup.Text>
                    <Form.Control
                      type="text"
                      value={this.state.videoType}
                      aria-label="Disabled input example"
                      disabled
                      readOnly
                      style = {{width: "auto"}}
                    />
                  </InputGroup>
                </Col>
                <Col>
                  <InputGroup className="mb-3">
                    <InputGroup.Text disabled>紀錄人</InputGroup.Text>
                    <Form.Control
                      type="text"
                      value={this.state.name}
                      aria-label="Disabled input example"
                      disabled
                      readOnly
                    />
                  </InputGroup>
                </Col>
              </Row>
              <div className="mh-100 d-flex">
                <div className="embed-responsive embed-responsive-16by9 align-content-center flex-wrap">
                  {this.state.baseURL.map((video) => {
                    return (
                      <video controls>
                        <source src={video} type="video/mp4" />
                      </video>
                    );
                  })}
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>
        <Col>
          <Card className="shadow" style={{ height: "100%" }}>
            <Card.Header>影片說明</Card.Header>
            <Card.Body
            // className = "d-flex align-items-center d-flex justify-content-center"
            >
              {this.state.remark}
            </Card.Body>
          </Card>
        </Col>
      </>
    );
  }
}
export default Video;
