import React from "react";
import Button from "react-bootstrap/Button";
import TabsButtoms from "./TabsButtoms.js";
import { Row, Col } from "react-bootstrap";
import DatatableCard from "../DatatableCard.js";
import axios from "axios";

class Tab extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      buttomData: [
        { title: "全部", eventKey: "home", backgroundColor: "blueButton" },
      ],
      datatables: {
        require: {},
        request_label: {
          video_type_name: "影片類別",
          video_name: "影片名稱",
          user_name: "紀錄人",
          first_insert_time: "上傳日期",
        },
        request: "/develop/videos/videos",
      },
      type: [],
      sendType: 0,
      upload: {
        Upload_type: ["image/jpg", "image/jpeg", "image/png", "video/mp4"],
        Upload_request_data: {},
        Upload_API_location: "/develop/videos/videos",
        return_image: "",
        editable: null,
      },
      componentControl: {
        role: 3,
        have_image: null,
      },
      fileName: "",
    };
    this.child = React.createRef();
    this.onSearchClick = this.onSearchClick.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.clearButtomData = this.clearButtomData.bind(this);
    this.imgTransport = this.imgTransport.bind(this);
    this.getDatatbleRequire = this.getDatatbleRequire.bind(this);
  }
  componentDidMount() {
    this.getVideoType();
  }
  getDatatbleRequire(require) {
    let newRequire = { ...this.state.datatables };
    newRequire["require"]["text"] = require[0]["text"];
    newRequire["require"]["videoType"] = require[0]["videoType"];
    if (require[0]["videoType"] == 0) {
      delete this.state.datatables["require"];
      delete newRequire["require"]["videoType"];
    }
    this.setState({ datatables: newRequire });
    this.afterHandleChange();
  }
  onSearchClick() {
    this.child.current.fetchUsers(
      this.state.datatables.require,
      this.state.datatables.request
    );
  }
  onDatatablesReponse(response) {
    console.log(response)
    let new_data = [];
    let tmp = [];
    let data = response.data.data;
    let new_response = {};
    for (let i = 0; i < response.data.data.length; i++) {
      for (let j = 0; j < response.data.data[i].length; j++) {
        tmp.push({
          video_type_name: data[i][j]["video_type_name"],
          first_insert_time: data[i][j]["first_insert_time"],
          video_name: data[i][j]["video_name"],
          user_name: data[i][j]["user_name"],
          src: data[i][j]["src"],
        });
      }
      new_data.push(tmp);
      tmp = [];
    }
    new_response["config"] = response.config;
    new_response["headers"] = response.headers;
    new_response["request"] = response.request;
    new_response["status"] = response.status;
    new_response["statusText"] = response.statusText;
    new_response["data"] = {};
    new_response["data"]["data"] = new_data;
    new_response["data"]["total"] = response.data.total;
    return new_response;
  }
  customizeCardGrandParent(response) {
    let data = {};
    let return_package = {};
    response.map((row, i) => {
      if (i === response.req_id) {
        Object.keys(row).map((key) => {
          if (key !== "src") {
            data[key] = row[key];
          }
        });
        return_package["datas"] = data;
        return_package["image_temp"] = row.src;
        return_package["image"] = [];
      }
    });
    return return_package;
  }
  getVideoType = () => {
    axios.get(`/develop/videos/video_type`).then((res) => {
      this.setState({ type: res.data });
      this.setState({
        buttomData: [
          { title: "全部", eventKey: "home", backgroundColor: "whiteButton" },
        ],
      });
    });
  };
  handleChange(key) {
    const newDatatables = { ...this.state.datatables };
    if (key == "home") {
      newDatatables["require"]["videoType"] = null;
    } else {
      newDatatables["require"]["videoType"] = key;
    }
    this.setState({ datatables: newDatatables });
    this.setState({ buttomData: [{ title: "全部", eventKey: "home" }] });
    this.afterHandleChange();
  }
  afterHandleChange = () => {
    this.child.current.fetchUsers();
  };
  clearButtomData = () => {
    this.setState({
      buttomData: [
        { title: "全部", eventKey: "home", backgroundColor: "whiteButton" },
      ],
    });
    this.afterHandleChange();
  };
  imgTransport(child_obj) {
    // let upload_temp = this.state.upload;
    // let componentControl_temp = this.state.componentControl;
    // if (child_obj.have_image === true) {
    //   upload_temp["return_image"] = child_obj.image;
    // }
    // componentControl_temp["have_image"] = child_obj.have_image;

    // this.setState({
    //   componentControl: componentControl_temp,
    // });
    /*-------------------------------------------------------------------*/
    this.setState({ buttomData: [{ title: "全部", eventKey: "home" }] });

    let file_id = child_obj["image_temp"].split("/");

    axios
      .get(`/develop/video/video_information`, {
        params: {
          video_id: file_id[file_id.length - 1],
        },
      })
      .then((response) => {
        this.setState({ fileName: response.data[0]["video_file_name"] });
        this.props.tabCallBack(file_id[file_id.length - 1]);
      });
  }
  render() {
    this.state.type.map((items) =>
      this.state.buttomData.push({
        title: items.name,
        eventKey: items.id,
        backgroundColor: "whiteButton",
      })
    );
    return (
      <div>
        <Row>
          <Col md={"auto"}>
            <TabsButtoms
              buttoms={this.state.buttomData}
              parentCallback={this.handleChange}
            />
          </Col>
        </Row>
        <div className="server_side">
          <DatatableCard
            datatables={this.state.datatables}
            ref={this.child}
            postProcess={this.onDatatablesReponse}
            customizeCardGrandParent={this.customizeCardGrandParent}
            imgTransport={this.imgTransport}
          />
        </div>
      </div>
    );
  }
}
export default Tab;
