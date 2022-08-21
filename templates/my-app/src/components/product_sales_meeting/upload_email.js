import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Row, Col, Form, FloatingLabel } from 'react-bootstrap';

import { useDropzone } from 'react-dropzone'

import axios from 'axios';

const uploadPdf = (file, onFileResponse) => {
  let pdf = new FormData();
  pdf.append('inputFile', file);
  // pdf.append('delivery_meet_content_id', 1);
  axios.post('/CRM/message_translate', pdf, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
    .then((response) => {
      onFileResponse(response.data)
    })
    .catch((error) => console.log(error))
}

function Accept(props) {
  const baseStyle = {
    flex: 1,
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    padding: '20px',
    borderWidth: 2,
    borderRadius: 2,
    borderColor: '#eeeeee',
    borderStyle: 'dashed',
    backgroundColor: '#fafafa',
    color: '#bdbdbd',
    outline: 'none',
    transition: 'border .24s ease-in-out'
  };

  const focusedStyle = {
    borderColor: '#2196f3'
  };

  const acceptStyle = {
    borderColor: '#00e676'
  };

  const rejectStyle = {
    borderColor: '#ff1744'
  };
  const {
    acceptedFiles,
    fileRejections,
    isFocused,
    isDragAccept,
    isDragReject,
    getRootProps,
    getInputProps
  } = useDropzone({
    accept: '.pdf,.msg',
    onDrop: files => {
      uploadPdf(files[0], props.onFileResponse);
    }
  });
  const style = useMemo(() => ({
    ...baseStyle,
    ...(isFocused ? focusedStyle : {}),
    ...(isDragAccept ? acceptStyle : {}),
    ...(isDragReject ? rejectStyle : {})
  }), [
    isFocused,
    isDragAccept,
    isDragReject
  ]);
  const acceptedFileItems = acceptedFiles.map(file => (
    <li key={file.path}>
      {file.path} - {file.size} bytes
    </li>
  ));

  const fileRejectionItems = fileRejections.map(({ file, errors }) => (
    <li key={file.path}>
      {file.path} - {file.size} bytes
      <ul>
        {errors.map(e => (
          <li key={e.code}>{e.message}</li>
        ))}
      </ul>
    </li>
  ));
const mail_name = acceptedFiles.map(file => (
  
    `${file.path}`
  
));
  return (
    <section className="container">
      <div {...getRootProps({ style })}>
        <input {...getInputProps()} />
        <p>上傳email</p>
      </div>
      <aside>
        <ul>{acceptedFileItems}</ul>
      </aside>
    </section>
  );
}

class UploadEmail extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      file_id: null,
      mail_name:"",
      translate: {
        origin: {
          body: "",
          subject: ""
        },
        english: {
          body: ``,
          subject: ``
        },
        chinese: {
          body: ``,
          subject: ``
        },
      }
    };
  }
  onFileResponse(translate_content) {
    let temp = this.state.translate
    temp["origin"] = translate_content.original
    temp["english"] = translate_content.english
    temp["chinese"] = translate_content.chinese
    this.setState({
      translate: temp,
      mail_name :translate_content.file_name
    })
  }
  render() {
    return (
      <>
        <Row className='gy-3'>
          <Col md="3">
            < Accept onFileResponse={this.onFileResponse.bind(this)} />
          </Col>
          <Col md="3">
            <FloatingLabel controlId="floatingTextarea" label="原文" className="mb-3">
              <Form.Control as="textarea" disabled placeholder="原文" value={"標頭 :" + this.state.translate.origin.subject + "內容 :" + this.state.translate.origin.body} />
            </FloatingLabel>
          </Col>
          <Col md="3">
            <FloatingLabel controlId="floatingTextarea" label="英文" className="mb-3">
              <Form.Control as="textarea" disabled placeholder="英文" value={"標頭 :" + this.state.translate.english.subject + "內容 :" + this.state.translate.english.body} />
            </FloatingLabel>
          </Col>
          <Col md="3">
            <FloatingLabel controlId="floatingTextarea" label="英翻中" className="mb-3">
              <Form.Control as="textarea" placeholder="英翻中" value={"標頭 :" + this.state.translate.chinese.subject + "內容 :" + this.state.translate.chinese.body} />
            </FloatingLabel>
          </Col>
        </Row>
      </>

    );
  }
}


export default UploadEmail;
