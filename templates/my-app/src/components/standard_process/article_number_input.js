import React, { useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom';
import { CloseButton, Image, Form, FloatingLabel, Container, Card, Button, Col, Row, FormControl, } from 'react-bootstrap';
import axios from 'axios';
class ArticleNumberInput extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            item: ["1", "2", "3"],
        }
        this.SortChild = React.createRef();
        this.AddItem = this.AddItem.bind(this);
        this.confirm = this.confirm.bind(this);

    }
    componentDidMount() {

    }
    AddItem() {
        let item_temp = this.state.item
        item_temp.push("")
        this.setState({
            item: item_temp
        })
    }
    confirm() {
        console.log(this.state.item)
    }
    render() {
        let item_input = this.state.item
        return (
            <>
                <Row>
                    <Col md="auto">

                        <Row>
                            {item_input.map((value, index) => (
                                <Col>
                                    <FloatingLabel label="品號" name="article_number_input" className="mb-2">
                                        <Form.Control value={value}></Form.Control>
                                    </FloatingLabel>
                                </Col>
                            ))}
                        </Row>
                    </Col>
                    <Col md="auto">
                        <Button variant="secondary" onClick={this.AddItem}>+</Button>{' '}
                    </Col>
                    <Col md="auto">
                        <Button variant="secondary"onClick={this.confirm}>確定</Button>{' '}
                    </Col>
                </Row>
            </>
        );
    }
}

export default ArticleNumberInput;