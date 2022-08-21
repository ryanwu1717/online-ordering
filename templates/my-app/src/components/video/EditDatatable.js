import React from "react";
import Datatable from "../Datatable.js";
import Search from "./Search.js";
import "./EditDatatable.css";
import Button from "react-bootstrap/Button";
import ContentEditable from "react-contenteditable";
import TabsButtoms from "./TabsButtoms.js";
import UploadTable from "./UploadTable.js";
import axios from "axios";
import { Form, Card, Row, Col, ButtonGroup, InputGroup } from "react-bootstrap";
import { BulbFilled } from "@ant-design/icons";
import { Tooltip } from "antd";
const expandedComponent = ({ data }) => (
  <pre>{JSON.stringify(data, null, 2)}</pre>
);

class EditDatatable extends React.Component {
  constructor(props) {
    super(props);
    this.sortable = false;
    this.state = {
      patchDatas: [],
      datas: [],
      type: [],
      videoType: [],
      datatables_range: {
        require: {},
        thead: [
          // {
          //   name: "",
          //   cell: (row) => (
          //     <input
          //       type="checkbox"
          //       onClick={this.handleClick(row.video_id)}
          //       style={{ width: "20px", height: "20px" }}
          //       defaultChecked={row.isChecked}
          //     />
          //   ),
          //   width: "120px",
          //   center: true,
          // },
          {
            sortField: "time_order_video_type",
            name: "影片類別",
            cell: (row) => (
              <ContentEditable
                html={row.video_type_name} // innerHTML of the editable div
                disabled={true} // use true to disable edition
              />
            ),
            width: "auto",
            center: true,
            sortable: true,
          },
          {
            sortField: "time_order_video_name",
            name: "影片名稱",
            cell: (row) => (
              <ContentEditable
                html={row.video_name} // innerHTML of the editable div
                disabled={true} // use true to disable edition
                onChange={this.handleChange(row.video_id, "video_name")} // handle innerHTML change
              />
            ),
            width: "auto",
            center: true,
            sortable: true,
          },
          {
            name: "影片說明",
            cell: (row) => (
              <ContentEditable
                html={row.remark} // innerHTML of the editable div
                disabled={true} // use true to disable edition
                onChange={this.handleChange(row.video_id, "remark")} // handle innerHTML change
              />
            ),
            width: "auto",
            center: true,
            // sortable: true,
          },
          {
            name: "上傳者",
            cell: (row) => (
              <ContentEditable
                html={row.user_name} // innerHTML of the editable div
                disabled={true} // use true to disable edition
              />
            ),
            width: "auto",
            center: true,
            // sortable: true,
          },
          {
            sortField: "time_order_first",
            name: "上傳日期",
            cell: (row) => (
              <ContentEditable
                html={row.first_insert_time} // innerHTML of the editable div
                disabled={true} // use true to disable edition
              />
            ),
            width: "auto",
            center: true,
            sortable: true,
          },
          {
            sortField: "time_order_last",
            name: "最後修改日期",
            cell: (row) => (
              <ContentEditable
                html={row.last_update_time} // innerHTML of the editable div
                disabled={true} // use true to disable edition
              />
            ),
            width: "auto",
            center: true,
            sortable: true,
          },
        ],
      },
      delete_arr: [],
      row: [],
      buttomData: [
        { title: "全部", eventKey: "home", backgroundColor: "blueButton" },
      ],
      expandableRows: true,
      input_datas: [],
      callUploadTable: false,
    };
    this.postProcess = this.postProcess.bind(this);
    this.datatableCallBack = this.datatableCallBack.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.handleClick = this.handleClick.bind(this);
    this.handleSort = this.handleSort.bind(this);
    this.handleButtonGroupChange = this.handleButtonGroupChange.bind(this);
    this.sendDatas = this.sendDatas.bind(this);
    this.deleteDatas = this.deleteDatas.bind(this);
    this.passToParent = this.passToParent.bind(this);
    this.myRef = React.createRef();
    this.modalRef = React.createRef();
  }
  componentDidMount() {
    let tmp = [];
    axios.get(`/develop/videos/video_type`).then((res) => {
      this.setState({ videoType: res.data });
      this.state.videoType.map((items) =>
        this.state.buttomData.push({
          title: items.name,
          eventKey: items.id,
          backgroundColor: "whiteButton",
        })
      );
      res.data.map((item) => {
        tmp[item.name] = item.id;
      });
      this.setState({ type: tmp });
    });

    axios.get(`/develop/video/garbage/videos`).then((res) => {
      this.setState({ input_datas: res.data });
    })
    this.myRef.current.fetchUsers();
  }
  componentDidUpdate(prevState) {
    if (
      prevState.datatables_range !== this.state.datatables_range &&
      this.sortable
    ) {
      this.datatableCallBack();
      this.sortable = false;
    }
  }
  datatableCallBack() {
    this.myRef.current.fetchUsers();
  }
  postProcess(response) {
    this.setState({ delete_arr: [] });
    let newResponses = Object.assign({}, response);
    for (var i = 0; i < response.data.data.length; i++) {
      let newResponse = { ...newResponses.data.data[i] };
      newResponse["isChecked"] = false;
      newResponses.data.data[i] = newResponse;
    }
    this.setState({ img_path: response.data.src });
    this.setState({ datas: newResponses.data.data });
    return newResponses;
  }
  handleChange = (video_id, changeName) => (event) => {
    let newDatas = [...this.state.datas];
    for (let i = 0; i < this.state.datas.length; i++) {
      if (newDatas[i].video_id == video_id) {
        let newData = { ...newDatas[i] };
        newData[changeName] = event.target.value;
        newDatas[i] = newData;
        if (this.state.patchDatas.length === 0) {
          this.state.patchDatas.push(newData);
        } else {
          for (let j = 0; j < this.state.patchDatas.length; j++) {
            if (this.state.patchDatas[j]["video_id"] === video_id) {
              this.state.patchDatas[j][changeName] = event.target.value;
            } else if (
              this.state.patchDatas[j]["video_id"] !== video_id &&
              j === this.state.patchDatas.length - 1
            ) {
              this.state.patchDatas.push(newData);
            }
          }
        }
      }
    }
    this.setState({ datas: newDatas });
  };
  handleClick = (video_id) => (event) => {
    let newChecks = [...this.state.datas];
    for (let i = 0; i < this.state.datas.length; i++) {
      /* checked or unchecked checkbox */
      if (newChecks[i].video_id == video_id) {
        let newCheck = { ...newChecks[i] };
        newCheck.isChecked = event.target.checked;
        newChecks[i] = newCheck;
      }
    }
    this.setState({ datas: newChecks });
  };
  handleSort = async (column, sortDirection) => {
    this.sortable = true;
    let newDatatables = Object.assign({}, this.state.datatables_range);
    delete newDatatables.require["time_order_last"];
    delete newDatatables.require["time_order_first"];
    delete newDatatables.require["time_order_video_name"];
    delete newDatatables.require["time_order_video_type"];
    newDatatables.require[column["sortField"]] = sortDirection;
    this.setState({ datatables_range: newDatatables });
    // this.datatableCallBack();
  };
  handleButtonGroupChange(key) {
    const newDatatables = { ...this.state.datatables_range };
    if (key == "home") {
      newDatatables["require"]["videoType"] = null;
    } else {
      newDatatables["require"]["videoType"] = key;
    }
    this.setState({ datatables_range: newDatatables });
    this.setState({ buttomData: [{ title: "全部", eventKey: "home" }] });
    this.datatableCallBack();
  }
  sendDatas = () => {
    let datas = this.state.patchDatas;
    axios
      .patch("/develop/video/video", datas)
      .then((response) => this.myRef.current.fetchUsers())
      .catch((error) => console.log(error));
  };
  deleteDatas = () => {
    let data = new Object([]);
    data.push({ delete: 0, data: [] });
    for (let i = 0; i < this.state.delete_arr.length; i++) {
      data.data.push({ video_id: this.state.delete_arr[i] });
    }
    axios.delete("/develop/video/garbage/videos", { data }).then(() => {
      this.myRef.current.fetchUsers();
      this.setState({ delete_arr: [] });
      this.setState({ callUploadTable: !this.state.callUploadTable });
    });
  };
  rowClickedHandler = (row, e) => {
    if (this.state.delete_arr.indexOf(row.video_id) === -1) {
      Object.assign(e.target.parentElement.style, { background: "#ffe8e8" });
      let delete_arr_temp = [...this.state.delete_arr];
      delete_arr_temp.push(row.video_id);
      this.setState({ delete_arr: delete_arr_temp });
    } else {
      Object.assign(e.target.parentElement.style, { background: "#ffffff" });
      let delete_arr_temp = [...this.state.delete_arr];
      delete_arr_temp.splice(this.state.delete_arr.indexOf(row.video_id), 1);
      this.setState({ delete_arr: delete_arr_temp });
    }
  };
  passToParent(require) {
    let newRequire = { ...this.state.datatables_range };
    newRequire["require"]["videoType"] = require[0]["videoType"];
    newRequire["require"]["text"] = require[0]["text"];
    if (require[0]["videoType"] == 0) {
      delete this.state.datatables_range["require"]["videoType"];
      delete newRequire["require"]["videoType"];
    }
    console.log(newRequire);
    this.setState({ datatables_range: newRequire });
    this.datatableCallBack();
  }

