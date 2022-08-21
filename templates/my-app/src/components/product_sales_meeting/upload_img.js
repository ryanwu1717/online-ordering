import React, { useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom';
import UploadEmail from './upload_email';
import { FloatingLabel, Image, FormControl, InputGroup, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';
import { ReactPainter } from 'react-painter';
import axios from 'axios';
import Select from 'react-select';
import { Editor } from "react-draft-wysiwyg";
import "react-draft-wysiwyg/dist/react-draft-wysiwyg.css";
import Draw from './draw';
import DataTable from 'react-data-table-component';
import "../meet/datatable.css"
class UploadImg extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
     img:[],
    }
  this.clickUpd = this.clickUpd.bind(this);
  }
  componentDidMount() {
    
  }
  componentDidUpdate() {

  }
  clickUpd = (e) => {
    document.getElementById("updfile").click();
  }
  changeImg(e){
    let new_src = e.target.src
    this.canvasRef.current.changeImage(new_src)
  }

  render() {
    return (
      <Row>
        <Row className='mb-2'>
          <Col md={"auto"}>
            <Button>
              上傳檔案
            </Button>
            <input id="updfile" style={{ display: 'none' }} type="file" ref={this.fileInputRef} onChange={(e) => this.onImageChange(e)} />
          </Col>
        </Row>
        <Row>
          <Col md={3}>
            <Image onClick={this.changeImg.bind(this)} thumbnail src="https://upload.wikimedia.org/wikipedia/commons/a/a1/Nepalese_Mhapuja_Mandala.jpg" />
          </Col>
          <Col md={3}>
            <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />
          </Col>
          <Col md={3}>
            <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />
          </Col>
          <Col md={3}>
            <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />
          </Col>
          <Col md={3}>
            <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />
          </Col>
          <Col md={3}>
            <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />
          </Col>
        </Row>

      </Row>



    );
  }
}
export default UploadImg;