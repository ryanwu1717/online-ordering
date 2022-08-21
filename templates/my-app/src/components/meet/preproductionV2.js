import React, { useState, useEffect, useCallback, useMemo } from 'react';
// import React, {} from 'react';

import { FormControl, InputGroup, FloatingLabel, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';
import { Table } from 'antd';
import DataTable from 'react-data-table-component';
import "bootstrap/dist/js/bootstrap.bundle.js";
import './preproductionV2.css'
import axios from 'axios';

import { CSVLink } from 'react-csv';
import * as XLSX from "xlsx";
import * as FileSaver from "file-saver";
// import "./holiday.json"

class PreproductionBlockV2 extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            columns: [
                {
                    title: '週數',
                    dataIndex: 'week',
                    align: 'center',
                    onCell: (_, index) => {

                        if (this.state.rowSpanIndex.indexOf(index) !== -1) {

                            return { rowSpan: this.state.rowSpanCount[this.state.rowSpanIndex.indexOf(index)] };
                        } else {
                            return { rowSpan: 0 }
                        }
                    },
                },
                {
                    title: '預計生產完成日',
                    align: 'center',
                    dataIndex: 'date',
                },
                {
                    title: '盤數',
                    dataIndex: 'count',
                    align: 'center',
                },
                {
                    title: '訂單數量',
                    dataIndex: 'order_count',
                    align: 'center',
                },
                {
                    title: '現場完成量',
                    dataIndex: 'current',
                    align: 'center',
                },
                {
                    title: '外注完成量',
                    dataIndex: 'outsourcing',
                    align: 'center',
                },
            ],
            external_injection: "",
            on_site: "",
            plate_total: "",
            order_total: "",
            default_date: null,
            date_begin: null,
            date_end: null,
            datatable_col: [],
            csvDataHeader: [
                { label: "週數", key: "週數" },
                { label: "預計生產完成日", key: "預計生產完成日" },
                { label: "盤數", key: "盤數" },
                { label: "訂單數量", key: "訂單數量" },
                { label: "現場完成量", key: "現場完成量" },
                { label: "外注完成量", key: "外注完成量" },
            ],
            rowSpanCount: [],
            rowSpanIndex: [],
            rowSpanValue: [],
            tableData: [],
        };
        this.getPreproduction = this.getPreproduction.bind(this);
        this.changeDateBegin = this.changeDateBegin.bind(this);
        this.changeDateEnd = this.changeDateEnd.bind(this);
        this.child = React.createRef();
        this.createCsvDataToday = this.createCsvDataToday.bind(this);
        this.isHoliday = this.isHoliday.bind(this);
        this.dateChange = this.dateChange.bind(this);

    }
    componentDidMount() {
        let date_start_tmp = new Date();
        let date_end_tmp = new Date();
        date_start_tmp.setDate(date_start_tmp.getDate() - 7)
        date_end_tmp.setMonth(date_end_tmp.getMonth() + 4)
        let temp = this.dateChange(date_end_tmp, "/")
        // let aaa = false
        // while (this.isHoliday(temp)==true) {
        //   date_end_tmp.setDate(date_end_tmp.getDate() + 1)
        //   this.dateChange(date_end_tmp, "/")
        //   this.isHoliday(temp);
        // }
        let end = this.dateChange(date_end_tmp, "-")
        let start = this.dateChange(date_start_tmp, "-")
        this.setState({
            date_begin: start,
            date_end: end
        });
        this.getPreproduction(start, end)

    }
    getPreproduction(start, end) {
        let date_begin = null
        let date_end = null
        if (this.state.date_begin != null && this.state.date_end != null) {
            date_begin = this.state.date_begin.replaceAll('-', '')
            date_end = this.state.date_end.replaceAll('-', '')
        }

        if (date_begin == null || date_end == null) {
            date_begin = start.replaceAll('-', '')
            date_end = end.replaceAll('-', '')
        }
        axios.get('/Report/preProduction', {
            //URL参數放在params屬性裏面
            params: {
                date_begin: date_begin,
                date_end: date_end
            }
        })
            .then((response) => {
                const item = response.data["origin"];
                const fivedays = response.data["fivedays"];
                const total = response.data["total"]["0"]
                if (item.length > 0) {
                    this.setState({
                        datatable_col: item
                    })
                    let rowSpanCount = [];
                    let rowSpanIndex = [];
                    let rowSpanValue = [];
                    let tableData = [];
                    item.map((value, index) => {
                        if (rowSpanValue.indexOf(value['週數']) === -1) {
                            rowSpanValue.push(value['週數'])
                            rowSpanIndex.push(index)
                            rowSpanCount.push(1)
                        } else {
                            rowSpanCount[rowSpanValue.indexOf(value['週數'])] += 1
                        }
                        tableData.push({
                            key: index,
                            week: value['週數'],
                            date: value['預計生產完成日'],
                            count: Math.round(value['盤數']),
                            order_count: Math.round(value['訂單數量']),
                            current: Math.round(value['現場完成量']),
                            outsourcing: Math.round(value['外注完成量']),
                        })
                    })

                    this.setState({
                        rowSpanIndex: rowSpanIndex,
                        rowSpanCount: rowSpanCount,
                        rowSpanValue: rowSpanValue,
                        tableData: tableData
                    })
                }
                this.setState({
                    external_injection: Math.round(fivedays["外注完成量"]),
                    on_site: Math.round(fivedays["現場完成量"]),
                    plate_total: Math.round(total["盤數"]),
                    order_total: Math.round(total["訂單數量"]),
                })
            })
            .catch((error) => console.log(error))

    }
    isHoliday(day) {
        axios.get('/CRM/holiday',)
            .then((response) => {
                let temp = response.data
                let value = false
                Object.keys(temp).map((key) => {
                    if (day == temp[key]["new_date"]) {
                        if (temp[key]["isHoliday"] == "是") {

                            this.isHoliday(day)
                        }
                    }
                })
                this.setState({
                    is_holiday: value
                })
            })
            .catch((error) => console.log(error))
    }
    dateChange(date, text) {
        let dd = date.getDate();
        let mm = date.getMonth() + 1;
        let yyyy = date.getFullYear();
        if (dd < 10) {
            dd = "0" + dd
        }
        if (mm < 10) {
            mm = "0" + mm
        }
        let temp = `${yyyy}${text}${mm}${text}${dd}`
        console.log(temp)
        return temp
    }
    changeDateBegin(event) {
        this.setState({
            date_begin: event.target.value,
        });
    }
    changeDateEnd(event) {
        this.setState({
            date_end: event.target.value,
        });
    }
    createCsvDataToday(e) {

        // document.getElementById("csvToday").click();
        if (e.target.attributes.output.value === 'csv') {
            document.getElementById("csvToday").click();
        } else {
            let res_data = [];
            this.state.datatable_col.map((value, index) => (
                res_data.push({
                    "預計生產完成日": value.預計生產完成日,
                    "盤數": value.盤數,
                    "訂單數量": value.訂單數量,
                    "現場完成量": value.現場完成量,
                    "外注完成量": value.外注完成量,
                })
            ))
            let ws = XLSX.utils.json_to_sheet(res_data);
            let wb = { Sheets: { data: ws }, SheetNames: ["data"] };
            let excelBuffer = XLSX.write(wb, { bookType: e.target.attributes.output.value, type: "array" });
            let data = new Blob([excelBuffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8" });
            FileSaver.saveAs(data, '預計生產報表.' + e.target.attributes.output.value);

        }
    }

    render() {
        return (
            <>
                <Form.Group as={Row} controlId="formGridState" className='mb-2'>
                    <Col md="auto">
                        <FloatingLabel label="起:" className="mb-2">
                            <Form.Control
                                id="date_begin" type="date" defaultValue={this.state.date_begin} onChange={this.changeDateBegin} className="form-control"
                            />
                        </FloatingLabel>
                    </Col>
                    <Col md="auto">
                        <FloatingLabel label="迄:" className="mb-2">
                            <Form.Control
                                id="date_end" type="date" defaultValue={this.state.date_end} onChange={this.changeDateEnd} className="form-control" value={this.state.date_end}
                            />
                        </FloatingLabel>
                    </Col>
                    <Col md="auto">
                        <Button variant="primary" onClick={this.getPreproduction}>確定</Button>{' '}
                    </Col>
                    <Col md="auto">
                        <CSVLink id="csvToday" filename='預計生產報表' data={this.state.datatable_col} headers={this.state.csvDataHeader} ></CSVLink>
                        <Button output="csv" variant="success" onClick={this.createCsvDataToday}>CSV</Button>
                    </Col>
                    <Col md="auto">
                        <Button output="xlsx" variant="light" onClick={this.createCsvDataToday} style={{ width: 'auto', background: "#507958", color: "white", }}>XLSX</Button>
                    </Col>
                    <Col md="auto">
                        <Button output="xls" variant="light" onClick={this.createCsvDataToday} style={{ width: 'auto', background: "#135721", color: "white", }}>XLS</Button>
                    </Col>
                </Form.Group>
                <Row className='mb-2'>
                    <Col md={2}>
                        <InputGroup className="mb-3">
                            <InputGroup.Text id="basic-addon3">
                                總盤數
                            </InputGroup.Text>
                            <FormControl id="basic-url" style={{ textAlign: 'right' }} disabled={true} value={this.state.plate_total} aria-describedby="basic-addon3" />
                        </InputGroup>
                    </Col>
                    <Col md={2}>
                        <InputGroup className="mb-3">
                            <InputGroup.Text id="basic-addon3">
                                總訂單數量
                            </InputGroup.Text>
                            <FormControl id="basic-url" style={{ textAlign: 'right' }} disabled={true} value={this.state.order_total} aria-describedby="basic-addon3" />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup className="mb-3">
                            <InputGroup.Text id="basic-addon3">
                                五日平均現場完成量
                            </InputGroup.Text>
                            <FormControl id="basic-url" style={{ textAlign: 'right' }} disabled={true} value={this.state.on_site} aria-describedby="basic-addon3" />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup className="mb-3">
                            <InputGroup.Text id="basic-addon3">
                                五日平均外注完成量
                            </InputGroup.Text>
                            <FormControl id="basic-url" style={{ textAlign: 'right' }} disabled={true} value={this.state.external_injection} aria-describedby="basic-addon3" />
                        </InputGroup>
                    </Col>
                    <Col md={"2"}>
                        < h5 style={{ fontWeight: "bold" }}>註：報表條件為訂單未結案</h5>
                    </Col>
                </Row>


                <Table
                    columns={this.state.columns}
                    dataSource={this.state.tableData}
                    pagination={{ pageSize: 50 }}
                    scroll={{ y: 640 }}
                    size="small"
                    bordered
                />
            </>
        );
    }
}
export default PreproductionBlockV2;
