import Row from "react-bootstrap/Row";
import Col from 'react-bootstrap/Col';
import { useState, useEffect } from "react";
import {Button, Form}  from 'react-bootstrap';
import axios from "axios";
const Search = (props) => {
  const [videoType, setVideoType] = useState("");
  const [videoName, setVideoName] = useState("");
  const [type, setType] = useState([]);
  useEffect(() => {
    let tmp = {};
    axios.get(`/develop/videos/video_type`).then((res) => {
      setType(res.data)
    });
  }, []);
  function handleClick () {
    console.log(videoType)
    props.passToParent([{'videoType': videoType, 'text': videoName}]);
  }
  return (
    <div>
        <Row className = "d-flex justify-content-start px-2">
            {/* <Col className = "m-0 p-0">
            <Form.Select
                aria-label="Default select example"
                onChange = {event => setVideoType(event.target.value)}
                value={
                  videoType == 0
                    ? 0
                    : videoType
                }
              >
                <option selected value = {0}>
                  請選擇
                </option>
                {type.map((item) => (
                  <option
                    value={item.id}
                    name={item.name}
                    selected={item.id == videoType}
                  >
                    {item.name}
                  </option>
                ))}
              </Form.Select>
            </Col> */}
            <Col className = "m-0 p-0">
                <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="請輸入搜尋內容"
                onChange = {event => setVideoName(event.target.value)}></input>
            </Col>
            <Col className = "d-flex justify-content-start">
              <Button variant = "primary" onClick = {handleClick}>搜尋</Button>
            </Col>
        </Row>
    </div>
  );
};
  
export default Search;
  