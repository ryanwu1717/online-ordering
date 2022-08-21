import { Row, Col, Button, Image, Card } from "react-bootstrap";
import 'bootstrap/dist/css/bootstrap.min.css';
import React from "react";
import axios from 'axios';
import Axios from 'axios';
import DynamicCrop from "./DynamicCrop";
import Complaint from "./Complaint";
import BasicModal from './BasicModal';
import { GiCancel } from "react-icons/gi";

class Gallery extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            Search: {
                'label': '備註:', 'id': 'order_processes_subfile_id', 'type': 'input', 'value': '',
            },
            upload: {
                allowType: ['image/jpg', 'image/jpeg', 'image/png', 'video/mp4'],
                request_data: {
                    order_processes_id: null,
                },
                API_location: "/3DConvert/PhaseGallery/order_processes/reprocess_image",
                return_image: '',
                return_data: {},
                editable: null,
            },
            modal: {
                show: false,
                size: 'sm'
            },
            source: Axios.CancelToken.source(),
            request: this.props.ParentData.Process_API_location_processes,
            request_upload: this.props.ParentData.Process_API_location_crop,
            request_delete_crop: this.props.ParentData.Process_API_location_crop_delete,
            request_delete_rect: this.props.ParentData.Process_API_location_rect_delete,
            request_add_subfile: this.props.ParentData.Process_API_location_subfile_add,
            api_location: '/3DConvert/PhaseGallery/order_processes/position',
            api_location_note: '/3DConvert/PhaseGallery/order_processes/subfile_image',
            cropData: undefined,
            rect_data: undefined,
            rectData: undefined,
            same_pic: true,
            formDataTemp: undefined,
        }

        this.child = React.createRef();
        this.subFileModal = React.createRef();
        this.fileInputRef = React.createRef(null);
        this.changeRect = this.changeRect.bind(this);
        this.deleteImg = this.deleteImg.bind(this);
        this.addRectData = this.addRectData.bind(this);
        this.handleChange = this.handleChange.bind(this);
        this.subfileNoteSave = this.subfileNoteSave.bind(this);
        this.callbackReturn = this.callbackReturn.bind(this);
    }

    componentDidMount() {
        console.log(this.props.ParentData)
        if (JSON.stringify(this.state.upload.request_data) !== JSON.stringify(this.props.ParentData)) {
            let upload_request_data_temp = this.state.upload.request_data;
            let upload_temp = this.state.upload;
            upload_request_data_temp['order_processes_id'] = this.props.ParentData.order_processes_id;
            upload_temp['request_data'] = upload_request_data_temp;
            this.setState({
                upload: upload_temp,
            })

            if (this['props']['ParentData']['order_processes'] !== undefined) {
                let search_temp = { ...this.state.Search };
                let cropDataTemp = { ...this['props']['ParentData'] };
                cropDataTemp['order_processes'].forEach((row, index) => {
                    search_temp['id'] = row.order_processes_subfile_id;
                    row['Search'] = [{ ...search_temp }];
                })
                this.setState({
                    cropData: cropDataTemp,
                })
            }

        }
    }

    componentDidUpdate(prevState, prevProps) {
        if (JSON.stringify(this.state.upload.request_data.order_processes_id) !== JSON.stringify(this.props.ParentData.order_processes_id)) {
            let upload_request_data_temp = { ...this.state.upload.request_data };
            let upload_temp = { ...this.state.upload };
            upload_request_data_temp['order_processes_id'] = this.props.ParentData.order_processes_id;
            upload_temp['request_data'] = upload_request_data_temp;
            this.setState({
                upload: upload_temp,
            })
        } else if (this['props']['ParentData'] !== undefined) {
            if (this['state']['cropData'] === undefined && this['props']['ParentData']['order_processes'] !== undefined) {
                let search_temp = { ...this.state.Search };
                let cropDataTemp = { ...this['props']['ParentData'] };
                cropDataTemp['order_processes'].forEach((row, index) => {
                    search_temp['id'] = row.order_processes_subfile_id;
                    row['Search'] = [{ ...search_temp }];
                })

                this.setState({
                    cropData: cropDataTemp,
                })
            }
        } else if (prevProps.cropData !== undefined && this.props.ParentData !== undefined) {
            this.child.current.changeFileArray();
        }
    }

    resetPic() {
        this.child.current.resetPic(this.props.ParentData.old_src);
    }

    changeRect(e) {
        let order_processes_subfile_id = e.target.closest('.card').getAttribute('deledata');
        let background_src = e.target.getAttribute('src');
        this.setState({
            rectData: {
                background_src: background_src,
                order_processes_subfile_id: order_processes_subfile_id,
                api_location: this.state.api_location,
                api_location_note: this.state.api_location_note,
            }
        })
    }

    deleteImg(e) {
        let delete_data = [];
        let order_processes_subfile_id = e.target.closest('.card').getAttribute('deledata');
        let order_processes_row_index = e.target.closest('.card').getAttribute('crop_row');

        delete_data = {
            order_processes_subfile_id: parseInt(order_processes_subfile_id)
        };
        axios.delete(`${this.state.request_delete_rect}`, {
            data: delete_data
        })
            .then((response) => {
                let crop_data_temp = this.state.cropData;
                crop_data_temp.order_processes.splice(order_processes_row_index, 1)
                this.setState({
                    cropData: crop_data_temp,
                })
            })
            .catch((error) => console.log(error))
    }

    addRectData(cropData) {
        let search_temp = { ...this.state.Search };
        let file_id_array = cropData.background_src.split('/');
        const file_id_temp = file_id_array[file_id_array.length - 1];
        let cropData_temp = this.state.cropData;
        cropData_temp['order_processes'].push({
            file_id: file_id_temp,
            order_processes_subfile_id: cropData.order_processes_subfile_id,
            order_processes_reprocess_data_array: [],
        })
        cropData_temp['order_processes'].forEach((row, index) => {
            search_temp['id'] = row.order_processes_subfile_id;
            row['Search'] = [{ ...search_temp }];
        })
        this.setState({
            cropData: cropData_temp,
            same_pic: false,
        })
    }

    fetchUsers() {
        document.getElementById("updsubfile").click()
    }

    onImageChange = event => {
        let file = event.target.files[0];
        let type = event.target.files[0].type;
        this.UploadImg(file, type);
        file = '';
        type = '';
    };

    UploadImg = (file, type) => {
        console.log(this.state)
        var config = { responseType: 'blob' };
        // 到時候要是可調的，允許之型態
        const supportedFilesTypes = this.state.upload.allowType;
        if (supportedFilesTypes.indexOf(type) > -1) {
            var payload = new FormData();
            payload.append('inputFile', file);
            Object.keys(this.state.upload.request_data).map((key, i) => {
                payload.append(key, parseInt(this.state.upload.request_data[key]));
            })

            axios.post(this.state.request_add_subfile, payload, {
                // cancelToken: this.state.upload.source.token,
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
            }).then(response => {
                let cropDataTemp = { ...this.state.cropData };
                let return_data = {
                    file_id: parseInt(response.data.file_id),
                    order_processes_subfile_id: parseInt(response.data.order_processes_subfile_id),
                }
                cropDataTemp['order_processes'].push(return_data);
                this.setState(
                    {
                        cropData: cropDataTemp,
                        modal: {
                            modal_title: "上傳結果",
                            modal_body: "上傳成功",
                            show: true,
                            size: 'md',
                        },
                    }
                );
                this.callbackReturn();

                // this.subFileModal.current.openModal();
            })
            // controller.abort();
            this.setState({ enableDragDrop: false });
        }
        else {
            this.setState({ preview: null, status: "此檔案無法上傳，請再次點擊或拖拉至此", fetchSuccess: true });
        }
    }

    handleChange(data) {
        let search_temp = this.state.cropData.order_processes;
        Object.keys(search_temp).map((key, value) => {
            search_temp[key]['Search'] = search_temp[key]['Search'].map((row, index) => {
                if (row['id'] === parseInt(data.id)) {
                    row['value'] = data.value.trim() === '' ? null : data.value.trim();
                }
                return row;

            })
        })
        this.setState({
            Search: search_temp,
        })
    }

    subfileNoteSave(data) {
        const order_processes_subfile_id = parseInt(this.state.rectData.order_processes_subfile_id);
        let object = {};
        data.map((row, index) => {
            Object.keys(row).map((key, i) => {
                object[key] = row[key];
            })
        });
        object['order_processes_subfile_id'] = order_processes_subfile_id;
        let params = [object];

        axios
            .patch(`/3DConvert/PhaseGallery/order_processes/subfile_image`, params)
            .then((response) => {
            });
    }


    callbackReturn() {
        let modal_temp = { ...this.state.modal };
        setTimeout(function () {
            modal_temp['show'] = false;
            this.setState({
                modal: modal_temp,
            })
        }.bind(this), 1000)
    }

    render() {
        return (
            <>
                {
                    this.props.ParentData.crop_editable === true ?
                        <Row>
                            <Col md={6} xs={6}>
                                {this.props.ParentData.show_label !== undefined ?
                                    <Row>
                                        <Col style={{ color: "#545051", fontWeight: "bold" }}>製程名稱：{this.props.ParentData.show_label.line_name}-{this.props.ParentData.show_label.processes_name}</Col>
                                    </Row>
                                    : ''
                                }
                                <DynamicCrop ref={this.child}
                                    parentRow={this.props.ParentData}
                                    request={this.state.request}
                                    request_upload={this.state.request_upload}
                                    request_delete_crop={this.state.request_delete_crop}
                                    request_draw={this.state.request_draw}
                                    deleteCrop={this.props.deleteCrop}
                                    addRectData={this.addRectData}
                                    addCropData={this.props.addCropData}
                                />
                            </Col>
                            <Col md={6} xs={6}>
                                <Row className='mt-4'>
                                    <Col md='12' className="mb-2">
                                        <Button className="mx-2" variant="secondary" onClick={this.fetchUsers} style={{ width: 'auto', background: "#5e789f", color: "white", fontWeight: "bold" }}>上傳附圖</Button>
                                        {this.state.modal.show ? <Col style={{ color: "#B22222", fontWeight: "bold" }}>{this.state.modal.modal_body}</Col> : ''}
                                        <input id="updsubfile" type="file" ref={this.fileInputRef} onChange={(e) => this.onImageChange(e)} hidden />
                                    </Col>
                                </Row>
                                <Col className='overflow-auto d-flex'>
                                    {this.state.cropData && this.state.cropData.order_processes.map((row, index) => {
                                        return (
                                            <Col>
                                                <div className="card" key={`${row.order_processes_subfile_id}${index}`} deledata={row.order_processes_subfile_id} crop_row={index} style={{ width: "8rem" }}>
                                                    <Image alt='no image' className='card-img-top position-relative mt-3' src={`${axios.defaults.baseURL}/3DConvert/PhaseGallery/order_image/${row.file_id}`} crop_row={index} onClick={this.changeRect}></Image>
                                                    <GiCancel className="icon position-absolute" onClick={this.deleteImg} style={{ top: 0, right: 0 }} />
                                                </div>
                                            </Col>
                                        )
                                    })}
                                </Col>
                                <Row >
                                    <Complaint request_data={this.state.rectData} drawRectArea={true} same_pic={this.state.same_pic} area_left_editable={this.props.ParentData.crop_editable} change_opr_subfile_code={this.subfileNoteSave}></Complaint>
                                </Row>
                            </Col>
                        </Row>

                        :
                        <Row>
                            <Col md={6} xs={6} hidden>
                                {this.props.ParentData.show_label !== undefined ?
                                    <Row>
                                        <Col style={{ color: "#545051", fontWeight: "bold" }}>製程名稱：{this.props.ParentData.show_label.line_name}-{this.props.ParentData.show_label.processes_name}</Col>
                                    </Row>
                                    : ''
                                }
                                <DynamicCrop ref={this.child}
                                    parentRow={this.props.ParentData}
                                    request={this.state.request}
                                    request_upload={this.state.request_upload}
                                    request_delete_crop={this.state.request_delete_crop}
                                    request_draw={this.state.request_draw}
                                    deleteCrop={this.props.deleteCrop}
                                    addRectData={this.addRectData}
                                    addCropData={this.props.addCropData}
                                />
                            </Col>
                            <Col>
                                <Row className='mt-4'>
                                    <Col md='12' className="mb-2" hidden>
                                        <Button className="mx-2" variant="secondary" onClick={this.fetchUsers} style={{ width: 'auto', background: "#5e789f", color: "white", fontWeight: "bold" }}>上傳附圖</Button>
                                        {this.state.modal.show ? <Col style={{ color: "#B22222", fontWeight: "bold" }}>{this.state.modal.modal_body}</Col> : ''}
                                        <input id="updsubfile" type="file" ref={this.fileInputRef} onChange={(e) => this.onImageChange(e)} hidden />
                                    </Col>
                                </Row>
                                <Col className='overflow-auto d-flex'>
                                    {this.state.cropData && this.state.cropData.order_processes.map((row, index) => {
                                        return (
                                            <Col>
                                                <div className="card" key={`${row.order_processes_subfile_id}${index}`} deledata={row.order_processes_subfile_id} crop_row={index} style={{ width: "8rem" }}>
                                                    <Image alt='no image' className='card-img-top position-relative mt-3' src={`${axios.defaults.baseURL}/3DConvert/PhaseGallery/order_image/${row.file_id}`} crop_row={index} onClick={this.changeRect}></Image>
                                                    <GiCancel className="icon position-absolute" onClick={this.deleteImg} style={{ top: 0, right: 0 }} />
                                                </div>
                                            </Col>
                                        )
                                    })}
                                </Col>
                                <Row >
                                    <Complaint request_data={this.state.rectData} drawRectArea={true} same_pic={this.state.same_pic} area_left_editable={this.props.ParentData.crop_editable} change_opr_subfile_code={this.subfileNoteSave}></Complaint>
                                </Row>
                            </Col>
                        </Row>
                }

                < BasicModal
                    modal_title={this.state.modal.modal_title}
                    modal_body={this.state.modal.modal_body}
                    show={this.state.modal.show}
                    ref={this.subFileModal}
                    size={this.state.modal.size}
                ></BasicModal >

            </>
        )

    }
}

export default Gallery