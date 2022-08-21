import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import { useDropzone } from 'react-dropzone'
import Image from 'react-bootstrap/Image'
import "bootstrap/dist/js/bootstrap.bundle.js";
import "bootstrap/dist/css/bootstrap.css";
import axios from 'axios';

const uploadPdf = (file, onFileResponse) => {
  let pdf = new FormData();
  pdf.append('inputFile', file);
  pdf.append('delivery_meet_content_id', 1);
  axios.post('/CRM/complaint/delivery_meet_content_file/upload', pdf, {
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
    accept: '.pdf,.jpg,.png,.tif',
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

  return (
    <section className="container">
      <div {...getRootProps({ style })}>
        <input {...getInputProps()} />
        <p>上傳 pdf png jpg  </p>
      </div>
      <aside>
        <ul>{acceptedFileItems}</ul>
      </aside>
    </section>
  );
}

class UploadBlock extends React.Component {
  constructor(props) {
    super(props);
    this.state = { file_id: null, image: [] };
  }
  onFileResponse(file_id) {
    file_id.map(u =>{
      axios.get(`/CRM/complaint/delivery_meet_content_file/${u.delivery_meet_content_file_id}`, { responseType: "blob" })
        .then((response) =>{
          var reader = new window.FileReader();
          reader.readAsDataURL(response.data);
          reader.onload = (e) =>{
            var imageDataUrl = reader.result;
            let images = this.state.image;
            images.push(imageDataUrl)
            this.setState({image:images});
          }
      });
    })
  }
  render() {
    return (
      <>
        <Accept onFileResponse={this.onFileResponse.bind(this)} />
        <Row className='gy-3'>
          {this.state.image.map(image =>(
            <Col md={2}>
              <Image thumbnail src={image} />
            </Col>
          ))}
        </Row>
      </>

    );
  }
}


export default UploadBlock;
