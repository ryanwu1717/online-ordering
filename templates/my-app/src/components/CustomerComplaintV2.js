// Demo.jsx
import React from 'react';
import axios from 'axios';
import Axios from 'axios'
import 'bootstrap/dist/css/bootstrap.min.css';
import 'antd/dist/antd.css';
import Search from './Search';
import DrawRect from './DrawRect';
import { Card, Row, Spinner, Col, Button, ListGroup, FormControl, InputGroup, Container, FloatingLabel, Form, CloseButton } from 'react-bootstrap';
import BasicModal from '../components/BasicModal';
import Complaint from './Complaint';
import { Drawer, Tooltip } from 'antd';
import { BulbFilled } from '@ant-design/icons';
import moment from 'moment';
import './CustomerComplaintV2.css'

class CustomerComplaintV2 extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            complaint_id: 1,
            meet_id: 1,
            complaints: [],
            complaints_name: [],
            processes: [],
            image: [],
            modal: {
                show: false,
                modal_body: '',
                modal_footer: '',
            },
            Search: {
                Select_row1: [
                    { 'label': '客戶代號:', 'id': 'complaint_customer_id', 'type': 'input', 'value': '', 'disabled': false },
                    { 'label': '客戶圖號:', 'id': 'img_id', 'type': 'input', 'value': '', 'disabled': false },
                ],
            },
            comments: "",
            note: "",
            upload: {
                allowType: ['image/jpg', 'image/jpeg', 'image/png', 'video/mp4'],
                request_data: {
                    complaint_id: 1
                },
                API_location: "/CRM/complaint/complaint/upload",
                return_image: '',
                return_data: {},
                editable: null,
            },
            componentControl: {
                role: 3,
                file_exists: null,
            },
            cardImg: {},
            allowType: ['image/jpg', 'image/jpeg', 'image/png', 'video/mp4', 'application/pdf'],
            enableDragDrop: true,
            fetchSuccess: true,
            status: "將檔案拖放到這裡或點擊此處",
            preview: null,
            source: Axios.CancelToken.source(),
            img_src: null,
            brushColor: "",
            height: "",
            width: "",
            pictures: [
            ],
            drawCanvasData: null,
            request_data: {
                background_src: "",
                img_src: "",
                attach_file_id: "",
                api_location: '/CRM/complaint/attach_file/picture/frame',

            },
            recorder_user_name: '',
            user_name: '',
            user_id: "",
            complaintRef: [],
            complaint_date: new Date().getFullYear() + "-" + this.addz(((new Date().getMonth() + 1).toString()), 2) + "-" + this.addz((new Date().getDate().toString()), 2),
            today_date: new Date().getFullYear() + "-" + this.addz(((new Date().getMonth() + 1).toString()), 2) + "-" + this.addz((new Date().getDate().toString()), 2),
            modal: {
                show: false,
                modal_body: '',
                modal_footer: '',
            },
            subject: "",
            pre_complaint_id: "",
            img: "",
            first_click: true,
            edited: false,
            visible: false,
            showDrawRect: false,
            start: false,
            isLoading: true,
            edit_date: new Date().getFullYear() + "-" + (new Date().getMonth() + 1) + "-" + new Date().getDate(),
            edit_name: '',
            edit_id: '',
            current_edit_id: '',
            current_edit_name: '',
            text: `主旨為必填\n新增檔案請先儲存在上傳圖片`,
            saveShow: false,
        };
        this.child = React.createRef();
        this.modalRef = React.createRef();
        this.fileInputRef = React.createRef(null);
        this.canvasRef = React.createRef(null);
        this.searchRef = React.createRef(null);
        this.subjectRef = React.createRef(null);
        this.warningRef = React.createRef(null);
        this.addRef = React.createRef(null);
        this.onImageChange = this.onImageChange.bind(this);
        this.handleSelected = this.handleSelected.bind(this);
        this.handleSave = this.handleSave.bind(this);
        this.handleModalSave = this.handleModalSave.bind(this);
        this.handleModalClose = this.handleModalClose.bind(this);
        this.handleCreate = this.handleCreate.bind(this);
        this.getComplaint = this.getComplaint.bind(this);
        this.handleReport = this.handleReport.bind(this);
        this.deleteImg = this.deleteImg.bind(this);

    }
    handleSearchChange = (event) => {
        let select_row = this.state.Search.Select_row1;
        console.log(event)
        select_row.map((value, index) => (
            value.id === event.id ? value.value = event.value : null
        ))
        let search = {
            Select_row1: select_row
        }
        this.setState({
            Search: search,
            edited: true
        })
    }
    addz(num, length) {
        if (num.length >= length) { return num }
        else {
            return this.addz(("0" + num), length)
        }
    }
    UploadImg = (file, type) => {
        var config = { responseType: 'blob' };
        this.setState({ fetchSuccess: false })
        // 到時候要是可調的，允許之型態
        const supportedFilesTypes = this.state.allowType;
        console.log(type)
        if (supportedFilesTypes.indexOf(type) > -1) {
            // Begin Reading File
            var image = new Image();
            image.src = file;
            const reader = new FileReader();
            reader.onload = e => this.setState({
                preview: e.target.result,
            });
            reader.readAsDataURL(file);
            // Create Form Data
            var payload = new FormData();
            payload.append('inputFile', file);
            Object.entries(this.state.upload.request_data).map(([key, item]) => {
                payload.append(key, item);
            })

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

                            this.setState({ fetchSuccess: true });
                        }, 750); // To match the transition 500 / 250
                    } else {
                        this.setState({ status: `${perc}%` });
                    }
                    this.setState({ percentage: perc });
                }
            }).then(response => {
                let picture = this.state.pictures;
                response.data.map((value, index) => (
                    picture.push({ src: value.id })
                ))

                this.setState({ pictures: picture });
            })
            // controller.abort();

        }
        else {
            this.setState({ preview: null, status: "此檔案無法上傳，請再次點擊或拖拉至此", fetchSuccess: true });
        }
    }
    onImageChange = event => {
        let file = event.target.files[0];
        let type = event.target.files[0].type;
        this.UploadImg(file, type);
        event.target.value = ""
    };

    handleChange = (e) => {
        let arr = this.state.note;
        arr[e.target.attributes.idx.value] = e.valu;
        this.setState({
            note: arr
        });
    }

    setColor = (e) => {
        this.setState({
            brushColor: e.target.value
        });
        console.log(e.target.value)
    }

    clickUpd = (e) => {
        this.fileInputRef.current.click()
    }

    setImg = (e) => {
        // this.setState({img_src: e.target.attributes.src.value});
        let request_data = this.state.request_data;
        request_data.background_src = e.target.attributes.src.value
        this.setState({ request_data: request_data });
        this.setState({ showDrawRect: true });
        this.setState({ img: e.target.attributes.img_id.value });

        axios.get(`/CRM/complaint/attach_file/picture/paint`, {
            params: { attach_file_id: e.target.attributes.img_id.value },
            // params: {delivery_meet_content_file_id: 120},
        })
            .then((response) => {
                let request_data = this.state.request_data;
                request_data.background_src = `${axios.defaults.baseURL}/CRM/complaint/complaint/attach_file/${e.target.attributes.img_id.value}`
                request_data.attach_file_id = e.target.attributes.img_id.value
                let file_id = [];
                response.data.map((value, index) => (
                    file_id.push(value.file_id)
                ))
                request_data.file_id = file_id
                console.log(this.state.request_data.file_id)
                // let request_data = {
                //     attach_file_id: e.target.attributes.img_id.value,
                //     background_src: 
                // }
                this.setState({ request_data: request_data });
            });
    }

    handleCommentsChange = (e) => {
        this.setState({
            [e.target.id]: e.target.value,
            edited: true
        });
    }

    handleSelected = (e) => {
        if (this.state.subject === '' && !this.state.first_click) {
            Object.assign(this.subjectRef.current.style, { borderColor: "red" })
            Object.assign(this.warningRef.current.style, { display: "block" })

        } else {
            this.setState({ showDrawRect: false });
            this.state.complaints.map((value, index) => (
                this.state.complaintRef[index].current.classList.remove("hadSlected")
            ))
            e.target.classList.add("hadSlected");
            this.state.complaints.map((value, index) => (
                Object.assign(this.state.complaintRef[index].current.style, { textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: "nowrap", width: '300px', maxWidth: '300px', height: "50px", fontSize: "18px", fontWeight: "bold", background: "white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium" })
            ))
            Object.assign(e.target.style, { textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: "nowrap", width: '300px', maxWidth: '300px', background: "#5e789f", color: "white", borderColor: "#5e789f" });
            this.setState({ next_complaint_id: e.target.attributes.complaint_id.value })
            this.setState({ first_click: false })
            if (this.state.edited) {
                this.setState({
                    modal: {
                        modal_body: "請問是否儲存變更?",
                        modal_footer:
                            <>
                                <Button
                                    variant="light"
                                    onClick={this.handleModalSave}
                                    style={{ background: "#5e789f", color: "white", fontWeight: "bold" }}>
                                    儲存變更
                                </Button>
                                <Button
                                    variant="light"
                                    style={{ background: "#858796", color: "white", fontWeight: "bold" }}
                                    onClick={this.handleModalClose}>
                                    捨棄變更
                                </Button>
                            </>,
                        show: true,
                    }
                });
                this.modalRef.current.openModal();
            } else {

                axios.get(`/CRM/complaint/content`, {
                    params: { complaint_id: e.target.attributes.complaint_id.value },
                })
                    .then((response) => {
                        if (response.data.length === 0) {
                            this.setState({
                                subject: '',
                                comments: '',
                            })
                            let temp = this.state.Search.Select_row1;
                            temp.map((value, index) => (
                                value['value'] = ''
                            ))
                            let search_temp = {
                                Select_row1: temp
                            }
                            this.setState({ Search: search_temp })
                            let pictures = [];
                            this.setState({ pictures: pictures })
                        } else {
                            this.setState({
                                subject: response.data[0].subject || '',
                                comments: response.data[0].content || '',
                                complaint_date: response.data[0].complaint_date === null ? '' : response.data[0].complaint_date.split(" ")[0],
                                user_name: response.data[0].name || '',
                                edit_date: response.data[0].edit_date === null ? '' : moment(response.data[0].edit_date).format('YYYY-MM-DD'),
                                edit_name: response.data[0].edit_user_name || '',
                            })
                            let temp = this.state.Search.Select_row1;
                            temp.map((value, index) => (
                                value['value'] = response.data[0][value.id] || ''
                            ))
                            let search_temp = {
                                Select_row1: temp
                            }
                            this.setState({ Search: search_temp })
                            let pictures = [];
                            response.data[0].files.map((value, index) => (
                                pictures.push({ src: value.file_id })
                            ))
                            this.setState({ pictures: pictures })
                        }

                    });
                this.setState({ complaint_id: e.target.attributes.complaint_id.value })
                let upload = this.state.upload;
                let upload_request = this.state.upload.request_data;
                upload_request['complaint_id'] = e.target.attributes.complaint_id.value
                upload['request_data'] = upload_request;
                this.setState({ upload: upload })
            }
        }
    }
    handleSave = (e) => {
        if (this.state.subject === '') {
            Object.assign(this.subjectRef.current.style, { borderColor: "red" })
            Object.assign(this.warningRef.current.style, { display: "block" })

        } else {
            Object.assign(this.subjectRef.current.style, { borderColor: "#ced4da" })
            Object.assign(this.warningRef.current.style, { display: "none" })
            let data = {};
            let temp = this.state.Search.Select_row1;
            let search_temp = {
                Select_row1: temp
            }
            this.setState({ Search: search_temp })

            if (parseInt(this.state.complaint_id, 10) !== 0) {
                this.state.Search.Select_row1.map((value, index) => (
                    data[value.id] = value.value
                ))
                data['subject'] = this.state.subject;
                data['content'] = this.state.comments;
                data['complaint_id'] = parseInt(this.state.complaint_id, 10);
                data['edit_date'] = moment(this.state.today_date).format('YYYY-MM-DD');
                // real data
                // data['edit_user_id'] = this.state.edit_id

                // fake data
                data['edit_user_id'] = 8

                let row = this;

                axios.post(`/CRM/complaint/content`,
                    data
                ).then((res) => {
                    row.setState({ saveShow: true })
                    setTimeout(function () {
                        row.setState({ saveShow: false })
                    }, 2000);
                });

            } else {

                data['subject'] = this.state.subject;
                data['content'] = this.state.comments;
                data['complaint_date'] = this.state.today_date;
                data['edit_date'] = moment(this.state.today_date).format('YYYY-MM-DD');


                // real data
                // data['user_id'] = this.state.user_id
                // data['edit_user_id'] = this.state.edit_id

                // fake data
                data['user_id'] = 8
                data['edit_user_id'] = 8

                this.state.Search.Select_row1.map((value, index) => (
                    data[value.id] = value.value
                ))
                axios.post(`/CRM/complaint/content/new_complaint`,
                    data
                ).then((res) => {
                    this.setState({ complaint_id: res.data.complaint_id })
                    let upload = this.state.upload;
                    let upload_request = this.state.upload.request_data;
                    upload_request['complaint_id'] = res.data.complaint_id
                    upload['request_data'] = upload_request;
                    this.setState({ upload: upload });
                    let complaints = this.state.complaints
                    complaints[complaints.length - 1] = res.data.complaint_id
                    this.setState({ complaints: complaints })
                    this.setState({ saveShow: true })
                    let row = this;
                    setTimeout(function () {
                        row.setState({ saveShow: false })
                    }, 2000);
                });
            }
            let complaints_name = this.state.complaints_name
            console.log(this.state.complaint_id)
            console.log(this.state.complaints)
            complaints_name[this.state.complaints.indexOf(parseInt(this.state.complaint_id, 10))] = this.state.subject
            this.setState({ complaints_name: complaints_name })
            this.setState({ edited: false })
        }
    }
    getComplaint = (e) => {
        axios.get(`/CRM/complaint/content`, {
            params: { complaint_id: this.state.next_complaint_id },
        })
            .then((response) => {
                if (response.data.length === 0) {
                    this.setState({
                        subject: '',
                        comments: '',
                    })
                    let temp = this.state.Search.Select_row1;
                    temp.map((value, index) => (
                        value['value'] = ''
                    ))
                    let search_temp = {
                        Select_row1: temp
                    }
                    this.setState({ Search: search_temp })
                } else {

                    this.setState({
                        subject: response.data[0].subject || '',
                        comments: response.data[0].content || '',
                        complaint_date: response.data[0].complaint_date === null ? '' : response.data[0].complaint_date.split(" ")[0],
                        user_name: response.data[0].name || '',
                        user_id: response.data[0].user_id,
                        edit_id: response.data[0].edit_user_id,
                        edit_date: response.data[0].edit_date === null ? '' : moment(this.state.edit_date).format('YYYY-MM-DD'),
                        edit_name: response.data[0].edit_user_name,
                    })
                    let temp = this.state.Search.Select_row1;
                    temp.map((value, index) => (
                        value['value'] = response.data[0][value.id] || ''
                    ))
                    let search_temp = {
                        Select_row1: temp
                    }
                    this.setState({ Search: search_temp })
                    let pictures = [];
                    response.data[0].files.map((value, index) => (
                        pictures.push({ src: value.file_id })
                    ))
                    this.setState({ pictures: pictures })
                }
                this.setState({ complaint_id: this.state.next_complaint_id })

            });
    }
    handleModalSave = (e) => {
        this.handleSave();
        this.modalRef.current.closeModal();
        this.getComplaint();
    }

    handleModalClose = (e) => {
        this.modalRef.current.closeModal();
        this.setState({ edited: false })
        this.getComplaint();
    }

    handleCreate = (e) => {
        if (this.state.complaint_id !== "0") {
            let complaints = this.state.complaints;
            complaints.push(0);
            this.setState({ complaints: complaints })

            let complaints_name = this.state.complaints_name;
            complaints_name.push('[尚未儲存案件]');
            this.setState({ complaints_name: complaints_name })

            let ref_arr = [...this.state.complaintRef]
            ref_arr.push(React.createRef())
            this.setState({
                complaintRef: ref_arr,
            });
            let pictures = [];
            let today_date = this.state.today_date;
            let user_name = this.state.user_name;
            this.setState({
                pictures: pictures,
                complaint_date: today_date,
                user_name: user_name,
                edit_name: this.state.edit_name,
                edit_date: today_date,
            })
            let row = this;
            this.setState({
                edit_date: moment(this.state.today_date).format('YYYY-MM-DD'),
                edit_id: this.state.current_edit_id,
                edit_name: this.state.current_edit_name,
            })
            setTimeout(function () {
                row.state.complaintRef[row.state.complaintRef.length - 1].current.click()
            }, 50);
        }
    }

    handleReport = (e) => {
        let url = `/CRM/complaint/qualityForm/${this.state.complaint_id}`;
        window.open(url, '_blank');
    }

    deleteImg = (e) => {

        let pictures = this.state.pictures;
        pictures.splice(e.target.attributes.idx.value, 1);
        this.setState({ pictures: pictures });

        axios.delete(`/CRM/complaint/complaint/attach_file`,
            { data: { file_id: e.target.attributes.file_id.value } }
        ).then((response) => {

        });
    }

    onClose = (e) => {
        this.setState({ visible: false })
    }

    openDrawer = (e) => {
        this.setState({ visible: true })
    }

    componentDidMount() {
        axios.get(`/CRM/user`)
            .then((response) => {
                let user = response.data[0]
                this.setState({
                    recorder_user_name: user.name,
                    user_name: user.name,
                    user_id: user.id,
                    edit_name: user.name,
                    edit_id: user.id,
                    current_edit_id: user.id,
                    current_edit_name: user.name,
                });

            });

        let params = {
            cur_page: 1,
            size: 0
        }
        axios.get(`/CRM/complaint/complaint`, {
            params: params,
        })
            .then((response) => {
                let arr = []
                let name = [];
                response.data.map((value, index) => (
                    arr.push(value.complaint_id)
                ))
                response.data.map((value, index) => (
                    name.push(value.subject)
                ))
                this.setState({
                    complaints: arr,
                    complaints_name: name,
                });
                let ref_arr = []
                this.state.complaints.map((value, index) => (
                    ref_arr.push(React.createRef())
                ))
                this.setState({
                    complaintRef: ref_arr,
                });
                let row = this;

                setTimeout(function () {
                    row.setState({ visible: true });
                    row.setState({ visible: false });
                    let complaint_id = window.location.href.split('/')[window.location.href.split('/').length - 1]
                    complaint_id === '0' ? row.addRef.current.click() : row.state.complaintRef[row.state.complaints.indexOf(parseInt(complaint_id))].current.click()

                }, 50);
                setTimeout(function () {
                    row.setState({ isLoading: false })
                }, 100);
            });

    }


    render() {
        return (
            <Container fluid>
                {this.state.isLoading ?
                    <div style={{ position: 'absolute', left: -1, top: -1, zIndex: 2, width: '101%', height: '101%', backgroundColor: '#00000080' }}>
                        <Spinner animation="border" variant="light" style={{ position: 'absolute', left: '50%', top: '50%' }} />
                    </div> : ''
                }
                <BasicModal
                    modal_title="訊息"
                    modal_body={this.state.modal.modal_body}
                    modal_footer={this.state.modal.modal_footer}
                    show={this.state.modal.show}
                    size="md"
                    height="280px"
                    close_button={false}
                    ref={this.modalRef}
                ></BasicModal>
                <Drawer title="歷史案件" placement="right" onClose={this.onClose.bind(this)} visible={this.state.visible}>

                    {this.state.complaints.map((value, index) => (
                        <Button className="mx-2 my-1" complaint_id={value} ref={this.state.complaintRef[index]} onClick={this.handleSelected} variant="light" style={{ textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: "nowrap", width: '300px', maxWidth: '300px', height: "50px", fontSize: "18px", fontWeight: "bold", background: "white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium" }}>{this.state.complaints_name[index]}</Button>

                    ))
                    }
                </Drawer>
                <Card className="mx-2 my-1">
                    <Row>
                        <Col md="auto">
                            <Card.Title md='12' as="h3" className='mb-3'>
                                <span className="badge rounded rfid_title p-3 text-center ">客訴內容</span>
                            </Card.Title>
                        </Col>
                        <Col>
                            <Tooltip className='my-2' placement="rightTop" title={this.state.text}>
                                <BulbFilled className='my-2 bulb' />
                            </Tooltip>
                        </Col>
                    </Row>
                    <Card.Body>

                        <Row style={{ display: 'flex', justifyContent: 'right' }}>
                            <Col md="3">
                                <InputGroup className="mb-3">
                                    <InputGroup.Text>修改日期</InputGroup.Text>
                                    <FormControl
                                        aria-describedby="inputGroup-sizing-default"
                                        disabled
                                        value={this.state.edit_date}
                                        style={{ background: "white" }}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md="3">
                                <InputGroup className="mb-3">
                                    <InputGroup.Text>修改者</InputGroup.Text>
                                    <FormControl
                                        aria-describedby="inputGroup-sizing-default"
                                        disabled
                                        style={{ background: "white", }}
                                        value={this.state.edit_name}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md="3">
                                <InputGroup className="mb-3">
                                    <InputGroup.Text>建立日期</InputGroup.Text>
                                    <FormControl
                                        aria-describedby="inputGroup-sizing-default"
                                        disabled
                                        value={this.state.complaint_date}
                                        style={{ background: "white" }}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md="3">
                                <InputGroup className="mb-3">
                                    <InputGroup.Text>建立者</InputGroup.Text>
                                    <FormControl
                                        aria-describedby="inputGroup-sizing-default"
                                        disabled
                                        style={{ background: "white", }}
                                        value={this.state.user_name}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                        <Row style={{ display: 'flex', justifyContent: 'right' }}>
                            <Col md='auto'>
                                <Button className="mx-2 my-2" ref={this.addRef} onClick={this.handleCreate} variant="light" style={{ fontWeight: "bold", background: "#7B84A0", color: "white", }}>新增案件</Button>
                                <Button className="mx-2 my-2" onClick={this.openDrawer.bind(this)} variant="light" style={{ fontWeight: "bold", background: "#7DC0BC", color: "white", }}>歷史案件</Button>
                            </Col>
                        </Row>
                        <Row>
                            <Col md="4">
                                <Row>
                                    <InputGroup style={{ height: '57px' }} >
                                        <InputGroup.Text>主旨</InputGroup.Text>
                                        <FormControl
                                            style={{ height: '57px' }}
                                            placeholder="必填*"
                                            aria-describedby="inputGroup-sizing-default"
                                            value={this.state.subject}
                                            id='subject'
                                            onChange={this.handleCommentsChange.bind(this)}
                                            ref={this.subjectRef}
                                        />
                                    </InputGroup>
                                </Row>
                                <Row>
                                    <h6 className='ml-4 my-2' ref={this.warningRef} style={{ color: 'red', display: 'none' }}>此欄位必填*</h6>
                                </Row>
                            </Col>
                            <Search ref={this.searchRef} resetData={this.handleSearchChange.bind(this)} name={this.state.Search.Select_row1}></Search>


                            <Col className="my-2">
                                <Button className="mx-2" variant="light" onClick={this.handleSave} style={{ fontWeight: "bold", background: "#ffbc00", color: "white", }}>儲存</Button>
                                <Button className="mx-2" onClick={this.handleReport} variant="light" style={{ width: 'auto', fontWeight: "bold", background: "#E85559", color: "white", }}>{'前往處理單>>'}</Button>
                                {/* <Button className="mx-2" output="csv" onClick={this.handleReport} variant="light" style={{width:'auto', fontWeight: "bold", background:"#7ea584", color: "white", }}>CSV</Button>
                            <Button className="mx-2" output="pdf" onClick={this.handleReport} variant="light" style={{width:'auto', fontWeight: "bold", background:"#E85559", color: "white", }}>PDF</Button> */}
                            </Col>
                        </Row>
                        <Row>
                            <label style={{ display: this.state.saveShow ? 'block' : 'none', fontWeight: 'bold', textAlign: 'right', color: 'red' }}>已儲存</label>
                        </Row>
                        <Row>
                            <Col md='8'>
                                <Row>
                                    <FloatingLabel label="客訴內容:">
                                        <Form.Control
                                            as="textarea"
                                            style={{ height: '120px' }}
                                            autoComplete="off"
                                            value={this.state.comments}
                                            id="comments"
                                            onChange={this.handleCommentsChange.bind(this)}
                                        />
                                    </FloatingLabel>
                                </Row>
                            </Col>
                        </Row>
                        {(parseInt(this.state.complaint_id) !== 0) ?
                            <div>
                                <hr />
                                <Row>
                                    <Button className="mx-2" variant="light" onClick={this.clickUpd.bind(this)} style={{ width: 'auto', fontWeight: "bold", background: "#7B84A0", color: "white", }}>上傳檔案</Button>
                                    <input style={{ display: 'none' }} type="file" ref={this.fileInputRef} onChange={(e) => this.onImageChange(e)} />
                                </Row>
                                <Row>
                                    <div style={{ display: 'flex', overflowX: 'scroll', overflowY: 'scroll', height: 350, whiteSpace: 'nowrap' }}>
                                        {this.state.pictures.map((value, index) => (
                                            <>
                                                <img
                                                    img_id={value.src}
                                                    src={`${axios.defaults.baseURL}/CRM/complaint/complaint/attach_file/${value.src}`}
                                                    alt={value.alt}
                                                    onClick={this.setImg.bind(this)}
                                                    className="mx-2 my-2"
                                                    style={{ width: 400, border: "1px solid #a39e9e", cursor: "pointer" }}
                                                />
                                                <CloseButton onClick={this.deleteImg} idx={index} file_id={value.src} style={{ position: 'relative', padding: '0rem 1rem', right: '4%', top: '4%' }} />
                                            </>
                                        ))
                                        }
                                    </div>
                                </Row>
                                <Row>
                                    <Col md='12'>
                                        <Row className='my-2'>
                                            {this.state.showDrawRect ?
                                                <Complaint request_data={this.state.request_data} ref={this.child} />
                                                : ''
                                            }
                                        </Row>
                                    </Col>
                                </Row>
                            </div> : ''
                        }
                    </Card.Body>
                </Card>
            </Container>
        );
    }
}

export default CustomerComplaintV2