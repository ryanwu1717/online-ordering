import React, { useState, useRef, useEffect } from 'react';
import ReactDom from 'react-dom';
import axios from 'axios';
import DrawRectFunc from '../components/DrawRectFunc';
import 'bootstrap/dist/css/bootstrap.min.css';
import Axios from 'axios';
import BasicModal from './BasicModal';


function Complaint(props) {
    const [api_location, setApiLocation] = useState(undefined);
    const [note, setNote] = useState(undefined);
    const [background_src, setBackgroundSrc] = useState(undefined);
    const [attach_file_id, setAttachFileId] = useState(undefined);
    const [file_id, setFileId] = useState(undefined);
    const child = useRef(null);
    const child_modal = useRef(null);
    const [drawRectArea, setDrawRectArea] = useState(false)
    const [request_data, setRequestData] = useState([]);
    const [source, setSource] = useState(Axios.CancelToken.source());
    const [area_left_editable, setAreaLeftEditable] = useState(null);
    const [modal, setModal] = useState(
        {
            show: false,
            size: 'sm'
        }
    )
    useEffect(() => {
        if (props.request_data !== undefined) {
            if (props.request_data.attach_file_id !== '' && props.request_data.attach_file_id !== undefined) {
                console.log(props.request_data)
                setApiLocation(props.request_data.api_location);
                setBackgroundSrc(`${props.request_data.background_src}`);
                setAttachFileId(props.request_data.attach_file_id)
                setFileId(props.request_data.file_id)
                axios
                    .get(props.request_data.api_location, {
                        params: {
                            attach_file_id: props.request_data.attach_file_id
                        }
                    })
                    .then(response => {
                        setRequestData(response.data)
                        
                    })
                console.log(request_data)
            } else if (props.request_data.order_processes_subfile_id !== undefined) {
                setApiLocation(props.request_data.api_location);
                setBackgroundSrc(`${props.request_data.background_src}`);
                setDrawRectArea(props.drawRectArea);
                setAttachFileId(props.request_data.attach_file_id)
                setFileId(props.request_data.file_id)
                setAreaLeftEditable(props.area_left_editable)
                let request_data_temp = JSON.parse(JSON.stringify(props.request_data));
                delete request_data_temp.background_src;
                delete request_data_temp.api_location;
                delete request_data_temp.api_location_note;
                axios
                    .get(props.request_data.api_location, {
                        params: request_data_temp
                    })
                    .then(response => {
                        setRequestData(response.data);
                    })
                axios
                    .get(props.request_data.api_location_note, {
                        params: request_data_temp
                    })
                    .then(response => {
                        setNote(response.data);
                    })
            } else {
            }
        }
    }, [JSON.stringify(props.request_data)])

    const handlePostRect = (response_data) => {
        if (props.request_data.attach_file_id !== undefined) {
            Object.assign(response_data, { attach_file_id: props.request_data.attach_file_id });
            axios
                .post(api_location,
                    response_data
                )
                .then(response => {
                    Object.assign(response_data, { position_id: response.data.position_id })
                    setRequestData([...request_data || [], response_data])
                })

        } else {
            Object.assign(response_data, { order_processes_subfile_id: props.request_data.order_processes_subfile_id });
            axios
                .post(api_location,
                    [response_data]
                )
                .then(response => {
                    console.log(response.data)
                    Object.assign(response_data, { order_processes_position_id: response.data.data })
                    setRequestData([...request_data || [], response_data])
                })
        }

    }

    const handleUpdateRect = (response_data) => {
        if (props.request_data.attach_file_id !== undefined) {
            axios
                .patch(api_location,
                    response_data
                )
                .then(response => {
                    setRequestData(response_data)
                })

        } else {
            axios
                .patch(api_location,
                    response_data
                )
                .then(response => {
                    setRequestData(response_data)
                    setModal(
                        {
                            modal_title: "保存結果",
                            modal_body: "保存成功",
                            show: true,
                            size: 'sm',
                        }, child.current.callbackReturn(modal)
                    )
                    // child_modal.current.openModal();

                })
        }


    }

    const getDrawCanvasData = (e) => {
        return child.current.getDrawCanvasData();
    }

    const handleDeleteRect = (index) => {
        let cur_request_data = [...request_data]

        if (props.request_data.attach_file_id !== undefined) {
            axios
                .delete(api_location, {
                    data: [{
                        position_id: parseInt(request_data[index].position_id, 10)
                    }]
                })
                .then(response => {
                    cur_request_data.splice(index, 1)
                    setRequestData(cur_request_data)
                })

        } else {
            axios
                .delete(api_location, {
                    data: {
                        order_processes_position_id: parseInt(request_data[index].order_processes_position_id, 10)
                    }
                })
                .then(response => {
                    cur_request_data.splice(index, 1)
                    setRequestData(cur_request_data)
                })
        }


    }

    const handleShowPaint = (e) => {
        let file_id_params = "";
        file_id.map((value, index) => (
            file_id_params += `file_id[]=${value}&`
        ))
        setBackgroundSrc(`${axios.defaults.baseURL}/CRM/complaint/complaint/image?${file_id_params}attach_file_id=${attach_file_id}`)
    }

    const handleShowOrigin = (e) => {
        setBackgroundSrc(`${axios.defaults.baseURL}/CRM/complaint/complaint/attach_file/${attach_file_id}`)
    }

    const handleCanvasChange = (e) => {
        let canvas = child.current.getDrawCanvasData();
        let dataURL = canvas.toDataURL("image/png");
        let byteString = atob(dataURL.split(',')[1]);
        var mimeString = dataURL.split(',')[0].split(':')[1].split(';')[0];
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        var inputblob = new Blob([ia], { type: mimeString });
        var file = new File([inputblob], "name.png");
        var payload = new FormData();
        payload.append('inputFile', file);
        payload.append('attach_file_id', props.request_data.attach_file_id)
        axios.post('/CRM/complaint/attach_file/picture/paint', payload, {
            cancelToken: source.token,
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (e) => {
                // const done = e.position || e.loaded;
                // const total = e.totalSize || e.total;
                // const perc = (Math.floor(done / total * 1000) / 10);
                // if (perc >= 100) {
                //     this.setState({ status: "上傳完畢" })
                //     // Delayed reset
                //     setTimeout(() => {
                //         // setPreview(null);
                //         // this.setState({ status: "將檔案拖放到這裡或點擊此處" });
                //         this.setState({ status: "" });
                //         this.setState({ percentage: 0 });
                //         this.setState({ enableDragDrop: true });
                //         this.setState({ fetchSuccess: true });
                //     }, 750); // To match the transition 500 / 250
                // } else {
                //     this.setState({ status: `${perc}%` });
                // }
                // this.setState({ percentage: perc });
            }
        }).then(response => {

        })
    }

    const change_opr_subfile_code = (data) => {
        props.change_opr_subfile_code(data);
    }

    return (
        <>
            <DrawRectFunc
                request_data={request_data}
                note={note}
                area_left_editable={area_left_editable}
                background_src={background_src}
                drawRectArea={drawRectArea}
                modal={modal}
                handleCanvasChange={handleCanvasChange}
                ref={child}
                handlePostRect={handlePostRect}
                handleUpdateRect={handleUpdateRect}
                handleDeleteRect={handleDeleteRect}
                handleShowPaint={handleShowPaint}
                handleShowOrigin={handleShowOrigin}
                change_opr_subfile_code={change_opr_subfile_code}
            ></DrawRectFunc>
            <BasicModal
                modal_title={modal.modal_title}
                modal_body={modal.modal_body}
                show={modal.show}
                ref={child_modal}
                size={modal.size}
            ></BasicModal>
        </>

    )
}

export default Complaint;