// Demo.jsx
import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';
import Axios from 'axios';
import './Upload.css';
/* 
    allowType:image/jpg、image/png、image/jpeg、video/mp4，允許傳入之型態，Ex:"['image/pmg']"
    request_data:key:value，API的key:value用object型態傳入，Ex:"["key":"value"]"
    API location，API位置，Ex:"/3DConvert/PhaseGallery/coptd_image"
*/
const UploadFile = (props) => {
    const [fetchSuccess, setFetchSuccess] = useState(true);
    const [status, setStatus] = useState("將檔案拖放到這裡或點擊此處");
    const [precentage, setPrecentage] = useState(0);
    const [preview, setPreview] = useState(null);
    const [returnPreview, setReturnPreview] = useState(null);
    const [enableDragDrop, setEnableDragDrop] = useState(true);
    const [fileExists, setfileExists] = useState(false);
    const [viewHeight, setViewHeight] = useState("50vh");
    const [srcCallBack, setSrcCallBack] = useState(null);
    let source = axios.CancelToken.source();
    const fileInputRef = useRef(null);
    const targetRef = useRef();
    const handleClick = () => {
        fileInputRef.current.click();
    }
    /*
    第二個參數稱作 dependencies，它是一個陣列，只要每次重新渲染後 dependencies 內的元素沒有改變，任何 useEffect 裡面的函式就不會被執行！*/
    useEffect(() => {
        if (props.file_exists === true) {
            setStatus("");
            setPreview(props.file_location);
            setfileExists(props.file_exists);
            if (props.height) {
                setViewHeight(props.height);
            }
        } else {
            setStatus("將檔案拖放到這裡或點擊此處");
            setPreview(null);
            setfileExists(false);
        }
    }, [props.file_location, props.file_exists]);
    useEffect(() => {
        if (srcCallBack !== null) {
            const data_return = {};
            data_return['preview'] = returnPreview;
            data_return['preview_file_id'] = srcCallBack;
            data_return['file_exists'] = true;
            props.parentCallback(data_return);
        }
    }, [srcCallBack]);
    useEffect(() => {
        if (props.anchor !== undefined) {
            targetRef.current.scrollIntoView(props.anchor);
        }
    }, [props.anchor]);

    // 當有檔案進入顯示「偵測到檔案」
    const onDragEnter = event => {
        if (setEnableDragDrop) { setStatus("偵測到檔案"); }
        event.preventDefault();
        event.stopPropagation();
    }
    // 當檔案放入成功，進入上傳階段，顯示「放入成功，上傳中...」
    const onDragLeave = event => {
        if (setEnableDragDrop) { setStatus("放入成功，上傳中..."); }
        event.preventDefault();
    }
    // 當檔案放入可上傳之位置，顯示「放置此處」
    const onDragOver = event => {
        if (setEnableDragDrop) { setStatus("放置此處"); }
        event.preventDefault();
    }
    const onAbortClick = () => {
        setPreview(null);
        setStatus("將檔案拖放到這裡或點擊此處");
        setPrecentage(0);
        setEnableDragDrop(true);
        source.cancel('Operation canceled by the user.');
        source = Axios.CancelToken.source();
        setFetchSuccess(true);
    };
    // 發出上傳請求並顯示上傳進度
    const UploadImg = (file, type) => {
        var config = { responseType: 'blob' };
        let temp = "";
        setFetchSuccess(false)
        // 到時候要是可調的，允許之型態
        const supportedFilesTypes = props.allowType;
        if (supportedFilesTypes.indexOf(type) > -1 && enableDragDrop) {
            // Begin Reading File
            const reader = new FileReader();
            reader.onload = e => {
                temp = (e.target.result)
                setPreview(temp)
                setReturnPreview(temp)
            }
            reader.readAsDataURL(file);
            var payload = new FormData();
            payload.append('inputFile', file);
            Object.keys(props.request_data).map((key, i) => {
                payload.append(key, props.request_data[key]);
            })
            axios.post(props.API_location, payload, {
                cancelToken: source.token,
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (e) => {
                    const done = e.position || e.loaded;
                    const total = e.totalSize || e.total;
                    const perc = (Math.floor(done / total * 1000) / 10);
                    if (perc >= 100) {
                        setStatus("上傳完畢");
                        // Delayed reset
                    } else {
                        setStatus(`${perc}%`)
                    }
                    setPrecentage(perc);
                }
            }).then(response => {
                setTimeout(() => {
                    let src_data_temp = JSON.parse(JSON.stringify(response.data));
                    setStatus("");
                    setPrecentage(0);
                    setEnableDragDrop(true);
                    setFetchSuccess(true);
                    setSrcCallBack(src_data_temp.src)
                }, 150); // To match the transition 500 / 250
            })
            setEnableDragDrop(false);
        }
        else {
            setPreview(null);
            setStatus("此檔案無法上傳，請再次點擊或拖拉至此");
            setFetchSuccess(true);
        }
    };
    // 「拖拉」正確放入檔案後，預處理file及type
    const onDrop = event => {
        let file = event.dataTransfer.files[0];
        let type = event.dataTransfer.files[0].type;
        UploadImg(file, type);;
        file = '';
        type = '';
    };
    // 「點擊」正確放入檔案後，預處理file及type
    const handleFileChange = (event) => {
        let file = event.target.files[0];
        let type = event.target.files[0].type;
        UploadImg(file, type);
        file = '';
        type = '';
    }

    return (
        <div className="UploadApp" style={{ height: viewHeight }} onDragEnter={onDragEnter} onDragLeave={onDragLeave} onDrop={onDragLeave} ref={targetRef}>
            <input
                type="file"
                onChange={(e) => handleFileChange(e)}
                ref={fileInputRef}
                hidden
                disabled={props.editable}
            />
            {/* <div className={`ImagePreview ${this.state.preview ? 'Show' : ''}`}>
                    <div style={{ backgroundImage: `url(${this.state.preview})` }}></div>
                </div> */}
            <div className={"Abort " + (fetchSuccess ? "hiddenBtn" : "")} onClick={onAbortClick}><span>&times;</span></div>
            <div className={`DropArea ${status === 'Drop' ? 'Over' : ''} ${status.indexOf('%') > -1 || status === 'Done' ? 'Uploading' : ''}`} onDragOver={onDragOver} onDragLeave={onDragEnter} onDrop={onDrop} onClick={handleClick} >
                <div className={`ImageProgress ${preview ? 'Show' : ''}`}>
                    <div className="ImageProgressImage" style={{ backgroundImage: `url(${preview})`, backgroundSize: "contain", backgroundRepeat: "no-repeat", width: "auto" }}></div>
                    {/* <div className="ImageProgressUploaded" style={{ backgroundImage: `url(${preview})`, clipPath: `inset(${100 - Number(precentage)}% 0 0 0)` }}></div> */}
                </div>
                <div className=
                    {`Status ${status.indexOf('%') > -1 || status === 'Done' ? 'Uploading' : ''}`}>{status}</div>
            </div>
        </div >
    );
}
export default UploadFile;