import { Card, Row, Col, Button, Accordion } from "react-bootstrap";
import SERACH from '../components/Search';
import UploadFile from '../components/Upload_copy';
import Gallery from '../components/Gallery';
import DatatableCard from '../components/DatatableCard';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'antd/dist/antd.css';
import { DatePicker, Space } from 'antd';
import moment from 'moment';
import React from "react";
import axios from 'axios';
import TechnicalModificationProcess from "./TechnicalModificationProcess";

class Phasegallery extends React.Component {
    constructor(props) {
        super(props);
        let todayDate = this.getNowDate();
        this.state = (
            {
                Search: {
                    Select_row1: [
                        { 'label': '單別:', 'id': 'coptd_td001', 'type': 'input' },
                        { 'label': '單號:', 'id': 'coptd_td002', 'type': 'input' },
                        { 'label': '序號:', 'id': 'coptd_td003', 'type': 'input' },
                        { 'label': '客戶代號:', 'id': 'coptc_tc003', 'type': 'input' }
                    ],
                    Select_row2: [
                        { 'label': '起始日期:', 'id': 'date_begin', 'type': 'date', 'value': todayDate },
                        { 'label': '迄止日期:', 'id': 'date_end', 'type': 'date', 'value': todayDate },
                    ]
                },
                datatables: {
                    require: {
                        coptd_td001: null,
                        coptd_td002: null,
                        coptd_td003: null,
                        coptc_tc003: null,
                        date_begin: todayDate,
                        date_end: todayDate,
                        row_size: null,
                        have_image: null,
                    },
                    request_label: {
                        coptd_td001: '單別',
                        coptd_td002: '單號',
                        coptd_td003: '序號',
                        coptc_tc003: '客戶代號',
                    },
                    request: '/3DConvert/PhaseGallery/order',
                    return_data: {

                    }
                },
                upload: {
                    allowType: ['image/jpg', 'image/jpeg', 'image/png', 'video/mp4', 'application/pdf', 'application/vnd.ms-excel'],
                    request_data: {},
                    API_location: "/3DConvert/PhaseGallery/order_image",
                    return_image: '',
                    return_data: {},
                    editable: null,
                    temp_place: {},
                    setting: {
                        fetchSuccess: true,
                        status: "將檔案拖放到這裡或點擊此處",
                        enableDragDrop: true,
                        file_exists: false,
                    },
                },
                process: {
                    Process_API_location_processes: "/3DConvert/PhaseGallery/processes",
                    editable: null,
                },
                componentControl: {
                    role: 3,
                    file_exists: null,
                },
                gallery: {
                    Process_API_location_processes: "/3DConvert/PhaseGallery/order_processes/series",
                    Process_API_location_crop: "/3DConvert/PhaseGallery/order_processes/reprocess",
                    Process_API_location_crop_delete: "/3DConvert/PhaseGallery/order_processes/reprocess_image",
                    Process_API_location_rect_delete: "/3DConvert/PhaseGallery/order_processes/subfile_image",
                    Process_API_location_subfile_add: "/3DConvert/PhaseGallery/order_processes/subfile_image/upload",
                    editable: null,
                    processes_name: '',
                    old_src: '',
                },
                cardImg: {},
                anchor: {
                    target: 'upload',
                    behavior: 'smooth',
                },
            }
        )
        this.child_Search = React.createRef();
        this.child_PhaseGalleryComponentSwitch_TechnicalModificationProcess = React.createRef();
        this.child_PhaseGalleryComponentSwitch_Gallery = React.createRef();
        this.child_PhaseGalleryComponentSwitch_Upload = React.createRef();
        this.child_processGroup = React.createRef();
        this.onSearchClick = this.onSearchClick.bind(this);
        this.handleChange = this.handleChange.bind(this);
        this.imgTransport = this.imgTransport.bind(this);
        this.getNowDate = this.getNowDate.bind(this);
        this.resetPic = this.resetPic.bind(this);
        this.PerProcessImg = this.PerProcessImg.bind(this);
        this.onDatatablesReponse = this.onDatatablesReponse.bind(this);
        this.customizeCardGrandParent = this.customizeCardGrandParent.bind(this);
        this.deleteRec = this.deleteRec.bind(this);
        this.deleteCrop = this.deleteCrop.bind(this);
        this.addCropData = this.addCropData.bind(this);
        this.dataTableImageValid = this.dataTableImageValid.bind(this);
        this.range = this.range.bind(this);
        this.date_change = this.date_change.bind(this);
    }

