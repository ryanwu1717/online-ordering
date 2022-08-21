import React, { Component } from 'react';
import { convertFromRaw } from 'draft-js';
import { FloatingLabel, Image, FormControl, InputGroup, Button, Container, Card, Row, Col, Form, ThemeProvider } from 'react-bootstrap';
import axios from 'axios';
import DataTable from 'react-data-table-component';
import AddTrackingModal from './add_tracking_modal';
class TrackingMatters extends Component {
    constructor(props) {
        super(props);
        this.state = {
            columns: [

            ],
            complete: this.props.complete,
            datatable_col: [],
        }
        this.modalChild = React.createRef();
        this.get_tracking = this.get_tracking.bind(this)
        this.add_tracking = this.add_tracking.bind(this)
        this.finish = this.finish.bind(this)
        this.sendTextToEditor = this.sendTextToEditor.bind(this)
        this.setTableHeader = this.setTableHeader.bind(this)
    }
    componentDidMount() {
        this.get_tracking()
    }
    componentWillMount() {
        this.setTableHeader()
    }
    setTableHeader() {
        this.setState({
            columns: [
                {
                    name: "完成追蹤",
                    cell: (row) => {
                        let complete = ``
                        if (row.is_complete == true) {
                            complete =
                                <Col sm={"auto"}>
                                    <label>{row.complete_date || "-"}</label>
                                </Col>
                        }
                        else {
                            complete = <>
                                <Col sm={"auto"}>
                                    <Button onClick={this.sendTextToEditor} content={row.content} tracking_name={row.name} style={{ width: 'auto', background: "#8F857D", color: "white", border: 'none', fontWeight: "bold" }} variant="light" create_date={row.create_date} >加入</Button>{' '}
                                </Col>
                                <Col sm={"auto"}>
                                    <Button onClick={this.finish} track_id={row.id} variant="warning" style={{ width: 'auto', background: "#FED766", color: "white", border: 'none', fontWeight: "bold" }}>完成</Button>{' '}
                                </Col>
                            </>
            
                        }
                        return (
                            <Row>
                                {complete}
                            </Row>
                        )
            
                    }
                    ,
                    ignoreRowClick: true,
                    width: 'auto',
                    // minWidth:  '150px',
            
                },
                {
                    name: '建立日期',
                    cell: row => row.create_date,
                    width: 'auto',
                },
                {
                    name: '標題',
                    cell: row => row.name,
                    width: 'auto',
                },
                {
                    name: '說明',
                    cell: row => row.content,
                    
                    width: 'auto',

                },
                {
                    name: '追蹤人',
                    cell: row => row.person_in_charge_name,
                    width: 'auto',

                },
                {
                    name: '權責單位',
                    cell: row => row.module_name,
                    width: 'auto',

                },
            ],
        })
    }
    add_tracking() {
        this.modalChild.current.openModal()
    }
    get_tracking() {
        axios.get('/CRM/complaint/tracking', {
            params: {
                complete: this.state.complete
            }
        })
            .then((response) => {
                this.setState({
                    datatable_col: response.data.data
                })
            })
            .catch((error) => console.log(error))
    }
    finish(event) {
        let tracking_id = event.target.attributes.track_id.value;
        let data = new Object;
        data["tracking_id"] = tracking_id
        data["complete"] = true

        axios.patch('/CRM/complaint/tracking/complete',
            data
        )
            .then((response) => {
                console.log(response.data)
                if (response.data.status == "success") {
                    this.get_tracking()
                }
            })
            .catch((error) => console.log(error))
    }
    sendTextToEditor(e) {
        this.props.sendTextToEditor(e)
    }
    // /CRM/complaint/complaint/today
    render() {
        return (
            <>
                <Row className='d-flex justify-content-end my-2'>
                    <Col md={"auto"} >
                        <AddTrackingModal get_tracking={this.get_tracking} ref={this.modalChild} />
                        { this.state.complete ? null :<Button style={{ width: 'auto', background: "#7286a0", color: "white", border: 'none', fontWeight: "bold" }} onClick={this.add_tracking} >新增</Button>}
                    </Col>
                </Row>
                <Row>
                    <DataTable
                        columns={this.state.columns}
                        data={this.state.datatable_col}
                        pagination
                        // responsive
                        subHeaderAlign="right"
                        // subHeaderWrap
                        paginationPerPage={50}
                    // fixedHeader
                    />
                </Row>
            </>
        )
    }
}
export default TrackingMatters;