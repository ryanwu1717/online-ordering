import Row from "react-bootstrap/Row";
import Col from "react-bootstrap/Col";
import Card from "react-bootstrap/Card";
import ListGroup from "react-bootstrap/ListGroup";
import axios from "axios";
import React from "react";

class News extends React.Component {
  constructor() {
    super();
    this.state = {
      datas: {},
      label: {
        name: "影片類別",
        video_name: "影片名稱",
        user_name: "紀錄人",
        last_update_time: "上傳日期",
      },
      src: {},
      image: {}
    };
  }
  componentDidMount() {
    let new_response = [];
    axios.get("/develop/videos/top_three_videos").then((response) => {
      for (let i = 0; i < response.data.length; i++) {
        this.state.src[i] = response.data[i]['src'];
        new_response.push({
          name: response.data[i]["last_update_time"],
          video_name: response.data[i]["video_name"],
          user_name: response.data[i]["user_name"],
        });
        this.setState({ datas: new_response });

        axios.get(`${response.data[i]['src']}`, { responseType: "blob" }).then((res) => {
          var reader = new window.FileReader();
          reader.readAsDataURL(res.data);
          reader.onload = (e) => {
            var imageDataUrl = reader.result;
            let images = this.state.image;
            images.push(imageDataUrl);
            this.setState({ image: images });
          };
        });
      }
    });
    
  }
  render() {
    const label = this.state.label;
    const src = this.state.image[0];
    const presentComponent = Object.keys(this.state.datas).map((items) => (
      <Col>
        <Card className="mt-3">
          <Card.Img
            variant="top"
            style={{height: "30vh" }}
            src={src}
          />
          {Object.keys(this.state.datas[items]).map((key, value) => (
            <ListGroup variant="flush">
              <ListGroup.Item key={value}>
              {label[key]}：{this.state.datas[items][key]}
              </ListGroup.Item>
            </ListGroup>
          ))}
        </Card>
      </Col>
    ));
    return (
      <div>
        <Row>
          <Col md={11}>
            <Row>{presentComponent}</Row>
          </Col>
        </Row>
      </div>
    );
  }
}

export default News;