    componentDidMount() {
        let upload_temp = this.state.upload;
        let process_temp = this.state.process;

        if (this.state.componentControl.role === 3) {
            upload_temp['editable'] = true;
            process_temp['editable'] = false;
        } else {
            upload_temp['editable'] = false;
            process_temp['editable'] = true;
        }
        this.setState({
            upload: upload_temp,
            process: process_temp,
        })
    }

    handleChange(data) {
        const temp_datatabls = this.state.datatables.require;
        const request = this.state.datatables.request;
        let upload_temp = this.state.upload;
        let search_temp = this.state.Search;
        Object.keys(temp_datatabls).map((key, value) => {
            if (key === data.id) {
                temp_datatabls[key] = data.value.trim() === '' ? null : data.value.trim();
                return;
            }
        })
        Object.keys(search_temp).map((key, value) => {
            search_temp[key].map((row, index) => {
                if (row['id'] === data.id) {
                    row['value'] = data.value.trim() === '' ? null : data.value.trim();
                    return;
                }
            })
        })
        upload_temp['request_data'] = temp_datatabls;
        this.setState({
            Search: search_temp,
            datatables: {
                require: temp_datatabls,
                request: request,
                request_label: this.state.datatables.request_label,
            },
            upload: upload_temp,
        })
    }

    onSearchClick() {
        this.child_Search.current.fetchUsers();
    }

    onDatatablesReponse(response) {
        let datatable_temp = this.state.datatables;
        datatable_temp['return_data'] = response.data.data;
        this.setState({
            datatables: datatable_temp,
        })
        return response;
    }

    getNowDate = () => {
        const date = new Date();
        let formatted_date = `${date.getFullYear()}-${(date.getMonth() + 1 < 10 ? `0${date.getMonth() + 1}` : date.getMonth() + 1)}-${(date.getDate() + 1 < 10 ? `0${date.getDate()}` : date.getDate())}`
        return formatted_date;
    }

    customizeCardGrandParent(response) {
        let data = {};
        let return_package = {};
        response.map((row, i) => {
            if (i === response.req_id) {
                if (row.row_id !== undefined) {
                    row.splice(0, 1);
                }
                if (row.req_id !== undefined) {
                    row.splice(0, 1);
                }
                Object.keys(row).map((key) => {
                    if (key !== "src" && key !== "file_exists" && key !== 'order_id' && key !== 'row_id' && key !== 'src_file_id') {
                        data[key] = row[key];
                    }
                })
                return_package['datas'] = data;
                return_package['image_temp'] = row.src;
                return_package['image_temp_file_id'] = row.src_file_id;
                return_package['file_exists'] = row.file_exists;
                return_package['order_id'] = row.order_id;
                // return_package['order_id'] = 1; //爛資料
                return_package['row_id'] = response.row_id;
                return_package['col_id'] = response.req_id;
                return_package['image'] = [];
            }
        })
        return return_package;
    }

