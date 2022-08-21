import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Search from '../Search.js';
import Image from 'react-bootstrap/Image'
import Form from 'react-bootstrap/Form';
import Container from 'react-bootstrap/Container';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import UploadBlock from '../meet/upload.js';
import DataTable from 'react-data-table-component';
import { Card } from 'react-bootstrap';

class MainPage extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            columns: [
                {
                    name: '報價單編號',
                    selector: row => row.報價單編號,
                },
                {
                    name: '開單日期',
                    selector: row => row.開單日期,
                },
                {
                    name: '客戶圖號',
                    selector: row => row.客戶圖號,
                },
                {
                    name: '目前狀況',
                    selector: row => row.目前狀況,
                },
                {
                    name: '客戶名稱',
                    selector: row => row.客戶名稱,
                },
                {
                    name: '動作',
                    selector: row => row.動作,
                },
            ],
            columns1: [
                {
                    name: '報價單編號',
                    selector: row => row.報價單編號,
                },
                {
                    name: '圖檔',
                    selector: row => row.圖檔,
                },
            ],
            datatable_col: [
                {
                    "報價單編號": "001", "開單日期": 40, "客戶圖號": "2022 / 3 / 5", "目前狀況": "", "動作": ""
                },
                {
                    "報價單編號": "001", "開單日期": 40, "客戶圖號": "2022 / 3 / 5", "目前狀況": "", "動作": ""
                },
                {
                    "報價單編號": "001", "開單日期": 40, "客戶圖號": "2022 / 3 / 5", "目前狀況": "", "動作": ""
                },
                {
                    "報價單編號": "001", "開單日期": 40, "客戶圖號": "2022 / 3 / 5", "目前狀況": "", "動作": ""
                },
            ],
            datatable_col1: [
                {
                    "報價單編號": "003", "圖檔": <Form.Select>< option >3</option></Form.Select >
                },
                {
                    "報價單編號": "004", "圖檔": <Form.Select>< option >4</option></Form.Select >
                },
            ],
            search_input: [
                { 'label': '客戶名稱:', 'id': 'name', 'type': 'input', 'name': 'search_input', 'isinvalid': false },
            ]
        };
    }
    componentDidMount() {

    }
    handleChange(data) {
        const temp = this.state.search_data;
        // const request = this.state.datatables.request;
        Object.keys(temp).map((key, value) => {
            if (key === data.id) {
                temp[key] = data.value.trim() === '' ? null : data.value.trim();
                return;
            }
        })
        this.setState({
            search_data: temp
        })
    }
    render() {
        return (
            <Container fluid className="d-grid gap-2 my-3">
                <Card>
                    <Card.Header>
                        新舊圖辨識
                    </Card.Header>
                    <Card.Body>
                        <Row>
                            <Col>
                                <UploadBlock />
                            </Col>
                            <Col>
                                <Search resetData={this.handleChange} name={this.state.search_input} />
                            </Col>
                        </Row>
                        <hr />
                        <Row>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />
                            </Col>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />


                            </Col>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />

                            </Col>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />

                            </Col>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />

                            </Col>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />

                            </Col>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />

                            </Col>
                            <Col md={2}>
                                <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />

                            </Col>
                        </Row>
                        <hr />
                        <Row>
                            <Col md="6">
                                <Row>
                                    <label>
                                        舊圖
                                    </label>
                                </Row>
                                <Row>
                                    <DataTable
                                        columns={this.state.columns}
                                        data={this.state.datatable_col}
                                        fixedHeaderScrollHeight="300px"
                                        pagination
                                        responsive
                                        subHeaderAlign="right"
                                        subHeaderWrap
                                    />
                                </Row>
                            </Col>
                            <Col md="6">
                                <Row>
                                    <label>
                                        新圖
                                    </label>
                                </Row>
                                <Row>
                                    <DataTable
                                        columns={this.state.columns1}
                                        data={this.state.datatable_col1}
                                        fixedHeaderScrollHeight="300px"
                                        pagination
                                        responsive
                                        subHeaderAlign="right"
                                        subHeaderWrap
                                    />
                                </Row>
                            </Col>
                        </Row>
                    </Card.Body>
                </Card>


            </Container>
        );
    }
}

export default MainPage;