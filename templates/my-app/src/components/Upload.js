// Demo.jsx
import React, { useState, useRef } from 'react';
import axios from 'axios';
import Axios from 'axios'
import './Upload.css';
/* 
    allowType:image/jpg、image/png、image/jpeg、video/mp4，允許傳入之型態，Ex:"['image/pmg']"
    request_data:key:value，API的key:value用object型態傳入，Ex:"["key":"value"]"
    API location，API位置，Ex:"/3DConvert/PhaseGallery/coptd_image"
*/
class UploadFile extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            fetchSuccess: true,
            status: "將檔案拖放到這裡或點擊此處",
            percentage: 0,
            preview: null,
            enableDragDrop: true,
            source: Axios.CancelToken.source(),
            file_exists: false,
        };

        this.fileInputRef = React.createRef(null);
        // this.useRef = { fileInput: null }
    }
    resetPicture(data) {
        console.log(data)
        let state_temp = this.state;
        this.setState(state_temp);
        Object.keys(data).map((key, i) => {
            console.log(key)
        })
    }
    handleClick = () => {
        this.fileInputRef.current.click();
    }
    // 當有檔案進入顯示「偵測到檔案」
    onDragEnter = event => {
        if (this.setState.enableDragDrop) { this.setState({ status: '偵測到檔案' }); }
        event.preventDefault();
        event.stopPropagation();
    }
    // 當檔案放入成功，進入上傳階段，顯示「放入成功，上傳中...」
    onDragLeave = event => {
        if (this.setState.enableDragDrop) { this.setState({ status: '放入成功，上傳中...' }); }
        event.preventDefault();
    }
    // 當檔案放入可上傳之位置，顯示「放置此處」
    onDragOver = event => {
        if (this.setState.enableDragDrop) { this.setState({ status: '放置此處' }); }
        event.preventDefault();
    }
    onAbortClick = () => {
        this.setState({ preview: null, status: "將檔案拖放到這裡或點擊此處", percentage: 0, enableDragDrop: true });
        this.state.source.cancel('Operation canceled by the user.');
        this.setState({ source: Axios.CancelToken.source(), fetchSuccess: true });
    };
    // 發出上傳請求並顯示上傳進度
    UploadImg = (file, type) => {
        var config = { responseType: 'blob' };
        this.setState({ fetchSuccess: false })
        // 到時候要是可調的，允許之型態
        const supportedFilesTypes = this.props.allowType;
        if (supportedFilesTypes.indexOf(type) > -1 && this.state.enableDragDrop) {
            // Begin Reading File
            const reader = new FileReader();
            reader.onload = e => this.setState({ preview: e.target.result });
            reader.readAsDataURL(file);
            // console.log("file:" + file);
            // Create Form Data
            var payload = new FormData();
            payload.append('inputFile', file);
            Object.keys(this.props.request_data).map((key, i) => {
                payload.append(key, this.props.request_data[key]);
            })
            axios.post(this.props.API_location, payload, {
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
                this.props.parentCallback(response.data, this.state.preview);
            })
            // controller.abort();
            this.setState({ enableDragDrop: false });
        }
        else {
            this.setState({ preview: null, status: "此檔案無法上傳，請再次點擊或拖拉至此", fetchSuccess: true });
        }
    };
    // 「拖拉」正確放入檔案後，預處理file及type
    onDrop = event => {
        let file = event.dataTransfer.files[0];
        let type = event.dataTransfer.files[0].type;
        this.UploadImg(file, type);
        file = '';
        type = '';
    };
    // 「點擊」正確放入檔案後，預處理file及type
    handleFileChange = (event) => {
        let file = event.target.files[0];
        let type = event.target.files[0].type;
        this.UploadImg(file, type);
        file = '';
        type = '';
    }

    render() {
        return (
            <div className="UploadApp" style={{ height: this.props.height || "50vh" }} onDragEnter={this.onDragEnter} onDragLeave={this.onDragLeave} onDragOver={this.doNothing} onDrop={this.onDragLeave}>
                <input
                    type="file"
                    onChange={(e) => this.handleFileChange(e)}
                    ref={this.fileInputRef}
                    hidden
                />
                {/* <div className={`ImagePreview ${this.state.preview ? 'Show' : ''}`}>
                    <div style={{ backgroundImage: `url(${this.state.preview})` }}></div>
                </div> */}
                <div className={"Abort " + (this.state.fetchSuccess ? "hiddenBtn" : "")} onClick={this.onAbortClick}><span>&times;</span></div>
                <div className={`DropArea ${this.state.status === 'Drop' ? 'Over' : ''} ${this.state.status.indexOf('%') > -1 || this.state.status === 'Done' ? 'Uploading' : ''}`} onDragOver={this.onDragOver} onDragLeave={this.onDragEnter} onDrop={this.onDrop} onClick={this.handleClick} >
                    <div className={`ImageProgress ${this.state.preview ? 'Show' : ''}`}>
                        <div className="ImageProgressImage" style={{ backgroundImage: `url(${this.state.preview})`, backgroundSize: "contain", backgroundRepeat: "no-repeat" }}></div>
                        <div className="ImageProgressUploaded" style={{ backgroundImage: `url(${this.state.preview})`, clipPath: `inset(${100 - Number(this.state.percentage)}% 0 0 0)` }}></div>
                    </div>
                    <div className=
                        {`Status ${this.state.status.indexOf('%') > -1 || this.state.status === 'Done' ? 'Uploading' : ''}`}>{this.state.status}</div>
                </div>
            </div >
        );
    }
}

export default UploadFile