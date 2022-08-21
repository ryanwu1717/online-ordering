import React, { Component } from 'react';
import { convertFromRaw } from 'draft-js';
import { FloatingLabel, Image, FormControl, InputGroup, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';
import AddElseDiscussModal from './add_else_ discuss_modal';
import DataTable from 'react-data-table-component';
import axios from 'axios';

class ElseDiscuss extends Component {
    constructor(props) {
        super(props);
        this.state = {
            columns: [],
            datatable_col: [
            ],
        }
        this.setTableHeader = this.setTableHeader.bind(this);
        this.sendTextToEditor = this.sendTextToEditor.bind(this);
        this.modalChild = React.createRef();
        this.get_discuss = this.get_discuss.bind(this);
        this.add_discuss = this.add_discuss.bind(this);
        this.delete_discuss = this.delete_discuss.bind(this);


    }
    componentWillMount() {
        this.setTableHeader();
        this.get_discuss();
    }
    sendTextToEditor(e) {
        this.props.sendTextToEditor(e)
    }
    add_discuss() {
        this.modalChild.current.openModal();
    }
    get_discuss() {
        axios.get('/CRM/discuss')
            .then((response) => {
                this.setState({
                    datatable_col: response.data.data
                })
            })
            .catch((error) => console.log(error))
    }
    delete_discuss(event) {
        let discuss_id = event.target.attributes.discuss_id.value;
        let data = []
        data.push(discuss_id)

        axios.delete('/CRM/discuss', {
            data: data
        }
        )
            .then((response) => {
              
                if (response.data.status == "success") {
                    this.get_discuss()
                }
            })
            .catch((error) => console.log(error))
    }
    setTableHeader() {
        this.setState({
            columns: [
                {
                    name: '建立日期',
                    selector: row => row.create_date,
                },
                {
                    name: '標題',
                    selector: row => row.discuss_name,
                },
                {
                    name: '說明',
                    selector: row => row.discuss_content,
                },
                {
                    cell: (row) =>
                        <Row>
                            <Col sm={"12"}>
                                <Button content={row.discuss_content} tracking_name={row.discuss_name} create_date={row.create_date} onClick={this.sendTextToEditor} style={{ width: 'auto', background: "#8F857D", color: "white", border: 'none', fontWeight: "bold" }}>加入</Button>{' '}
                            </Col>
                            <Col sm={"12"}>
                                <Button discuss_id={row.discuss_id} onClick={this.delete_discuss} style={{ width: 'auto', background: "#EB8A8A", color: "white", border: 'none', fontWeight: "bold" }}>刪除</Button>{' '}
                            </Col>

                        </Row>
                    ,
                    width: "20%",
                    ignoreRowClick: true,
                    allowOverflow: true,
                }
            ],
        })
    }
    // /CRM/complaint/complaint/today
    render() {
        return (
            <>
                <Row className='d-flex justify-content-end my-2'>
                    <Col md={"auto"} >

                        <AddElseDiscussModal get_discuss={this.get_discuss} ref={this.modalChild} />
                        <Button  style={{ width: 'auto', background: "#7286a0", color: "white", border: 'none', fontWeight: "bold" }} onClick={this.add_discuss}>新增</Button>{' '}
                    </Col>
                </Row>
                <Row>
                    <DataTable
                        columns={this.state.columns}
                        data={this.state.datatable_col}
                        fixedHeaderScrollHeight="700px"
                        pagination
                        responsive
                        subHeaderAlign="right"
                        subHeaderWrap
                        paginationPerPage={50}
                        fixedHeader
                    />
                </Row>
            </>
        )
    }
}
export default ElseDiscuss;