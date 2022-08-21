import React, { useState, useEffect, useReducer, useRef } from 'react';
import axios from 'axios';
import './Quote_Summary.css';
import Datatable from "../Datatable";
import { Card, Row, Col, Button, Modal, InputGroup, FormControl } from 'react-bootstrap';

const QuoteSummary = (props) => {
    const baseURL = ``;
    const [showHide, setShowHide] = useState(false); // 控制圖片縮圖Modal
    // const [like_img_state, setLike_img_state] = useState("yes"); // 是否有相似廠內圖(目前為Hidden)
    const [img_id, setImg_id] = useState(""); // 客戶圖號
    const [customer_id, setCustomer_id] = useState("");//品號
    const [img_time, setImg_time] = useState(""); //開單時間
    const [img_note, setImg_note] = useState(""); //註記
    const [img_src, setImg_src] = useState(""); //客戶圖縮圖之src
    const [show, setShow] = useState(false); // 控制修改單號之Modal
    const [Editshow, setEditshow] = useState(true); // 控制修改圖號之FormControl
    const [customer, setCustomer] = useState({ picture_num: "", customer_id: "" })
    const [RadioOpt, setRadioOpt] = useState(''); //選了哪一個Radio
    const [SendBtn, setSendBtn] = useState('送出'); //客戶圖號「狀態」
    const [flagBtn, setFlagBtn] = useState(false); //客戶圖號「狀態」禁用
    //點擊Radio後，獲得當前選取之值
    const RadioChange = (event) => {
        setRadioOpt(event.target.value)
    }
    // 顯示DataTable
    const handleShow = () => {
        setShow(true)
    };
    // 關閉DataTable
    const handleClose = () => setShow(false);
    // DataTable所傳遞Data及Thead輸入
    const datatables_range = {
        require: {
            picture_num: "",
            customer_id: "",
        },
        thead: [{
            name: '#',
            cell: row => <input type="radio" name="dataTable_Radio" id={row.id} value={row["品號"]} style={{ width: "20px", height: "20px" }} onChange={RadioChange} />,
            width: '14%',
            center: true,
        }, {
            name: '品號',
            cell: row => row["品號"],
            width: '15%',
            center: true,
        }, {
            name: '硬度',
            cell: row => row["硬度"],
            width: '14%',
            center: true,
        }, {
            name: '客戶圖號',
            cell: row => row["客戶圖號"],
            width: '15%',
            center: true,
        }, {
            name: '版次',
            cell: row => row["版次"],
            width: '14%',
            center: true,
        }, {
            name: '材質',
            cell: row => row["材質"],
            width: '14%',
            center: true,
        }, {
            name: '鍍鈦',
            cell: row => row["鍍鈦"],
            width: '14%',
            center: true,
        }]
    };
    const [datatable_data, setDatatable_data] = useState(datatables_range);
    // 獲取修改完客戶圖號之值
    const myRef = useRef(null);
    // 動態更新DataTable子代
    const child_Search = useRef(null);
    useEffect(() => {
        let params = {};
        params["module_name"] = props.module_name;
        axios
            .get("/file/state/"+props.file_id, {
                params: params,
            })
            .then((response) => {
                let data = response["data"]["file_information"];
                setImg_id(data[0]["order_name"]);
                setImg_time(data[0]["upload_time"]);
                setImg_src(axios.defaults.baseURL + "/file/" + data[0]["id"])
            });
    }, [Editshow])

    useEffect(() => {
        let params = {};
        params["file_id"] = props.file_id;
        axios
            .get("/file/information", {
                params: params,
            })
            .then((response) => {
                setCustomer_id(response["data"][0]["itemno"])
            });
        axios
            .get("/file/file_comment", {
                params: params,
            })
            .then((response) => {
                                let text=response["data"]["comment"][0]["comment"]
                setImg_note(text)
                if(text==null){
                    setImg_note("無")
                }
            });
    }, [])

    function handleModalShowHide(src) {
        setShowHide(!showHide);
    }
    // 是否有相似圖，目前此功能d-none起來
    // function like_img() {
    //     if (like_img_state == "yes") {
    //         return (
    //             <img className="img-thumbnail img" src={props.img_src1} onClick={() => handleModalShowHide(props.img_src1)}></img>
    //         )
    //     }
    //     else {
    //         return (
    //             <label className='text-dark'>尚未找到相似的廠內圖</label>
    //         )
    //     }
    // }

    // 獲得DataTable輸入之客戶圖號
    function GETorderName(e) {
        setCustomer({ ...customer, picture_num: e.target.value })
    }
    // 獲得DataTable輸入之客戶代號
    function GETcustomID(e) {
        setCustomer({ ...customer, customer_id: e.target.value })
    }
    function EditData() {
        let temp = datatable_data.require;
        temp.customer_id = customer.customer_id;
        temp.picture_num = customer.picture_num;
        setDatatable_data({ ...datatable_data, require: temp })
        setTimeout(function () {
            child_Search.current.fetchUsers();
        }, 100)
    }
    function postProcess(response) {
        return response;
    }
    useEffect(() => {
        EditData()
    }, [customer])
    // 修改「下一步」的API
    function updateItemNOmodal() {
        let params = {
            file_id: props.file_id,
            itemno: RadioOpt
        }
        axios
            .patch("/file/itemno", params)
            .then((response) => {
                setShow(!show);
                setCustomer_id(RadioOpt)
            });
    }
    // 控制客戶圖號的修改Btn跟Input顯示/隱藏
    function modifyorder_name() {
        setEditshow(!Editshow)
    }
    // 修改客戶圖號的API
    function Editorder_name() {
        let params = {
            file_id: props.file_id,
            order_name: myRef.current.value
        }
        setSendBtn("修改中...")
        setFlagBtn(true)
        axios
            .patch("/file/order_name", params)
            .then((response) => {
                setEditshow(!Editshow)
                setSendBtn("送出")
                setFlagBtn(false)
            });
    }

    return (
        <div className='Quote_Summary'>
            <Row className='w-100 m-0'>
                <Col md='12'>
                    <Card className='shadow mb-4 w-100'>
                        <Card.Title md='12' as="h3" className='position-relative mb-5'>
                            <span className="badge rounded position-absolute rfid_title p-3 text-center top-0 start-0 ">速報區</span>
                        </Card.Title>
                        <Row className='d-flex py-4'>
                            <Col md='4' sm='6' xs='12' className='mt-2 px-5 d-flex flex-column'>
                                <label className='text-title pb-2'>客戶圖縮圖</label>
                                <img className="img-thumbnail" src={img_src} onClick={() => handleModalShowHide(img_src)}></img>
                            </Col>
                            {/* <Col md='4' sm='6' xs='12' className='mt-2 px-5 d-flex flex-column text-nowrap'>
                                <label className='text-title pb-2'>相似客戶圖</label>
                                {like_img()}
                            </Col> */}
                            <Col md='4' sm='6' xs='12' className='mt-2 px-5'>
                                <label className='text-title pb-2'>訂單資訊</label>
                                <InputGroup className="mb-3">
                                    <label className='text-dark'>品號：</label>
                                    <FormControl
                                        placeholder={customer_id}
                                        aria-label="Recipient's username"
                                        aria-describedby="basic-addon2"
                                        disabled
                                    />
                                    <Button variant="secondary" className='rfid_title' data-toggle="modal"
                                        data-target="#exampleModal2" data-type="selectItemNOmodal" onClick={handleShow}>
                                        修改
                                    </Button>
                                </InputGroup>
                                <div className='mb-3 d-flex text-nowrap'>
                                    <label className='text-dark'>客戶圖號：</label>
                                    <label className={'text-dark me-2 ' + (Editshow ? " " : "d-none")}>{img_id}</label>
                                    <InputGroup className={"mb-3 me-2  " + (Editshow ? "d-none" : " ")}>
                                        <FormControl
                                            placeholder="客戶圖號"
                                            aria-label="客戶圖號"
                                            aria-describedby="basic-addon1"
                                            defaultValue={img_id}
                                            ref={myRef}
                                        />
                                        <Button variant="secondary" className='rfid_title' onClick={Editorder_name} disabled={flagBtn}>
                                            {SendBtn}
                                        </Button>
                                    </InputGroup>
                                    <Button variant="secondary" className={'rfid_title ' + (Editshow ? " " : "d-none")} onClick={modifyorder_name}>
                                        修改
                                    </Button>
                                </div>
                                <div className='mb-3 d-flex text-nowrap'>
                                    <label className='text-dark'>開單時間：</label>
                                    <label className='text-dark'>{img_time}</label>
                                </div>
                            </Col>
                            <Col md='4' sm='6' xs='12' className='mt-2 px-5 d-flex flex-column text-nowrap'>
                                <label className='text-title pb-2'>註記</label>
                                <label className='text-dark'>{img_note}</label>
                            </Col>
                        </Row>
                    </Card>
                </Col>
            </Row>
            <Modal show={showHide} size="lg">
                <Modal.Header closeButton onClick={() => handleModalShowHide()}>
                    <Modal.Title>檢視縮圖</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <img src={img_src} className="img-fluid" ></img>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => handleModalShowHide()}>
                        Close
                    </Button>
                </Modal.Footer>
            </Modal>
            {/* 修改的modal */}
            <Modal show={show} size="lg"
                aria-labelledby="contained-modal-title-vcenter"
                centered>
                <Modal.Header closeButton onClick={() => handleClose()}>
                    <Modal.Title>選擇品號</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row className='history_table'>
                        <InputGroup className="mb-3">
                            <InputGroup.Text id="basic-addon1">客戶圖號</InputGroup.Text>
                            <FormControl
                                type="text"
                                placeholder="客戶圖號"
                                aria-label="客戶圖號"
                                aria-describedby="basic-addon1"
                                onChange={GETorderName}
                            />
                        </InputGroup>
                        <InputGroup className="mb-3">
                            <InputGroup.Text id="basic-addon2">客戶代號</InputGroup.Text>
                            <FormControl
                                placeholder="客戶代號"
                                aria-label="客戶代號"
                                aria-describedby="basic-addon2"
                                onChange={GETcustomID}
                            />
                        </InputGroup>
                        <Datatable datatables={datatable_data} postProcess={postProcess} api_location="/business/itemNO/react" ref={child_Search} />
                    </Row>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose}>
                        關閉
                    </Button>
                    <Button variant="primary" onClick={updateItemNOmodal}>
                        下一步
                    </Button>

                </Modal.Footer>
            </Modal>
        </div>
    );
}
export default QuoteSummary;