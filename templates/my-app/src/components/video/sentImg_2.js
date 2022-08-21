import './SentImg2.css';
import React, { useState, useRef } from 'react';
import axios from 'axios';
import { Upload } from 'react-bootstrap-icons';

// 建立拖移進入的事件
const SentImg_2 = (props) => {
    const [status, setStatus] = useState('將檔案拖放到這裡或點擊此處');
    const [percentage, setPercentage] = useState(0);
    const [preview, setPreview] = useState(null);
    const [enableDragDrop, setEnableDragDrop] = useState(true);
    const doNothing = event => event.preventDefault();
    // 用來中止當前請求
    const controller =new AbortController();
    const CancelToken = axios.CancelToken;
    const source = CancelToken.source();
    const fileInput = useRef(null)
    // 點擊任何區域皆能觸發input file
    const handleClick = () => {
        fileInput.current.click();
    }
    const onDragEnter = event => {
        if (enableDragDrop) { setStatus('偵測到檔案'); }
        event.preventDefault();
        event.stopPropagation();
    }
    const onDragLeave = event => {
        if (enableDragDrop) { setStatus('放入成功，上傳中...'); }
        event.preventDefault();
    }
    const onDragOver = event => {
        if (enableDragDrop) { setStatus('放置此處'); }
        event.preventDefault();
    }
    // 上傳時，中止此請求
    const onAbortClick = () => {
        console.log("點擊請求");
        setPreview(null);
        setStatus('將檔案拖放到這裡或點擊此處');
        setPercentage(0);
        setEnableDragDrop(true);
        // controller.abort();
        source.cancel('Operation canceled by the user.');

    };
    // 正確放入檔案後，發出請求並顯示上傳進度
    const onDrop = event => {
        var config = {responseType: 'blob'};
        const supportedFilesTypes = ['video/mp4'];
        const { type } = event.dataTransfer.files[0];
        if (supportedFilesTypes.indexOf(type) > -1 && enableDragDrop) {
            // Begin Reading File
            const reader = new FileReader();
            reader.onload = e => setPreview(e.target.result);
            reader.readAsDataURL(event.dataTransfer.files[0]);
            console.log(event.dataTransfer.files[0]);
            // Create Form Data
            var payload = new FormData();
            payload.append('file', event.dataTransfer.files[0]);
            axios.post('/develop/video/preview_video',payload,{cancelToken: source.token
            }, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (e) => {
                    const done = e.position || e.loaded;
                    const total = e.totalSize || e.total;
                    const perc = (Math.floor(done / total * 1000) / 10);
                    if (perc >= 100) {
                        setStatus('上傳完畢');
                        // Delayed reset
                        setTimeout(() => {
                            // setPreview(null);
                            setStatus('將檔案拖放到這裡或點擊此處');
                            setPercentage(0);
                            setEnableDragDrop(true);
                        }, 750); // To match the transition 500 / 250
                    } else {
                        setStatus(`${perc}%`);
                    }
                    setPercentage(perc);
                }
            }).then((response) => {
                axios.get(`/develop/video/preview_video/${response.data.video_id}`, config)
                .then(resp => {
                    props.parentCallback(response.data.video_id, window.URL.createObjectURL(resp.data));
                })
            })
            // controller.abort();
            setEnableDragDrop(false);
        }
        event.preventDefault();
    }
    const handleFileChange = (event) => {
        console.log(event);
        const supportedFilesTypes = ['video/mp4'];
        const type = event.target.files[0].type;
        console.log(type);
        if (supportedFilesTypes.indexOf(type) > -1 && enableDragDrop) {
            // Begin Reading File
            const reader = new FileReader();
            reader.onload = e => setPreview(e.target.result);
            reader.readAsDataURL(event.target.files[0]);
            console.log(event.target.files[0]);
            // Create Form Data
            var payload = new FormData();
            payload.append('file', event.dataTransfer.files[0]);
            axios.post('/develop/video/preview_video', payload, {
                signal:controller.signal,
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (e) => {
                    const done = e.position || e.loaded;
                    const total = e.totalSize || e.total;
                    const perc = (Math.floor(done / total * 1000) / 10);
                    if (perc >= 100) {
                        setStatus('上傳完畢');
                        // Delayed reset
                        setTimeout(() => {
                            // setPreview(null);
                            setStatus('將檔案拖放到這裡或點擊此處');
                            setPercentage(0);
                            setEnableDragDrop(true);
                        }, 750); // To match the transition 500 / 250
                    } else {
                        setStatus(`${perc}%`);
                    }
                    setPercentage(perc);
                }
            })
            // controller.abort();
            setEnableDragDrop(false);

        }
        event.preventDefault();
    }
    return (
        <div className="App" onDragEnter={onDragEnter} onDragLeave={onDragLeave} onDragOver={doNothing} onDrop={onDragLeave}>
            <input
                type="file"
                onChange={(e) => handleFileChange(e)}
                ref={fileInput}
                hidden
            />
            <div className={`ImagePreview ${preview ? 'Show' : ''}`}>
                <video src={preview}></video>
            </div>
            <div className={`DropArea ${status === 'Drop' ? 'Over' : ''} ${status.indexOf('%') > -1 || status === 'Done' ? 'Uploading' : ''}`} onDragOver={onDragOver} onDragLeave={onDragEnter} onDrop={onDrop}  >
                <div className={`ImageProgress ${preview ? 'Show' : ''}`}>
                    <div className="ImageProgressImage" style={{ backgroundImage: `url(${preview})` }}></div>
                    <div className="ImageProgressUploaded" style={{ backgroundImage: `url(${preview})`, clipPath: `inset(${100 - Number(percentage)}% 0 0 0)` }}></div>
                </div>
                <div className={`Status ${status.indexOf('%') > -1 || status === 'Done' ? 'Uploading' : ''}`}>{status}</div>
                {status.indexOf('%') > -1 && <div className="Abort" onClick={onAbortClick}><span>&times;</span></div>}
            </div>
        </div>
    );
};
export default SentImg_2;