    imgTransport(child_obj, cardImg) {
        console.log('imgTransport');
        let upload_temp = this.state.upload;
        let componentControl_temp = this.state.componentControl;
        let process_temp = this.state.process;
        let gallery_temp = { ...this.state.gallery };
        if (child_obj.file_exists === true) {
            upload_temp['return_image'] = child_obj.image[0];
            upload_temp['setting']['file_exists'] = child_obj.file_exists;
        }
        upload_temp['return_data'] = child_obj.datas;
        upload_temp['label'] = this.state.datatables.request_label;
        upload_temp['temp_place'] = {
            row: child_obj.row_id,
            col: child_obj.col_id
        }
        componentControl_temp['file_exists'] = child_obj.file_exists;
        process_temp['order_id'] = child_obj['order_id'];

        gallery_temp['editable'] = false;

        this.setState({
            componentControl: componentControl_temp,
            upload: upload_temp,
            process: process_temp,
            gallery: gallery_temp,
            cardImg: cardImg,
            anchor: {
                target: 'upload',
                behavior: 'smooth',
            },
        })
        //假資料
        if (this.state.componentControl.role === 4) {
            this.PerProcessImg(
                {
                    line_id: "A",
                    line_index: 1,
                    line_name: "A)車床組",
                    processes: [{
                        note: 12,
                        order_processes_id: 32,
                        processes_id: 201,
                        processes_index: 2,
                        processes_name: "粗車"
                    }],
                }
            );
        }
        this.child_PhaseGalleryComponentSwitch_TechnicalModificationProcess.current.returnViewComponent();
    }

    sendToCrop(dataToCrop) {
        console.log(dataToCrop);
    }

    resetPic(child_obj) {
        console.log('resetPic');
        let upload_temp = JSON.parse(JSON.stringify(this.state.upload));
        let componentControl_temp = this.state.componentControl;
        upload_temp['return_image'] = child_obj.preview;
        upload_temp['return_image_file_id'] = child_obj.preview_file_id;
        upload_temp['file_exists'] = child_obj.file_exists;
        componentControl_temp['file_exists'] = child_obj.file_exists;
        this.setState({
            componentControl: componentControl_temp,
            upload: upload_temp,
        }, this.child_Search.current.sync_pic(upload_temp))
    }

    PerProcessImg(process_data) {
        console.log('PerProcessImg');
        let gallery_temp = { ...this.state.gallery };
        let order_processes_id_arr = [];
        let param = {};
        if (this.state.componentControl.role === 3) {
            process_data['processes'].map((row, index) => {
                order_processes_id_arr.push(row.order_processes_id);
            })
            param = {
                order_processes_id: order_processes_id_arr,
            }
        } else {
            // 假資料
            param = {
                order_processes_id: [32],
            }
        }

        axios
            .get(this.state.gallery.Process_API_location_processes, {
                params: param,
            })
            .then((response) => {
                response.data.map((data, i) => {
                    Object.keys(data).map((key, i) => {
                        gallery_temp[key] = data[key];
                    })
                })
                gallery_temp['old_src'] = this.state.upload.return_image;
                gallery_temp['editable'] = true;
                this.state.componentControl.role === 3 ? gallery_temp['crop_editable'] = true : gallery_temp['crop_editable'] = false;
                gallery_temp['show_label'] = {
                    line_name: process_data['line_name'],
                    processes_name: process_data['processes'][0]['processes_name'],
                }
                this.setState(
                    {
                        gallery: gallery_temp
                    }
                );
                this.child_PhaseGalleryComponentSwitch_Gallery.current.returnGalleryComponent();

            })
            .catch(function (error) {
                console.log(error);
            });
    }

    deleteRec(row_index) {
        console.log(this.state.gallery)
    }

    deleteCrop(order_processes_file_id_temp, order_processes_reprocess_row_index) {
        let delete_data = [];
        let order_processes_file_temp = [...this.state.gallery.order_processes_file];
        order_processes_file_temp.splice(order_processes_reprocess_row_index, 1);
        delete_data.push({
            order_processes_file_id: order_processes_file_id_temp,
        })
        axios.delete(`${this.state.gallery.Process_API_location_crop_delete}`, {
            data: {
                data: delete_data
            }
        })
            .then((response) => {
                this.setState(prevState => ({
                    gallery: {
                        ...prevState.gallery,
                        'order_processes_file': order_processes_file_temp
                    }
                }));
            })
            .catch((error) => console.log(error))

    }

