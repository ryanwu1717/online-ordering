import React, { useState, useEffect, useCallback, useMemo } from 'react';

import { Offcanvas, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';
import axios from 'axios';
import { Popconfirm } from 'antd';

class History extends React.Component {
    constructor(props) {
        super(props);
        this.can = false
        this.state = {
            show: this.props.show,
            meets: [],
            meet_id: this.props.meet_id,
            save_success: this.props.save_success,
            style: {
                textOverflow: 'ellipsis',
                overflow: 'hidden',
                whiteSpace: "nowrap",
                width: '100%',
                maxWidth: '100%',
                height: "50px",
                fontSize: "18px",
                fontWeight: "bold",
                background: "white",
                color: "#5e789f",
                borderColor: "#5e789f",
                borderWidth: "medium"
            },
            style_activy: {
                textOverflow: 'ellipsis',
                overflow: 'hidden',
                whiteSpace: "nowrap",
                width: '100%',
                maxWidth: '100%',
                height: "50px",
                fontSize: "18px",
                fontWeight: "bold",
                background: "#5e789f",
                color: "white",
                borderColor: "#5e789f",
                borderWidth: "medium"
            },
            selected_meet_id: this.props.meet_id
        };

        this.handleClose = this.handleClose.bind(this);
        this.handleSelected = this.handleSelected.bind(this);
        this.handleShow = this.handleShow.bind(this);
        this.getMeet = this.getMeet.bind(this);
        this.modalRef = React.createRef();
        this.saveMeet = this.saveMeet.bind(this);
        this.saveSuccess = this.saveSuccess.bind(this);

    }
    componentDidMount() {
        this.getMeet()
    }
    componentDidUpdate(prevProps, prevState, snapshot) {
        if (this.props.meet_id !== prevProps.meet_id) {
            this.getMeet()
        }
        if (this.props.save_success !== prevProps.save_success && this.props.save_success) {
            this.getMeet()
        }
    }
    handleClose() {
        this.setState({
            show: false
        })
    }
    handleShow() {
        this.setState({
            show: true
        })
    }
    getMeet() {
        axios.get('/CRM/record_meet')
            .then((response) => {
                this.setState({
                    meets: response.data.data
                })
            })
            .catch((error) => console.log(error))
    }
    handleSelected(e, meet_id, save) {
        this.setState({
            selected_meet_id: meet_id
        })
        if (save) {
            if (this.props.meet_id === 0) {
                this.props.addMeet()
            }
            else {
                this.props.saveMeet("modal")
            }
        }
        else {
            this.props.changeMeet(meet_id)
        }
    }
    saveMeet() {
        this.props.saveMeet("modal")
    }
    saveSuccess(is_success) {
        if (is_success) {
            this.props.changeMeet(this.state.selected_meet_id)
        }
    }
    render() {
        console.log(this.props.meet_id)
        return (
            <>
                <Offcanvas placement={"end"} show={this.state.show} onHide={this.handleClose} >
                    <Offcanvas.Header closeButton>
                        <Offcanvas.Title>歷史會議</Offcanvas.Title>
                    </Offcanvas.Header>
                    <Offcanvas.Body>
                        <Row>
                            {Object.keys(this.state.meets).map((index) => (
                                this.props.save_success ?
                                    <Col sm={12}>
                                        <Button className="mx-2 my-1" meet_id={this.state.meets[index].meet_id}
                                            onClick={e => this.handleSelected(e, this.state.meets[index].meet_id, false)} variant="light"
                                            style={this.state.meets[index].meet_id == this.props.meet_id ? this.state.style_activy : this.state.style}>{this.state.meets[index].meet_name}</Button>
                                    </Col>
                                    :
                                    <Popconfirm
                                        title="您尚未儲存更改，請問是否需儲存變更?"
                                        onConfirm={e => this.handleSelected(e, this.state.meets[index].meet_id, true)}
                                        onCancel={e => this.handleSelected(e, this.state.meets[index].meet_id, false)}
                                        okText="是"
                                        cancelText="否">
                                        <Col sm={12}>
                                            <Button className="mx-2 my-1" meet_id={this.state.meets[index].meet_id}
                                                variant="light"
                                                style={this.state.meets[index].meet_id == this.props.meet_id ? this.state.style_activy : this.state.style}>{this.state.meets[index].meet_name}</Button>
                                        </Col >
                                    </Popconfirm>

                            ))}
                            {
                                this.props.meet_id !== "0" ? null :
                                    <Col sm={12}>
                                        <Button className="mx-2 my-1"
                                            variant="light"
                                            style={this.state.style_activy}>[尚未儲存案件]</Button>
                                    </Col>
                            }
                        </Row>
                    </Offcanvas.Body>
                </Offcanvas>
            </>
        );
    }
}

export default History;