import { Card, Accordion, ProgressBar, Col, Row, Form, Button } from "react-bootstrap";
import 'bootstrap/dist/css/bootstrap.min.css';
import React from "react";
import './CardProgress.css'
import Datatable from "./Datatable";

class CardProgess extends React.Component {
    constructor(props) {
        super(props);
        let todayDate = this.getNowDate();
        this.state = (
            {
                Progess: {
                    percentage: 50
                },
                data: this.props.data,
                status_rate: [],
                status_label: ['waiting', 'processing', 'defect'],
                status_label_ch: ['Waiting', 'Running', 'Bad'],
                datatables: {
                    require: {
                    //   date: new Date().getFullYear() + "-" + this.addz(("0" + (new Date().getMonth() + 1).toString()), 2) + "-" +this.addz(("0" + (new Date().getDate() + 1).toString()), 2),
                      date: "2022-02-22",
                    },
                    thead: [
                      {
                        name: '機台名稱',
                        cell: row => (row.machine_name) || '',
                        width: '200px',
                      },
                      {
                        name: '狀況',
                        cell: row => (row.problem) || '',
                        width: 'auto',
                      },
                    ]
                },
                error: null,
            }
        )
        this.getNowDate = this.getNowDate.bind(this);
        this.postProcess = this.postProcess.bind(this);
        this.addz = this.addz.bind(this);
    }
    addz(num, length) {
        if (num.length >= length) { return num }
        else {
          return this.addz(("0" + num), length)
        }
    }
    postProcess(response) {
        this.setState({ img_path : response.data.src });
        return response;
    }

    getNowDate = () => {
        const date = new Date();
        let formatted_date = `${date.getFullYear()}-${(date.getMonth() + 1 < 10 ? `0${date.getMonth() + 1}` : date.getMonth() + 1)}-${(date.getDate() + 1 < 10 ? `0${date.getDate() + 1}` : date.getDate() + 1)}`
        return formatted_date;
    }
    componentDidMount() {
        this.state.status_rate.push({wating: this.props.data[0].wating})
        this.state.status_rate.push({processing: this.props.data[0].processing})
        this.state.status_rate.push({defect: this.props.data[0].defect})
    }

    render() {
        let row = this;
        return (
            <>
                <Row className="mb-2" >
                    <Col md="4" style={{padding: '0rem 2rem 0rem 0rem'}}>
                        <Card>
                            <Row className="mt-3 mx-2">
                                <Col md="6" style={{padding: '0rem' , whiteSpace: "nowrap"}}><h5>總派工量</h5></Col>
                                <Col md="6" style={{overflowX: 'auto', textAlign: "right", whiteSpace: "nowrap" }}><h5>{`${this.props.data[0].total}件`}</h5></Col>
                            </Row>
                        </Card>
                    </Col>
                    <Col md="4" style={{padding: '0rem 2rem 0rem 0rem'}}>
                        <Card>
                            <Row className="mt-3 mx-2">
                                <Col md="6" style={{whiteSpace: "nowrap"}}><h5>完工率</h5></Col>
                                <Col md="6" style={{overflowX: 'auto', textAlign: "right", whiteSpace: "nowrap" }}><h5>{`${this.props.data[0].percentage}%`}</h5></Col>
                            </Row>
                        </Card>
                    </Col>
                    <Col md="4" style={{padding: '0rem 0.5rem 0rem 0rem'}}>
                        <Card>
                            <Row className="mt-3 mx-2">
                                <Col md="6" style={{whiteSpace: "nowrap"}}><h5>未完工</h5></Col>
                                <Col md="6" style={{overflowX: 'auto', textAlign: "right", whiteSpace: "nowrap" }}><h5>{`${this.props.data[0].unfinished}件`}</h5></Col>
                            </Row>
                        </Card>
                    </Col>
                </Row>
                {this.state.status_label.map((value, index) => (
                    <Row >
                        <Col md="12" style={{padding: '0rem 0.5rem 0rem 0rem'}}>
                            <Card className="my-2">
                                <Row className="mt-3 mx-2">
                                    <Col md="2" style={{whiteSpace: "nowrap"}}><h5>{row.state.status_label_ch[index]}</h5></Col>
                                    <Col md="2" style={{whiteSpace: "nowrap"}}><h5>{`${(row.props.data[0][value].count) || 0}件`}</h5></Col>
                                    <Col md="6">
                                        <ProgressBar style={{padding: '0rem 0rem '}} now={row.props.data[0][value].percentage} animated />
                                    </Col>
                                    <Col md="2"><h5 style={{ textAlign: "center" }}>{`${row.props.data[0][value].percentage}%`}</h5></Col>
                                </Row>
                            </Card>
                        </Col>
                    </Row>
                ))}
                
                <Row >
                    <Col md="12" style={{padding: '0rem 0.5rem 0rem 0rem'}}>
                        <Card className="my-2">
                            <Card.Header ><Row><Col md="2"><h5>異常</h5></Col><Col><h5>{`${this.props.data[0]['abnormal'].count}件`}</h5></Col></Row></Card.Header>
                            <Card.Body>
                                <Datatable date_start="" date_end="" datatables={this.state.datatables} postProcess={this.postProcess} ref={this.tableRef} api_location="/RFID/error" />
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </>
        )
    }
}

export default CardProgess