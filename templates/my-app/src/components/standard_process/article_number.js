import React, { useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom';
import { Image, Form, FloatingLabel, Container, Card, Button, Col, Row, FormControl, } from 'react-bootstrap';
import axios from 'axios';
class ArticleNumber extends React.Component {
    constructor(props) {
        super(props);
        this.state = {

        }
        this.SortChild = React.createRef();
        this.AddItem = this.AddItem.bind(this);

    }
    componentDidMount() {

    }
    AddItem() {
        this.SortChild.current.AddItem();
    }

    render() {
        return (
            <>  
                <hr />
                <Row>
                    <Col md="auto" >
                        <FloatingLabel label="品號" className="mb-2">
                            <Form.Control />
                        </FloatingLabel>
                    </Col>
                    <Col md="auto">
                        <FloatingLabel label="材質" className="mb-2">
                            <Form.Control />
                        </FloatingLabel>
                    </Col>
                    <Col md="auto">
                        <FloatingLabel label="規格" className="mb-2">
                            <Form.Control />
                        </FloatingLabel>
                    </Col>
                </Row>
                <Row >
                    <Col md="4" >
                        <Image thumbnail src="https://image.cache.storm.mg/styles/smg-800x533-fp/s3/media/image/2020/01/31/20200131-052418_U17017_M588719_cd2e.jpg?itok=s0SyFjTD" />
                    </Col>
                    <Col md="2" >
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md="2">
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md="2">
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md="2">
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md="2">
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md="2">
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md="2">
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md="2">
                        <Card>
                            <Card.Header>
                                <Row className='d-flex justify-content-end' >

                                </Row>
                            </Card.Header>
                            <Card.Body>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程順序" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程代號" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                                <Row>
                                    <Col md="auto">
                                        <FloatingLabel label="製程名稱" className="mb-2">
                                            <Form.Control />
                                        </FloatingLabel>
                                    </Col>
                                </Row>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
                <Row>
                </Row>
            </>
        );
    }
}

export default ArticleNumber;