import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import "react-draft-wysiwyg/dist/react-draft-wysiwyg.css";
import CanvasDraw from "react-canvas-draw";
import { Image, FormControl, InputGroup, Button, Card, Row, Col, Form } from 'react-bootstrap';

// SummerNote.ImportCode();
class Draw extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            img_src: "",
            brushColor: "",
            height: "",
        }

        this.canvasRef = React.createRef(null);
        this.changeImage = this.changeImage.bind(this)
    }
    componentDidMount() {
       
    }
    componentDidUpdate() {

    }
    componentWillMount() {
       
    }
    setColor = (e) => {
        this.setState({
            brushColor: e.target.value
        });
        console.log(e.target.value)
    }
    handleCanvasChange = (e) => {
        let canvas = this.canvasRef.current.canvasContainer.childNodes[1]
        let dataURL = canvas.toDataURL("image/png");
        let byteString = atob(dataURL.split(',')[1]);
        var mimeString = dataURL.split(',')[0].split(':')[1].split(';')[0];
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        var inputblob = new Blob([ia], { type: mimeString });
        var file = new File([inputblob], "name");
        var payload = new FormData();
        payload.append('inputFile', file);
        axios.post(this.state.upload.API_location, payload, {
            cancelToken: this.state.source.token,
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (e) => {
                const done = e.position || e.loaded;
                const total = e.totalSize || e.total;
                const perc = (Math.floor(done / total * 1000) / 10);
                if (perc >= 100) {
                    this.setState({ status: "上傳完畢" })
                    // Delayed reset
                    setTimeout(() => {
                        // setPreview(null);
                        // this.setState({ status: "將檔案拖放到這裡或點擊此處" });
                        this.setState({ status: "" });
                        this.setState({ percentage: 0 });
                        this.setState({ enableDragDrop: true });
                        this.setState({ fetchSuccess: true });
                    }, 750); // To match the transition 500 / 250
                } else {
                    this.setState({ status: `${perc}%` });
                }
                this.setState({ percentage: perc });
            }
        }).then(response => {

        })
    }
    changeImage(imgSrc) {
        console.log(imgSrc);
        this.setState({
            img_src: `${imgSrc}`
        })
    }
    render() {
        return (
            <>
                <Row className='my-2'>
                    <CanvasDraw
                        ref={canvasDraw => (this.canvasRef = canvasDraw)}
                        imgSrc={`${this.state.img_src}`}
                        brushColor={this.state.brushColor}
                        brushRadius={2}
                        lazyRadius={2}
                        enablePanAndZoom={true}
                        canvasHeight={500}
                        canvasWidth={600}
                        id="canvas"
                    />
                </Row>
                <Row className='my-2'>
                    <Form.Control
                        type="color"
                        id="exampleColorInput"
                        defaultValue={this.state.brushColor}
                        title="Choose your color"
                        onChange={this.setColor.bind(this)}
                    />
                    <Button className="mx-2" variant="light" onClick={() => { this.canvasRef.undo(); }} style={{ width: 'auto', fontWeight: "bold", background: "#7DC0BC", color: "white", }}>上一步</Button>
                    <Button className="mx-2" variant="light" onClick={() => { this.canvasRef.eraseAll(); }} style={{ width: 'auto', fontWeight: "bold", background: "#7DC0BC", color: "white", }}>清空</Button>
                    <Button className="mx-2" variant="light" onClick={this.handleCanvasChange.bind(this)} style={{ width: 'auto', fontWeight: "bold", background: "#7B84A0", color: "white", }}>儲存圖片</Button>
                </Row>
            </>
        );
    }
}
export default Draw;