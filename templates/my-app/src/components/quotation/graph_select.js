import React from "react";
import { Button, Accordion, Row, Col, Form } from 'react-bootstrap';
import axios from 'axios';

class AccordionShowProcess extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            all_processes: [],
            test_processes:[],
            processes_id_show: this.props.processes_id_show,
            processes_name_show: this.props.processes_name_show

        };
    }
    selectAllHandler = (e) => {
        let row = this;
        if (e.target.checked === true) {
            document.getElementsByName(e.target.attributes.line_id.value).forEach((rate) => {
                rate.checked = true
                if (row.props.processes_id_show.indexOf(rate.id) === -1) {
                    row.props.processes_id_show.push(rate.id);
                    row.props.processes_name_show.push(rate.attributes.process_name.value);
                }
            });
        } else {
            document.getElementsByName(e.target.attributes.line_id.value).forEach((rate) => {
                rate.checked = false
                let processes_id_show_check_list = [];
                let processes_name_show_check_list = [];
                row.props.processes_id_show.map((item, idx) => {
                    if (item === rate.id) {
                        row.props.processes_id_show.splice(idx, 1);
                        row.props.processes_name_show.splice(idx, 1);
                    }
                })
                row.setState({ processes_id_show: processes_id_show_check_list })
                row.setState({ processes_name_show: processes_name_show_check_list })
            });
        }
        this.props.refreshBarChart();
    }
    categoryCheckboxFilterHandler = (e) => {
        if (e.target.checked == true) {
            this.props.processes_id_show.push(e.target.id);
            this.props.processes_name_show.push(e.target.attributes.process_name.value);
        } else {
            let processes_id_show_check_list = [];
            let processes_name_show_check_list = [];
            let row = this;
            this.props.processes_id_show.map((item, idx) => {
                if (item === e.target.id) {
                    row.props.processes_id_show.splice(idx, 1);
                    row.props.processes_name_show.splice(idx, 1);
                }
            })
            this.setState({ processes_id_show: processes_id_show_check_list })
            this.setState({ processes_name_show: processes_name_show_check_list })
        }
        this.props.refreshBarChart();
    };
    processesOnClick(e) {
        document.getElementById(e.target.attributes.line.value).hidden = !document.getElementById(e.target.attributes.line.value).hidden;
        if (e.target.classList.contains("nonClicked")) {
            e.target.classList.remove("nonClicked");
            e.target.classList.add("hadClicked");
            Object.assign(e.target.style, { width: 'auto', background: "#5e789f", color: "white", borderColor: "#5e789f" });
        } else {
            e.target.classList.remove("hadClicked");
            e.target.classList.add("nonClicked");
            Object.assign(e.target.style, { width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" });
        }
    }
    componentDidMount() {
        axios
        .get('/RFID/process/names')
        .then(response => {
            this.setState({ all_processes: response.data });
            console.log(response.data)
        })
        .catch(function (error) {
            console.log(error);
        });
        this.setState({ test_processes: old => [...old, this.props.processes_name_show] });

        // this.setState({ all_processes: this.props.processes_name_show });

    }
    render() {
        return (
            <>
                <Row>
                    {this.state.all_processes.map((value, index) => (
                        value['??????'] !== null ?
                            <Button className="mx-1 my-1 nonClicked" variant="light" onClick={this.processesOnClick.bind(this)} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} line={value['????????????'].trim()} >{value['????????????']}</Button>
                            : null
                    ))}
                </Row>
                <Accordion defaultActiveKey={['0']} alwaysOpen>
                    <Accordion.Item eventKey="0">
                        <Accordion.Header>??????????????????</Accordion.Header>
                        <Accordion.Body>

                            {this.state.all_processes.map((value, index) => (
                                value['??????'] !== null ?
                                    <div id={value['????????????'].trim()} hidden={true}>
                                        <Row>
                                            {value['??????'].map((process_value, process_index) => (
                                                <Col md='3'>
                                                    <Form.Group controlId={process_value['????????????'][0]}>
                                                        <Form.Check type="checkbox" label={process_value['????????????'][0]} name={value['????????????'][0]} process_name={process_value['????????????'][0]} id={process_value['????????????'][0]} onChange={e => this.categoryCheckboxFilterHandler(e)} defaultChecked={this.state.processes_id_show.indexOf(process_value['????????????'][0]) !== -1 ? true : false} />
                                                    </Form.Group>
                                                </Col>
                                            ))}
                                        </Row>
                                    </div> : null
                            ))}
                        </Accordion.Body>
                    </Accordion.Item>
                </Accordion>
            </>
        )
    }
}

export default AccordionShowProcess