    addCropData(order_processes_file_temp) {
        let order_processes_file_data = [...this.state.gallery.order_processes_file];
        order_processes_file_data.push(order_processes_file_temp);
        this.setState(prevState => ({
            gallery: {
                ...prevState.gallery,
                'order_processes_file': order_processes_file_data
            }
        }));
    }

    dataTableImageValid(e) {
        let datatables_temp = { ...this.state.datatables };
        datatables_temp['require']['have_image'] = e.target.getAttribute(`have_image`);
        this.setState({
            datatables: datatables_temp
        }, () => {
            this.onSearchClick()
        })
    }

    range(start, end) {
        const result = [];
        for (let i = start; i < end; i++) {
            result.push(i);
        }
        return result;
    }

    date_change(date, dateString) {
        let start_time = dateString[0];
        let end_time = dateString[1];
        let search_temp = this.state.Search;
        let temp_datatabls = this.state.datatables.require;
        const request = this.state.datatables.request;
        search_temp['Select_row2'][0]['value'] = start_time;
        search_temp['Select_row2'][1]['value'] = end_time;
        temp_datatabls['date_begin'] = start_time;
        temp_datatabls['date_end'] = end_time;
        console.log(temp_datatabls)
        this.setState({
            Search: search_temp,
            datatables: {
                require: temp_datatabls,
                request: request,
                request_label: this.state.datatables.request_label,
            },
        });
        console.log(this.state.datatables)
    }

    render() {
        const { RangePicker } = DatePicker;
        return (
            <>
                <Card>
                    <Card.Body>
                        <Row className="g-4">
                            <SERACH resetData={this.handleChange} name={this.state.Search.Select_row1}></SERACH>
                        </Row >
                        < Row className="g-3 mt-2" >
                            <Col>
                                <nav aria-label="Page navigation example">
                                    <ul className="pagination">
                                        <li className="page-item"><a className="page-link btn-lg" href="#2" onClick={this.dataTableImageValid}>全部</a></li>
                                        <li className="page-item"><a className="page-link btn-lg" href="#3" have_image='1' onClick={this.dataTableImageValid}>有圖</a></li>
                                        <li className="page-item"><a className="page-link btn-lg" href="#4" have_image='0' onClick={this.dataTableImageValid}>無圖</a></li>
                                    </ul>
                                </nav>
                            </Col>
                            <Col>
                                <RangePicker onChange={this.date_change} size='large' defaultValue={[moment(this.state.Search.Select_row2[0].value), moment(this.state.Search.Select_row2[1].value)]} placeholder={[this.state.Search.Select_row2[0].label, this.state.Search.Select_row2[1].label]} />
                            </Col>
                            <Col>
                                <Button variant="info" size="md" onClick={this.onSearchClick}>搜尋</Button>
                            </Col>
                        </Row >
                    </Card.Body>
                    <DatatableCard className="mx-1" datatables={this.state.datatables} ref={this.child_Search} postProcess={this.onDatatablesReponse} customizeCardGrandParent={this.customizeCardGrandParent} imgTransport={this.imgTransport} />
                </Card >

                <PhaseGalleryComponentSwitch componentData={this.state.upload} componentUse='upload' control={this.state.componentControl} ref={this.child_PhaseGalleryComponentSwitch_Upload} resetPic={this.resetPic} anchor={this.state.anchor}></PhaseGalleryComponentSwitch>
                <PhaseGalleryComponentSwitch componentData={this.state.process} componentUse='process' control={this.state.componentControl} ref={this.child_PhaseGalleryComponentSwitch_TechnicalModificationProcess} sendToParentfunction={this.PerProcessImg}></PhaseGalleryComponentSwitch>
                <PhaseGalleryComponentSwitch componentData={this.state.processGroup} componentUse='processGroup' control={this.state.componentControl} ref={this.processGroup}></PhaseGalleryComponentSwitch>
                <PhaseGalleryComponentSwitch componentData={this.state.gallery} componentUse='gallery' control={this.state.componentControl} ref={this.child_PhaseGalleryComponentSwitch_Gallery} deleteCrop={this.deleteCrop} addCropData={this.addCropData}></PhaseGalleryComponentSwitch>
            </>
        )
    }
}

