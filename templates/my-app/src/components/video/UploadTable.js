import React from "react";
import Table from "react-bootstrap/Table";
import { useState, useEffect } from "react";
import { Button, Row, Col } from "react-bootstrap";
import "./UploadTable.css";

const UploadTable = (props) => {
  const [datas, setDatas] = useState([]);
  const thead = [
    "影片類別",
    "影片名稱",
    "影片",
    "影片說明",
    <Button variant="warning" onClick={handleDelete}>
      刪除
    </Button>,
  ];

  useEffect(()=>{
    setDatas(props.input_datas);
    if (props["cleanData"] !== undefined)
      props.cleanData();
  },[props.input_datas])

  function handleDelete() {
    let datasTmp = [...datas];
    datasTmp = datasTmp.filter(function(value) {
      return !value.isChecked;
    })
    setDatas(datasTmp);
    if (props["updateDatas"] !== undefined) 
      props.updateDatas(datasTmp);
  }
  function handleClick(event) {
    let newChecks = [...datas];
    for (let i = 0; i < datas.length; i++) { /* checked or unchecked checkbox */
      if (datas[i].file_id == event.target.name) {
        let newCheck = {...newChecks[i]};
        newCheck.isChecked = event.target.checked;
        newChecks[i] = newCheck;
      }
    }
    setDatas(newChecks);
  }
  return (
    <Row>
      <Col>
        <Table>
          <thead>
            <tr>
              {thead.map((th) => (
                <th key={th}>{th}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {datas.map((item) => (
              <tr>
                <td>{item.video_type_name}</td>
                <td>{item.video_name}</td>
                <td>
                  <video controls>
                    <source src={item.fileURL} type="video/mp4" />
                  </video>
                </td>
                <td>{item.remark}</td>
                <td>
                  <input
                    type="checkbox"
                    name={item.file_id}
                    style={{ width: "20px", height: "20px" }}
                    onClick={handleClick}
                    checked = {item.isChecked}
                  />
                </td>
              </tr>
            ))}
          </tbody>    
        </Table>
      </Col>
    </Row>
  );
};
export default UploadTable;
