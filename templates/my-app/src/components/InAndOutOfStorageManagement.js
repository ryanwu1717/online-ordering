import React, { useState, useEffect } from 'react';
import { Table, Col, Row, Button,Card, FormControl, InputGroup } from "react-bootstrap";
import { FaTimes } from "react-icons/fa";

const InAndOutOfStorageManagement = (props) => {
    const [data, setData] = useState({
        manager_id: '001',
        manager_name: '管理人01',
        delivery_id: '001',
        delivery_name: 'abc',
        commodity_name: '貨1',
        commodity_weight: 50,
    });
    const addz = (num, length) => {
        if (num.length >= length) { return num }
        else {
          return addz(("0" + num), length)
        }
    }
    const [today_date, setToday_date] = useState();
    const [groupRef, setGroupRef] = useState([]);
    const [currentGroup, setCurrentGroup] = useState([
        {
            name: '自訂', 
            content: [] 
        },
    ])

    

    useEffect(() => {
        setToday_date(new Date().getFullYear() + "-" + addz(((new Date().getMonth() + 1).toString()), 2) + "-" + addz((new Date().getDate().toString()), 2))
    }, []);

    return (
        <Row>
            <Col md="12">
                <Card className="my-1 align-top" style={{ borderColor: "#a5a5a5"}}>
                    <Card.Header className="justify-content-center align-items-center" style={{ background: "#ebedec", borderColor: "#a5a5a5", color: "#4E5068", fontWeight: "bold" }}>出入庫管理</Card.Header>
                    <Card.Body>
                        <Row style={{display: 'flex', justifyContent:'right'}}>
                            <Col md="auto">
                                <InputGroup className="mb-3">
                                    <InputGroup.Text>日期</InputGroup.Text>
                                    <FormControl
                                    aria-describedby="inputGroup-sizing-default"
                                    disabled
                                    value= {today_date}
                                    style={{ background:"white" }}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                        <Row>
                            <Col md="6">
                                <Card className="my-1 align-top" style={{ borderColor: "#6e6e6e" }}>
                                    <Card.Header className="justify-content-center align-items-center" style={{ borderColor: "#6e6e6e", background: "#ffefe8", color: "#4E5068", fontWeight: "bold" }}>管理人</Card.Header>
                                    <Card.Body>
                                        <Row>
                                            <h5>姓名：{data.manager_name}</h5>
                                        </Row>
                                        <Row>
                                            <h5>編號：{data.manager_id}</h5>
                                        </Row>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md="6">
                                <Card className="my-1 align-top" style={{ borderColor: "#6e6e6e" }}>
                                    <Card.Header className="justify-content-center align-items-center" style={{ borderColor: "#6e6e6e", background: "#fdf2ce", color: "#4E5068", fontWeight: "bold" }}>送貨人/領物人</Card.Header>
                                    <Card.Body>
                                        <Row>
                                            <h5>姓名：{data.delivery_name}</h5>
                                        </Row>
                                        <Row>
                                            <h5>編號：{data.delivery_id}</h5>
                                        </Row>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>
                        <Row style={{display: 'flex', justifyContent:'right'}}>
                            <Col md="auto">
                                <Button className="my-2" variant="light" style={{ fontWeight: "bold", background:"#6e7d93", color: "white", }}>影像擷取</Button>
                            </Col>
                        </Row>
                        <Row>
                            <Col md="6">
                                <Card className="my-1 align-top" style={{ borderColor: "#6e6e6e" }}>
                                    <Card.Header className="justify-content-center align-items-center" style={{ borderColor: "#6e6e6e", background: "#e3f0fc", color: "#4E5068", fontWeight: "bold" }}>貨品資訊</Card.Header>
                                    <Card.Body>
                                        <Row>
                                            <h5>品名：{data.commodity_name}</h5>
                                        </Row>
                                        <Row>
                                            <Col className='my-2' md="auto">
                                                <h5>重量：{data.commodity_weight}</h5>
                                            </Col>
                                            <Col>
                                                <Row>
                                                    <Col className='my-2' md="auto"><h5>數量：</h5></Col>
                                                    <Col md="4">
                                                        <FormControl
                                                        aria-describedby="inputGroup-sizing-default"
                                                        type='number'
                                                        />
                                                    </Col>
                                                </Row>
                                            </Col>
                                        </Row>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md="6">
                                <img src="https://livingtechlearn.files.wordpress.com/2017/02/iphone-ed.png" alt="" className="img-fluid" style={{ border: "1px solid #a39e9e" }}/>
                            </Col>
                        </Row>
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    );
}

export default InAndOutOfStorageManagement;