  render() {
    return (
      <>
        <Row className="w-100 m-0 p-0">
          <Col md="12">
            <Card className="shadow mb-5 w-100">
              <Card.Title md="12" as="h3" className="badge text-center m-0 p-0">
                <Row className="m-0">
                  <Col md={"auto"} className="title">
                    <span>影片編輯</span>
                  </Col>
                </Row>
              </Card.Title>
              <Card.Body md="12">
                <Row className="d-flex justify-content-start">
                  <Col md={6}>
                    <Search passToParent={this.passToParent} />
                  </Col>
                  <Col className="d-flex justify-content-end">
                    <ButtonGroup aria-label="Basic example">
                      <Button
                        variant="warning"
                        onClick={this.deleteDatas}
                        className="update"
                      >
                        刪除
                      </Button>
                      <Button
                        variant="warning"
                        onClick={this.sendDatas}
                        className="update"
                      >
                        保存
                      </Button>
                    </ButtonGroup>
                  </Col>
                </Row>
                <Row className="d-flex justify-content-start my-3">
                  <Col md={"auto"}>
                    <TabsButtoms
                      buttoms={this.state.buttomData}
                      parentCallback={this.handleButtonGroupChange}
                    />
                  </Col>
                </Row>
                <Row className="d-flex">
                  <div className="single_line m-0 p-0">
                    <Datatable
                      sortDatatble
                      handleSort={this.handleSort}
                      rowClickedHandler={this.rowClickedHandler}
                      datatables={this.state.datatables_range}
                      postProcess={this.postProcess}
                      ref={this.myRef}
                      api_location="/develop/videos/videos_single_line"
                      expandableRows={this.state.expandableRows}
                      expandedComponent={({ data }) => (
                        <Card className="shadow mb-5 w-100">
                          <Card.Body md="12">
                            <Row className="p-2">
                              <Col md={"auto"}>
                                <video controls>
                                  <source src={data.src} type="video/mp4" />
                                </video>
                              </Col>
                              <Col md={"auto"}>
                                <InputGroup className="mb-3">
                                  <InputGroup.Text id="inputGroup-sizing-default">
                                    影片類別
                                  </InputGroup.Text>
                                  <Form.Select
                                    aria-label="Default select example"
                                    onChange={this.handleChange(
                                      data.video_id,
                                      "video_type"
                                    )}
                                    value={
                                      data.video_type == 0
                                        ? "請選擇"
                                        : data.video_type
                                    }
                                  >
                                    <option disabled selected>
                                      請選擇
                                    </option>
                                    {this.state.videoType.map((type) => (
                                      <option
                                        value={type.id}
                                      >
                                        {type.name}
                                      </option>
                                    ))}
                                  </Form.Select>
                                </InputGroup>
                                <InputGroup className="mb-3">
                                  <InputGroup.Text>影片名稱</InputGroup.Text>
                                  <Form.Control
                                    type="text"
                                    defaultValue={data.video_name}
                                    aria-label="Disabled input example"
                                    onChange={() => this.handleChange(
                                      data.video_id,
                                      "video_name"
                                    )}
                                  />
                                </InputGroup>
                              </Col>
                              <Col>
                                <Card
                                  className="shadow"
                                  style={{ height: "100%" }}
                                >
                                  <Card.Header>影片說明</Card.Header>
                                  <Card.Body
                                  // className = "d-flex align-items-center d-flex justify-content-center"
                                  >
                                    <Form.Group controlId="exampleForm.ControlTextarea1">
                                      <Form.Control
                                        as="textarea"
                                        rows={3}
                                        defaultValue={data.remark}
                                        onChange={() => this.handleChange(
                                          data.video_id,
                                          "remark"
                                        )}
                                      />
                                    </Form.Group>
                                  </Card.Body>
                                </Card>
                              </Col>
                            </Row>
                          </Card.Body>
                        </Card>
                      )}
                    />
                  </div>
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
        <Row className="w-100 m-0 p-0">
          <Col md="12">
            <Card className="shadow mb-5 w-100">
              <Card.Title md="12" as="h3" className="badge text-center m-0 p-0">
                <Row className="m-0">
                  <Col md={"auto"} className="title">
                    <span>影片回收區</span>
                  </Col>
                  <Col md={"auto"}>
                    <Tooltip
                      className="my-2"
                      placement="rightTop"
                      title={"30天後會自動刪除"}
                    >
                      <BulbFilled className="my-2 bulb" />
                    </Tooltip>
                  </Col>
                </Row>
              </Card.Title>
              <div className="py-4">
                <UploadTable
                  input_datas={this.state.input_datas}
                  callUploadTable = {this.state.callUploadTable}
                ></UploadTable>
              </div>
            </Card>
          </Col>
        </Row>
      </>
    );
  }
}
export default EditDatatable;
