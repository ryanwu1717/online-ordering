// Demo.jsx
import React from 'react';
import axios from 'axios';
import Axios from 'axios'
import 'bootstrap/dist/css/bootstrap.min.css';
import Search from './Search';
import DrawRect from './DrawRect';
import { Card, Row, Col, Button,FormControl,InputGroup, Container, FloatingLabel, Form } from 'react-bootstrap';
import BasicModal from '../components/BasicModal';

class CustomerComplaint extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            complaint_id: 1,
            meet_id: 1,
            complaints : [],
            complaints_name : [],
            processes: [],
            image: [],
            modal: {
                show: false,
                modal_body: '',
                modal_footer: '',
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
            allowType : ['image/jpg', 'image/jpeg', 'image/png', 'video/mp4', 'application/pdf'],
            enableDragDrop: true,
            fetchSuccess: true,
            status: "將檔案拖放到這裡或點擊此處",
            preview: null,
            source: Axios.CancelToken.source(),
            img_src: null,
            brushColor: "",
            height:"",
            width: "",
            pictures:[],
            drawCanvasData: null,
            request_data: {
                background_src: "",
                img_src: "",
            },
            recorder_user_name: '',
            complaintRef: [],
            today_date: new Date().getFullYear() + "-" + this.addz(((new Date().getMonth() + 1).toString()), 2) + "-" +this.addz((new Date().getDate().toString()), 2),
            modal: {
                show: false,
                modal_body: '',
                modal_footer: '',
            },
            subject: "",
            pre_complaint_id: "",
            img : "",
            first_click: true,
        };
        this.child = React.createRef();
        this.modalRef = React.createRef();
        this.fileInputRef = React.createRef(null);
        this.canvasRef  = React.createRef(null);
        this.searchRef = React.createRef(null);
        this.onImageChange = this.onImageChange.bind(this);
        this.handleCanvasChange = this.handleCanvasChange.bind(this);
        this.handleSelected = this.handleSelected.bind(this);
        this.handleEdit = this.handleEdit.bind(this);
        this.handleSave = this.handleSave.bind(this);
        this.handleModalSave = this.handleModalSave.bind(this);
        this.handleModalClose = this.handleModalClose.bind(this);
        this.handleCreate = this.handleCreate.bind(this);
        this.getComplaint = this.getComplaint.bind(this);
        this.handleReport = this.handleReport.bind(this);
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
            Search: search
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
        if (supportedFilesTypes.indexOf(type) > -1 && this.state.enableDragDrop) {
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
                            this.setState({ enableDragDrop: true });
                            this.setState({ fetchSuccess: true });
                        }, 750); // To match the transition 500 / 250
                    } else {
                        this.setState({ status: `${perc}%` });
                    }
                    this.setState({ percentage: perc });
                }
            }).then(response => {
                let file_arr_temp = [];
                let file_img_id = [];
                response.data.map((value, index) => (
                    file_arr_temp.push({src:value.id})
                ))
                this.setState({ pictures: file_arr_temp });
            })
            // controller.abort();
            this.setState({ enableDragDrop: false });
        }
        else {
            this.setState({ preview: null, status: "此檔案無法上傳，請再次點擊或拖拉至此", fetchSuccess: true });
        }
    }
    onImageChange = event => {
        let file = event.target.files[0];
        let type = event.target.files[0].type;
        this.UploadImg(file, type);
        file = '';
        type = '';
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

    
    handleCanvasChange = (e) => {
        
        let canvas = this.child.current.getDrawCanvasData();
        // let canvas = this.state.drawCanvasData;
        let dataURL = canvas.toDataURL("image/png");
        let byteString = atob(dataURL.split(',')[1]);
        var mimeString = dataURL.split(',')[0].split(':')[1].split(';')[0];
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        var inputblob =  new Blob([ia], {type:mimeString});
        var file = new File([inputblob], "name.png");
        var payload = new FormData();
        payload.append('inputFile', file);
        payload.append('attach_file_id', this.state.img)
        axios.post('/CRM/complaint/attach_file/picture/paint', payload, {
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
    clickUpd = (e) => {
        this.fileInputRef.current.click()
    }

    setImg = (e) => {
        // this.setState({img_src: e.target.attributes.src.value});
        let request_data = {
            background_src: e.target.attributes.src.value
        }
        this.setState({request_data: request_data});
        this.setState({img: e.target.attributes.img_id.value});

        axios.get(`/CRM/complaint/attach_file/picture/paint`, {
            params: { attach_file_id: e.target.attributes.img_id.value},
            // params: {delivery_meet_content_file_id: 120},
        })
        .then((response) => {
            let request_data = {
                attach_file_id: e.target.attributes.img_id.value,
                background_src: `${axios.defaults.baseURL}/CRM/complaint/attach_file/image/${e.target.attributes.img_id.value}/${response.data.file_id}`
            }
            this.setState({request_data: request_data});
        });
    }

    handleCommentsChange = (e) => {
        console.log(e.target.id, e.target.value)
        this.setState({
			[e.target.id]: e.target.value
		});
    }

    handleSelected = (e) => {
        this.state.complaints.map((value, index) => (
            this.state.complaintRef[index].current.classList.remove("hadSlected")
        ))
        e.target.classList.add("hadSlected");
        this.state.complaints.map((value, index) => (
            Object.assign(this.state.complaintRef[index].current.style, {width:'auto', height: "50px", fontSize: "18px",fontWeight: "bold", background:"white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium"})
        ))
        Object.assign(e.target.style, {width:'auto', background:"#5e789f", color: "white", borderColor: "#5e789f"});
        this.setState({next_complaint_id: e.target.attributes.complaint_id.value})
        if(!this.state.first_click){
            this.setState({
                modal: {
                    modal_body: "請問是否儲存更改?",
                    modal_footer: 
                    <>
                        <Button 
                            variant="light" 
                            onClick={this.handleModalSave}
                            style={{ background: "#5e789f", color: "white", fontWeight: "bold" }}>
                            是
                        </Button>
                        <Button 
                            variant="light" 
                            style={{ background: "#858796", color: "white", fontWeight: "bold" }} 
                            onClick={this.handleModalClose}>
                            否
                        </Button>
                    </>,
                    show: true,
                }
            });
            this.modalRef.current.openModal();
        } else {
            this.setState({first_click: false})
            axios.get(`/CRM/complaint/content`, {
                params: {complaint_id: e.target.attributes.complaint_id.value},
            })
            .then((response) => {
                if(response.data.length === 0) {
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
                    this.setState({Search: search_temp})
                    let pictures = [];
                    this.setState({pictures: pictures})
                } else {
                    this.setState({
                        subject: response.data[0].subject || '',
                        comments: response.data[0].content || '',
                    })
                    let temp = this.state.Search.Select_row1;
                    temp.map((value, index) => (
                        value['value'] = response.data[0][value.id] || ''
                    ))
                    let search_temp = {
                        Select_row1: temp
                    }
                    this.setState({Search: search_temp})
                    let pictures = [];
                    response.data[0].files.map((value, index) => (
                        pictures.push({src: value.file_id})
                    ))
                    this.setState({pictures: pictures})
                }
                
            });
            this.setState({complaint_id: e.target.attributes.complaint_id.value})
            let upload = this.state.upload;
            let upload_request = this.state.upload.request_data;
            upload_request['complaint_id'] = e.target.attributes.complaint_id.value
            upload['request_data'] = upload_request;
            this.setState({upload: upload})
        }
        
    }
    handleEdit = (e) => {
        let temp = this.state.Search.Select_row1;
        let search_temp = {
            Select_row1: temp
        }
        this.setState({Search: search_temp})
    }
    handleSave = (e) => {
        let data = {};
        let temp = this.state.Search.Select_row1;
        let search_temp = {
            Select_row1: temp
        }
        this.setState({Search: search_temp})
        if(parseInt(this.state.complaint_id, 10) !== 0) {
            this.state.Search.Select_row1.map((value, index) => (
                data[value.id] = parseInt(value.value, 10)
            ))
            data['subject'] = this.state.subject;
            data['content'] = this.state.comments;
            data['complaint_id'] = parseInt(this.state.complaint_id, 10);
            
            axios.post(`/CRM/complaint/content`, 
            data 
            ).then((res) => {
                
            });
            
        } else {
            data['meet_id'] = this.state.meet_id;
            data['subject'] = this.state.subject;
            data['content'] = this.state.comments;
            this.state.Search.Select_row1.map((value, index) => (
                data[value.id] = parseInt(value.value, 10)
            ))
            axios.post(`/CRM/complaint/content/new_complaint`, 
                data 
            ).then((res) => {
                
            });
        }
        let complaints_name = this.state.complaints_name
        console.log(this.state.complaint_id)
        console.log(this.state.complaints)
        complaints_name[this.state.complaints.indexOf(parseInt(this.state.complaint_id, 10))] = this.state.subject
        this.setState({complaints_name: complaints_name})
    }
    getComplaint = (e) => {
        axios.get(`/CRM/complaint/content`, {
            params: {complaint_id: this.state.next_complaint_id},
        })
        .then((response) => {
            if(response.data.length === 0) {
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
                this.setState({Search: search_temp})
            } else {
                
                this.setState({
                    subject: response.data[0].subject || '',
                    comments: response.data[0].content || '',
                })
                let temp = this.state.Search.Select_row1;
                temp.map((value, index) => (
                    value['value'] = response.data[0][value.id] || ''
                ))
                let search_temp = {
                    Select_row1: temp
                }
                this.setState({Search: search_temp})
                let pictures = [];
                response.data[0].files.map((value, index) => (
                    pictures.push({src: value.file_id})
                ))
                this.setState({pictures: pictures})
            }
            this.setState({complaint_id: this.state.next_complaint_id})

        });
    }
    handleModalSave = (e) => {
        this.handleSave();
        this.modalRef.current.closeModal();
        this.getComplaint();
    }

    handleModalClose = (e) => {
        this.modalRef.current.closeModal();
        this.getComplaint();
    }

    handleCreate= (e) => {
        let complaints = this.state.complaints;
        complaints.push(0);
        this.setState({complaints: complaints})

        let complaints_name = this.state.complaints_name;
        complaints_name.push('[尚未儲存案件]');
        this.setState({complaints_name: complaints_name})

        let ref_arr = this.state.complaintRef
        ref_arr.push(React.createRef())
        this.setState({
            complaintRef: ref_arr,
        });
        let pictures = [];
        this.setState({pictures: pictures})
        let row = this;
        setTimeout(function() { 
            row.state.complaintRef[row.state.complaintRef.length - 1].current.click()
            
        }, 50);
        
    }

    handleReport = (e) => {
        let url = `/CRM/complaint/qualityForm/${this.state.complaint_id}`;
        window.open(url, '_blank');
    }
 
    componentDidMount() {
        axios.get(`/CRM/user`)
            .then((response) => {
                let user = response.data[0]
                this.setState({
                recorder_user_name: user.name,
                });
            });
        

        let params = {
            meet_id : 1,
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
                    complaints_name: name
                });
                let ref_arr = []
                this.state.complaints.map((value, index) => (
                    ref_arr.push(React.createRef())
                ))
                this.setState({
                    complaintRef: ref_arr,
                });
            });
        
    }
    

    render() {
        return (
            <Container fluid>
                <BasicModal
                    modal_title="訊息"
                    modal_body={this.state.modal.modal_body}
                    modal_footer={this.state.modal.modal_footer}
                    show={this.state.modal.show}
                    ref={this.modalRef}
                  ></BasicModal>
                 <Card className="mx-2 my-1">
                    <Card.Header>客訴內容</Card.Header>
                    <Card.Body>
                    <Row style={{display: 'flex', justifyContent:'right'}}>
                        <Col md="auto">
                            <InputGroup className="mb-3">
                                <InputGroup.Text>日期</InputGroup.Text>
                                <FormControl
                                aria-describedby="inputGroup-sizing-default"
                                disabled
                                value= {this.state.today_date}
                                style={{ background:"white" }}
                                />
                            </InputGroup>
                        </Col>
                        <Col md="auto">
                            <InputGroup className="mb-3">
                                <InputGroup.Text>建立者</InputGroup.Text>
                                <FormControl
                                aria-describedby="inputGroup-sizing-default"
                                disabled
                                style={{ background:"white", }}
                                value={this.state.recorder_user_name}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                    <Row>
                        <Col md='10'>
                            {this.state.complaints.map((value, index) => (
                                <Button className="mx-2 my-2" complaint_id={value} ref={this.state.complaintRef[index]} onClick={this.handleSelected} variant="light" style={{ textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace:"nowrap", maxWidth:'150px', height: "50px", fontSize: "18px",fontWeight: "bold", background:"white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium"}}>{this.state.complaints_name[index]}</Button>
                                // <Button className="mx-2 my-2" complaint_id={value} ref={this.state.complaintRef[index]} onClick={this.handleSelected} variant="light" style={{  height: "50px", fontSize: "18px",fontWeight: "bold", background:"white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium"}}>{this.state.complaints_name[index]}</Button>
                            ))
                            }
                            <Button className="mx-2 my-2" onClick={this.handleCreate} variant="light" style={{width:'50px', height: "50px",fontSize: "25px",fontWeight: "bold", background:"#7DC0BC", color: "white", }}>+</Button>
                        </Col>
                    </Row>
                    <Row>
                        <Col md="4">
                            <InputGroup style={{height: '57px'}} className="mb-3">
                                <InputGroup.Text>主旨</InputGroup.Text>
                                <FormControl  style={{height: '57px'}}
                                placeholder="必填*"
                                aria-describedby="inputGroup-sizing-default"
                                value={this.state.subject}
                                id='subject'
                                onChange={this.handleCommentsChange.bind(this)}
                                />
                            </InputGroup>
                        </Col>
                        <Search ref={this.searchRef} resetData={this.handleSearchChange.bind(this)} name={this.state.Search.Select_row1}></Search>
                        
                        
                        <Col className="my-2" >
                            <Button className="mx-2" variant="light" onClick={this.handleSave} style={{fontWeight: "bold", background:"#ffbc00", color: "white", }}>儲存</Button>
                            <Button className="mx-2" output="csv" onClick={this.handleReport} variant="light" style={{width:'auto', fontWeight: "bold", background:"#7ea584", color: "white", }}>CSV</Button>
                            <Button className="mx-2" output="pdf" onClick={this.handleReport} variant="light" style={{width:'auto', fontWeight: "bold", background:"#E85559", color: "white", }}>PDF</Button>
                        </Col>
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
                    <hr />
                    <Row>
                        <Button className="mx-2" variant="light" onClick={this.clickUpd.bind(this)} style={{width:'auto', fontWeight: "bold", background:"#7B84A0", color: "white", }}>上傳檔案</Button>
                        <input style={{ display: 'none' }} type="file" ref={this.fileInputRef} onChange={(e) => this.onImageChange(e)} />
                    </Row>
                    <Row>
                        <div style={{ overflowX: 'scroll', overflowY: 'scroll', height: 350, whiteSpace: 'nowrap' }}>
                            {this.state.pictures.map((value, index) => (
                                <img 
                                    img_id={value.src} 
                                    src={`${axios.defaults.baseURL}/CRM/complaint/complaint/attach_file/${value.src}`} 
                                    alt={value.alt} 
                                    onClick={this.setImg.bind(this)} 
                                    className="mx-2 my-2" 
                                    style={{ width: 400, border: "1px solid #a39e9e", cursor: "pointer" }} />
                            ))
                            }
                        </div>
                    </Row>
                    <Row>
                        <Col md='12'>
                            <Row className='my-2'>
                                <DrawRect drawRectArea={false} ref={this.child} request_data={this.state.request_data} handleCanvasChange={this.handleCanvasChange} api_location='/CRM/complaint/attach_file/picture/frame' />
                            </Row>
                        </Col>
                    </Row>
                    </Card.Body>
                </Card>
            </Container>
        );
    }
}

export default CustomerComplaint