import React, { useState, useRef, useEffect } from "react";
import { Card, Modal, Button, Row, Col, Container } from 'react-bootstrap';
import axios from 'axios';
import BasicModal from '../components/BasicModal';
import SearchGroup from "./SearchGroup";
import SearchTextArea from "./SearchTextArea";

function HandleComplaintForm(props) {
    const [modalData, setModalData] = useState({
        src: '',
        alt: '',
        file_name: '',
        rotate: 0
    });
    const [searchDataRow1, setSearchDataRow1] = useState([
        { disabled: true, idx: 0, row_idx: 0, name: '客戶代號', id: 'customer_id', value: '', type: 'input' },
        { disabled: false, idx: 0, row_idx: 1, name: '品號', id: 'number', value: '', type: 'input' },
        { disabled: false, idx: 0, row_idx: 2, name: '訂單號碼', id: 'order_num', value: '', type: 'input' },
        { disabled: true, idx: 0, row_idx: 3, name: '圖號', id: 'img_id', value: '', type: 'input' },

    ]);
    const [searchDataRow2, setSearchDataRow2] = useState([
        { disabled: true, idx: 1, row_idx: 0, name: '填單日期', id: 'fill_in_date', value: '', type: 'date' },
        { disabled: false, idx: 1, row_idx: 1, name: '出貨日期', id: 'shipping_date', value: '', type: 'date' },
        { disabled: false, idx: 1, row_idx: 2, name: '出貨數量', id: 'shipping_count', value: '', type: 'number' },
        { disabled: false, idx: 1, row_idx: 3, name: '不良數量', id: 'bad_count', value: '', type: 'number' },
    ]);
    const [searchDataRow3, setSearchDataRow3] = useState([
        { idx: 2, row_idx: 0, 'height': 180, 'label': '客戶抱怨內容:', 'id': 'complaint_content', 'type': 'input', 'value': '', 'disabled': true },
    ]);
    const [searchDataRow4, setSearchDataRow4] = useState([
        { disabled: false, idx: 3, row_idx: 0, name: '現有庫存', id: 'current_count', value: '', type: 'number' },
    ]);
    const [searchDataRow5, setSearchDataRow5] = useState([
        { disabled: false, idx: 4, row_idx: 0, name: '現有訂單', id: 'current_order', value: '', type: 'number' },
    ]);
    const [searchDataRow6, setSearchDataRow6] = useState([
        { idx: 5, row_idx: 0, 'height': 110, 'label': '現狀掌握:', 'id': 'current_situation', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 5, row_idx: 1, 'height': 180, 'label': '問題分析與原因追查:', 'id': 'problem', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 5, row_idx: 2, 'height': 180, 'label': '改善對策:(庫存,現有訂單,未來訂單)', 'id': 'improve_strategy', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 5, row_idx: 3, 'height': 180, 'label': '內部追蹤:', 'id': 'internal_tracking', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 5, row_idx: 4, 'height': 180, 'label': '外部追蹤:', 'id': 'external_tracking', 'type': 'input', 'value': '', 'disabled': false },
    ]);
    const [searchDataRow7, setSearchDataRow7] = useState([
        { idx: 6, row_idx: 0, 'height': 180, 'label': '責任單位簽名日期:', 'id': 'sign_date_1', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 6, row_idx: 1, 'height': 180, 'label': '責任單位簽名日期:', 'id': 'sign_date_2', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 6, row_idx: 2, 'height': 180, 'label': '責任單位簽名日期:', 'id': 'sign_date_3', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 6, row_idx: 3, 'height': 180, 'label': '責任單位簽名日期:', 'id': 'sign_date_4', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 6, row_idx: 4, 'height': 180, 'label': '責任單位簽名日期:', 'id': 'sign_date_5', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 6, row_idx: 5, 'height': 180, 'label': '責任單位簽名日期:', 'id': 'sign_date_6', 'type': 'input', 'value': '', 'disabled': false },
        { idx: 6, row_idx: 6, 'height': 180, 'label': '責任單位簽名日期:', 'id': 'sign_date_7', 'type': 'input', 'value': '', 'disabled': false },
    ]);
    const [searchDataRow8, setSearchDataRow8] = useState([
        { disabled: false, idx: 7, row_idx: 0, name: '備註', id: 'note', value: '', type: 'input' },
        { disabled: false, idx: 7, row_idx: 1, name: '分發單位簽名日期', id: 'sign_date_8', value: '', type: 'input' },
    ]);
    const [searchData, setSearchData] = useState([
        searchDataRow1, searchDataRow2, searchDataRow3, searchDataRow4, searchDataRow5, searchDataRow6, searchDataRow7, searchDataRow8
    ])
    const [isSubmit, setIsSubmit] = useState(false);
    const [dataSubmit, setDataSubmit] = useState(null);
    const [loading, setLoading] = useState(false);
    const [show, setShow] = useState(false);
    const [complaintId, setComplaintId] = useState(window.location.href.split('/')[window.location.href.split('/').length - 1])
    const handleChange = (e) => {
        let searchData_temp = searchData;
        let row_data = searchData_temp[e.target.attributes.idx.value]
        row_data[e.target.attributes.row_idx.value].value = e.target.value;
        searchData_temp[e.target.attributes.idx.value] = row_data;
        setSearchData(JSON.parse(JSON.stringify(searchData_temp)))
    };
    const handleBlankReport = (e) => {
        let url = `${axios.defaults.baseURL}/CRM/complaint/report?type=pdf&complaint_id=${complaintId}`;
        window.open(url, '_blank');
    };

    const handleReport = (e) => {
        let url = `${axios.defaults.baseURL}/CRM/complaint/data_report?type=pdf&complaint_id=${complaintId}`;
        window.open(url, '_blank');
    };

    const handleSave = () => {
        let data = {};
        console.log(searchData)
        data['complaint_id'] = complaintId
        searchData.map((search_value, search_index) => (
            (search_index !== 6) ?
                search_value.map((value, index) => (
                    data[value.id] = ((value.value === undefined) ? '' : value.value)
                )) : null
        ))

        console.log(data)
        axios.post(`/CRM/complaint/form`, data)
            .then((response) => {

            });
    };
    useEffect(() => {
        setLoading(props.loading)
    }, [props.loading]);

    useEffect(() => {
        axios.get(`/CRM/complaint/form`, {
            params: { complaint_id: complaintId },
        })
            .then((response) => {
                if (response.data.fill_in_date !== undefined && response.data.shipping_date !== null) {
                    response.data.fill_in_date = response.data.fill_in_date.split(" ")[0]
                }
                if (response.data.shipping_date !== undefined && response.data.shipping_date !== null) {
                    response.data.shipping_date = response.data.shipping_date.split(" ")[0]
                }
                let searchData_temp = searchData;
                searchData_temp.map((search_data_value, search_data_index) => (
                    search_data_value.map((value, index) => (
                        value['value'] = response.data[value.id]
                    ))
                ))
                setSearchData(JSON.parse(JSON.stringify(searchData_temp)))
            });
    }, []);

    return (
        <>
            {loading ? <>讀取中...</> :
                <Container>
                    <Row>
                        <Col style={{ display: 'flex', justifyContent: 'right' }}>
                            <Button className="mx-2 my-2" variant="light" onClick={handleSave} style={{ fontWeight: "bold", background: "#263859", color: "white", }}>儲存處理單</Button>
                            <Button className="mx-2 my-2" variant="light" onClick={handleBlankReport} style={{ fontWeight: "bold", background: "#6b778d", color: "white", }}>空白處理單</Button>
                            <Button className="mx-2 my-2" variant="light" onClick={handleReport} style={{ fontWeight: "bold", background: "#ff6768", color: "white", }}>下載處理單</Button>
                        </Col>
                    </Row>
                    <div className="mt-2" style={{ color: '#375077', textAlign: 'center' }}>
                        <h2 style={{ fontWeight: 'bold' }}>龍畿企業股份有限公司</h2>
                    </div>
                    <div className="mb-4" style={{ color: '#375077', textAlign: 'center' }}>
                        <h2 style={{ fontWeight: 'bold' }}>客戶抱怨處理單</h2>
                    </div>
                    <Row>
                        <Col md="9">
                            <Row>
                                <Col md="6">
                                    <SearchGroup onChange={handleChange} searchData={searchData[0]} />
                                </Col>
                                <Col md="6">
                                    <SearchGroup onChange={handleChange} searchData={searchData[1]} />
                                </Col>
                            </Row>
                            <SearchTextArea onChange={handleChange} searchData={searchData[2]} />
                            <Row>
                                <Col md="6">
                                    <SearchGroup onChange={handleChange} searchData={searchData[3]} />
                                </Col>
                                <Col md="6">
                                    <SearchGroup onChange={handleChange} searchData={searchData[4]} />
                                </Col>
                            </Row>
                            <SearchTextArea onChange={handleChange} searchData={searchData[5]} />
                        </Col>
                        <Col md="3">
                            <SearchTextArea onChange={handleChange} searchData={searchData[6]} />
                        </Col>
                    </Row>
                    <Row>
                        <SearchGroup onChange={handleChange} searchData={searchData[7]} />
                    </Row>
                </Container>
            }

        </>
    );
}

export default HandleComplaintForm;