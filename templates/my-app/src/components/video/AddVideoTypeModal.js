import React from "react";
import { useState, useEffect, useRef } from "react";
import { Button, Row, Col } from "react-bootstrap";
import Modal from "react-bootstrap/Modal";
import InputGroup from "react-bootstrap/InputGroup";
import Form from "react-bootstrap/Form";
import axios from "axios";
import CustomTable from "./CustomTable.js";

function AddVideoTypeModal(props) {
  const [show, setShow] = useState(props.showVideoTypeModal);
  const ref = useRef();
  const [updateDatas, setUpdateDatas] = useState([])
  const [postData, setPostData] = useState("")
  const handleClose = () => {
    setShow(false);
    props.addVideoTypeModal();
  };
  const handleInputChange = (event) => {
    setPostData(event.target.value);
  };
  const uploadVideoType = (event) => {
    let data = [];
    data.push({ video_type_name: postData });
    axios.post("/develop/video/video_type", data ).then((res) => {
      console.log("post");
      ref.current.datatableCallBack();
    });
  };
  const deleteVideoType = () => {
    let data = [];
    ref.current.delete_arr.map((item) => (
      data.push({ video_type_id: item})
    ));
    axios.delete("/develop/video/video_type", { data }).then((res) => {
      console.log("delete");
      ref.current.datatableCallBack();
    });
  };
  // const patchVideoType = () => {
  //   console.log(ref.current.patch_arr)
  // }
  const clearChange = () => {
    setPostData("");
  }
  return (
    <>
      <Modal
        show={show}
        onHide={handleClose}
        size="md"
        aria-labelledby="contained-modal-title-vcenter"
        centered
      >
        <Modal.Header closeButton>
          <Modal.Title className="text-bold">影片類別</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Row>
            <Col>
              <InputGroup className="d-flex justify-content-center">
                <Col>
                  <Form.Control
                    type="text"
                    placeholder={"請輸入"}
                    onChange={handleInputChange}
                  />
                </Col>
              </InputGroup>
            </Col>
            <Col>
              <Button
                style={{
                  backgroundColor: "#336699",
                  border: "#336699",
                }}
                onClick={uploadVideoType}
              >
                新增
              </Button>
              <Button
                style={{
                  backgroundColor: "#336699",
                  border: "#336699",
                }}
                onClick={deleteVideoType}
                className="mx-1"
              >
                刪除
              </Button>
            </Col>
          </Row>
          <Row className="my-1">
            <div className="d-flex justify-content-center">
              <CustomTable ref = {ref}/>
            </div>
          </Row>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={handleClose}>
            關閉
          </Button>
          {/* <Button variant="primary" onClick={patchVideoType}>
            確定
          </Button> */}
        </Modal.Footer>
      </Modal>
    </>
  );
}
export default AddVideoTypeModal;