class PhaseGalleryComponentSwitch extends React.Component {
    constructor(props) {
        super(props);
        this.child_TechnicalModificationProcess = React.createRef();
        this.child_Gallery = React.createRef();
    }

    returnViewComponent(data) {
        if (this.child_TechnicalModificationProcess.current !== null) {
            this.child_TechnicalModificationProcess.current.returnViewComponentSwitch();
        }
    }

    returnGalleryComponent() {
        if (this.child_Gallery.current !== null) {
            this.child_Gallery.current.resetPic();
        }
    }

    render() {
        if (this.props.control.role === 3) {
            if (this.props.componentUse === 'upload') {
                if (this.props.control.file_exists === true) {
                    return (
                        <Card className="">
                            <Card.Body>
                                <Accordion defaultActiveKey={['0']} alwaysOpen>
                                    <Accordion.Item eventKey="0">
                                        <Accordion.Header>廠內圖</Accordion.Header>
                                        <Accordion.Body>
                                            <Row>
                                                {Object.keys(this.props.componentData.return_data).map((key, index) => {
                                                    return (
                                                        <Col>{this['props']['componentData']['label'][key]}: {this['props']['componentData']['return_data'][key]}</Col>
                                                    )
                                                })
                                                }
                                            </Row>
                                            <Row>
                                                <UploadFile
                                                    parentCallback={this.props.resetPic}
                                                    request_data={this.props.componentData.return_data}
                                                    allowType={this.props.componentData.allowType}
                                                    API_location={this.props.componentData.API_location}
                                                    file_exists={this.props.control.file_exists}
                                                    file_location={this.props.componentData.return_image}
                                                    ref={this.child_Upload}
                                                    anchor={this.props.anchor}
                                                />
                                            </Row>
                                        </Accordion.Body>
                                    </Accordion.Item>
                                </Accordion>
                            </Card.Body>
                        </Card>
                    )
                } else if (this.props.control.file_exists === false) {

                    return (
                        <Card className="">
                            <Card.Body>
                                <Accordion defaultActiveKey={['0']} alwaysOpen>
                                    <Accordion.Item eventKey="0">
                                        <Accordion.Header>廠內圖</Accordion.Header>
                                        <Accordion.Body>
                                            <Row>
                                                {Object.keys(this.props.componentData.return_data).map((key, index) => {
                                                    return (
                                                        <Col>{this['props']['componentData']['label'][key]}: {this['props']['componentData']['return_data'][key]}</Col>
                                                    )
                                                })
                                                }
                                            </Row>
                                            <Row>
                                                <UploadFile
                                                    parentCallback={this.props.resetPic}
                                                    request_data={this.props.componentData.return_data}
                                                    allowType={this.props.componentData.allowType}
                                                    API_location={this.props.componentData.API_location}
                                                    file_exists={this.props.control.file_exists}
                                                    file_location={this.props.componentData.return_image}
                                                    ref={this.child_Upload}
                                                    anchor={this.props.anchor}
                                                />
                                            </Row>
                                        </Accordion.Body>
                                    </Accordion.Item>
                                </Accordion>
                            </Card.Body>
                        </Card>
                    )
                }
                else {
                    return <></>
                }
            }
            else if (this.props.componentUse === 'process') {
                if (this.props.control.file_exists === true) {
                    return (
                        <Accordion>
                            <Accordion.Item>
                                <Accordion.Header style={{ background: "#98AEEE", color: "#4E5068", borderColor: "#577567", fontWeight: "bold" }}>製程資訊</Accordion.Header>
                                <Accordion.Body>
                                    <TechnicalModificationProcess API_location_processes={this.props.componentData.Process_API_location_processes}
                                        editable={this.props.componentData.editable} sendToParentfunction={this.props.sendToParentfunction} order_id={this.props.componentData.order_id} ref={this.child_TechnicalModificationProcess}></TechnicalModificationProcess>
                                </Accordion.Body>
                            </Accordion.Item>
                        </Accordion>
                    )
                } else {
                    return (
                        <></>
                    )
                }
            }
            else if (this.props.componentUse === 'gallery') {
                if (this.props.control.file_exists === true && this.props.componentData.editable) {
                    return (
                        <Accordion>
                            <Accordion.Item>
                                <Accordion.Header style={{ background: "#98AEEE", color: "#4E5068", borderColor: "#577567", fontWeight: "bold" }}>分層圖上傳</Accordion.Header>
                                <Accordion.Body>
                                    <Gallery ParentData={this.props.componentData} ref={this.child_Gallery} deleteCrop={this.props.deleteCrop} addCropData={this.props.addCropData}></Gallery>
                                </Accordion.Body>
                            </Accordion.Item>
                        </Accordion>
                    )
                } else {
                    return (
                        <></>
                    )
                }
            } else {
                return (
                    <></>
                )
            }
        } else if (this.props.control.role === 4) {
            if (this.props.componentUse === 'upload') {
                if (this.props.control.file_exists === true) {
                    return (
                        <Card className="">
                            <Card.Body>
                                <Accordion defaultActiveKey={['0']} alwaysOpen>
                                    <Accordion.Item eventKey="0">
                                        <Accordion.Header>廠內圖</Accordion.Header>
                                        <Accordion.Body>
                                            <Row>
                                                {Object.keys(this.props.componentData.return_data).map((key, index) => {
                                                    return (
                                                        <Col>{this['props']['componentData']['label'][key]}: {this['props']['componentData']['return_data'][key]}</Col>
                                                    )
                                                })
                                                }
                                            </Row>
                                            <Row>
                                                <UploadFile
                                                    parentCallback={this.props.resetPic}
                                                    request_data={this.props.componentData.return_data}
                                                    allowType={this.props.componentData.allowType}
                                                    API_location={this.props.componentData.API_location}
                                                    file_exists={this.props.control.file_exists}
                                                    file_location={this.props.componentData.return_image}
                                                    ref={this.child_Upload}
                                                    anchor={this.props.anchor}
                                                    editable={true}
                                                />
                                            </Row>
                                        </Accordion.Body>
                                    </Accordion.Item>
                                </Accordion>
                            </Card.Body>
                        </Card>
                    )
                }
                else {
                    return (
                        <></>
                    )
                }
            }
            else if (this.props.componentUse === 'process') {
                if (this.props.control.file_exists === true) {
                    return (
                        <Accordion >
                            <Accordion.Item>
                                <Accordion.Header style={{ background: "#98AEEE", color: "#4E5068", borderColor: "#577567", fontWeight: "bold" }}>製程資訊</Accordion.Header>
                                <Accordion.Body>
                                    <TechnicalModificationProcess API_location_processes={this.props.componentData.Process_API_location_processes}
                                        editable={this.props.componentData.editable} sendToCrop={this.sendToCrop}></TechnicalModificationProcess>
                                </Accordion.Body>
                            </Accordion.Item>
                        </Accordion >
                    )
                } else {
                    return (
                        <></>
                    )
                }
            }
            else if (this.props.componentUse === 'gallery') {
                if (this.props.control.file_exists === true) {
                    return (
                        <Accordion>
                            <Accordion.Item>
                                <Accordion.Header style={{ background: "#98AEEE", color: "#4E5068", borderColor: "#577567", fontWeight: "bold" }}>分層圖上傳</Accordion.Header>
                                <Accordion.Body>
                                    <Gallery ParentData={this.props.componentData} ref={this.child_Gallery} deleteCrop={this.props.deleteCrop} addCropData={this.props.addCropData}></Gallery>
                                </Accordion.Body>
                            </Accordion.Item>
                        </Accordion>
                    )
                } else {
                    return (
                        <></>
                    )
                }
            }
            else {
                return (
                    <></>
                )
            }
        } else {
            return (
                <></>
            )
        }
    }
}

export default Phasegallery