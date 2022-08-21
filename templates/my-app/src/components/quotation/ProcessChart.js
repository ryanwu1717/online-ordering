import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Card, Row, Col, Button, InputGroup } from 'react-bootstrap';
import './ProcessChart.css';

const ProcessChart = (props) => {

    return (
        <Row className='w-100 m-0'>
            <Col md='12'>
                <Card className='shadow mb-4 w-100'>
                    <Row className='overflow-auto'>
                        <Col md='4'>
                            <Card className='alert-primary'>
                                <Card.Body className='d-flex align-items-center'>
                                    <Card.Text className='text-nowrap m-0 pe-2'>製程</Card.Text>
                                    <InputGroup className='responsive nowrap d-flex'>
                                        <Button className="resetBtn btn-sm" variant="outline-secondary">待上傳圖檔</Button>
                                        <Button className="resetBtn btn-sm" variant="outline-secondary">待查詢歷史訂單</Button>
                                        <Button className="resetBtn btn-sm" variant="outline-secondary">待全圖比對</Button>
                                        <Button className="resetBtn btn-sm" variant="outline-secondary">待流程確認</Button>
                                    </InputGroup>
                                </Card.Body>
                            </Card>
                        </Col>
                        <Col></Col>
                        <Col></Col>
                    </Row>
                </Card>
            </Col>
        </Row>
    );
}
export default ProcessChart;