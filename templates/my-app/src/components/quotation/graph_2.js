import React, { useState, useEffect, useReducer, useRef } from 'react';
import axios from 'axios';
import styled from 'styled-components'
import { Chart } from 'chart.js';
import { Card, Row, Col, Button, InputGroup, FormControl } from 'react-bootstrap';
import moment from 'moment'


const Graph_2 = (props) => {
    const [StartDate, setStartDate] = useState(moment().subtract(1, 'days').format('YYYYMMDD HH:mm:ss'));
    const [EndDate, setEndDate] = useState(moment().format('YYYYMMDD HH:mm:ss'));
    const StartDateTime = useRef("");
    const EndDateTime = useRef("");
    const myChartRef = useRef(null);
    const [myChart, setMyChart] = useState(null);
    // 未完成
    let [chart_labels, setChart_labels] = useState([]);
    let [chart_data, setChart_data] = useState([]);
    // 已完成
    let [chart_labels_finish, setChart_labels_finish] = useState([]);
    let [chart_data_finish, setChart_data_finish] = useState([]);
    const [backgroundColor, setBackgroundColor] = useState([]);
    const [borderColor, setBorderColor] = useState([]);
    // 未完成
    const [processes_id_show, setProcesses_id_show] = useState([]);
    const [processes_name_show, setProcesses_name_show] = useState([]);
    const [processes_data, setProcesses_data] = useState([]);
    // 已完成
    const [processes_id_show_finish, setProcesses_id_show_finish] = useState([]);
    const [processes_name_show_finish, setProcesses_name_show_finish] = useState([]);
    const [processes_data_finish, setProcesses_data_finish] = useState([]);
    const [ChangeGraph, setChangeGraph] = useState(false);

    function refreshBarChart() {
        let temp_data = [];
        let temp_data_finish = [];

        processes_id_show.map((value, index) => (
            temp_data.push({ label: processes_name_show[index], data: processes_data[index] })
        ))
        processes_id_show_finish.map((value, index) => (
            temp_data_finish.push({ label: processes_name_show_finish[index], data: processes_data_finish[index] })
        ))
        setChart_labels([]);
        setChart_data([]);
        setChart_labels_finish([]);
        setChart_data_finish([]);
        temp_data.map((item) => {
            chart_labels.push(item.label)
            chart_data.push(item.data)
            let backgroundColor1 = Math.floor(Math.random() * 150);
            let backgroundColor2 = Math.floor(Math.random() * 150);
            let backgroundColor3 = Math.floor(Math.random() * 150);
            backgroundColor.push(`rgba(${backgroundColor1}, ${backgroundColor2}, ${backgroundColor3}, 0.2)`)
            borderColor.push(`rgba(${backgroundColor1}, ${backgroundColor2}, ${backgroundColor3}, 1)`)
        })
        temp_data_finish.map((item) => {
            chart_labels_finish.push(item.label)
            chart_data_finish.push(item.data)
            let backgroundColor1 = Math.floor(Math.random() * 150);
            let backgroundColor2 = Math.floor(Math.random() * 150);
            let backgroundColor3 = Math.floor(Math.random() * 150);
            backgroundColor.push(`rgba(${backgroundColor1}, ${backgroundColor2}, ${backgroundColor3}, 0.2)`)
            borderColor.push(`rgba(${backgroundColor1}, ${backgroundColor2}, ${backgroundColor3}, 1)`)
        })
        let ctx = myChartRef.current.getContext('2d');
        if (myChart !== null) {
            myChart.destroy()
        }
        setMyChart(new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chart_labels,
                datasets: [{
                    label: '未處理',
                    data: chart_data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1
                }, {
                    label: '已處理',
                    data: chart_data_finish,
                    type: 'bar',
                    backgroundColor: 'rgba(0, 0, 0, 0.1)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                }
                ]
            },
            options: {
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        }));
    };
    useEffect(() => {
        let params = {};
        params["start"] = StartDate;
        params["end"] = EndDate;
        params["id"] = props.id;
        params["type"] = 'temperary';
        axios
            .get("/modifyprocess/outsourcer/temperary_temp", {
                params: params,
            })
            .then((response) => {
                setProcesses_name_show([])
                setProcesses_id_show([])
                setProcesses_data([])
                console.log(response)
                response["data"].map((value, item) => {
                    let un_name = value["外包廠商"] + "-" + value["製程名稱"]
                    return (setProcesses_name_show(old => [...old, un_name]),
                        setProcesses_id_show(old => [...old, item]),
                        setProcesses_data(old => [...old, value["unfinish"]]))
                })
                setProcesses_name_show_finish([])
                setProcesses_id_show_finish([])
                setProcesses_data_finish([])
                response["data"].map((value, item) => {
                    let finish_name = value["外包廠商"] + "-" + value["製程名稱"]
                    return (setProcesses_name_show_finish(old => [...old, finish_name]),
                        setProcesses_id_show_finish(old => [...old, item]),
                        setProcesses_data_finish(old => [...old, value["finish"]]))
                })
                setChangeGraph(!ChangeGraph)
            });
    }, [StartDate, EndDate])
    const DayChange = () => {
        setStartDate(moment(StartDateTime.current.value).format('YYYYMMDD HH:mm:ss'))
        // console.log(EndDateTime.current.value)
        if (EndDateTime.current.value.length != 0) {
            setEndDate(moment(EndDateTime.current.value).format('YYYYMMDD HH:mm:ss'))
        }
    }
    useEffect(() => {
        refreshBarChart()
    }, [ChangeGraph])
    const Rfid_title = styled.span`
    color: #FFF;
    background: #889bff !important;
    font-weight: bold;
    text-align: center;
    border: #889bff;
`
    return (
        <div className='Graph'>
            <Row className='w-100 m-0'>
                <Col md='12'>
                    <Card className='shadow mb-4 w-100'>
                        <Card.Title md='12' as="h3" className='position-relative mb-5'>
                            <Rfid_title className="badge rounded position-absolute p-3 text-center top-0 start-0 ">目前外包狀況</Rfid_title>
                        </Card.Title>
                        <Row className='d-flex px-5 mt-4'>
                            <Col md='3' sm='6' xs='12' className='ps-0 d-flex align-items-center'>
                                <label className="form-label fw-bold mb-0" htmlFor="filterDate_start">起：</label>
                                <input type="datetime-local" name="filterDate" ref={StartDateTime} data-type="start" className="form-control" id="filterDate_start" onChange={DayChange} />
                            </Col>
                            <Col md='3' sm='6' xs='12' className='ps-0 d-flex align-items-center'>
                                <label className="form-label fw-bold mb-0" htmlFor="filterDate_end">迄：</label>
                                <input type="datetime-local" name="filterDate" data-type="end" className="form-control" ref={EndDateTime} id="filterDate_end" onChange={DayChange} />
                            </Col>
                            <Col md='12' className='mt-2'>
                                <canvas ref={myChartRef}></canvas>
                            </Col>
                        </Row>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
export default Graph_2;