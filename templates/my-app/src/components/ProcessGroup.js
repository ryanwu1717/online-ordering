import SearchTextArea from './SearchTextArea';
import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import { Table, Col, Row, FormControl, Button, Card, Container, Accordion } from "react-bootstrap";
import { FaIndustry } from "react-icons/fa";
const ProcessGroup = (props) => {


    const [group, setGroup] = useState(['']);
    const [selectedOption, setSelectedOption] = useState([]);
    const [options, setOptions] = useState([]);

    useEffect(() => {
        if (props.process_id !== undefined || props.process_id !== null) {
            let options_temp = [];
            props.process_id.map((value, index) => {
                options_temp.push({ value: value, label: props.process_name[index] })
            })
            setOptions(options_temp);
        }
    }, [props.process_id]);
    const [searchData, setSearchData] = useState([
        { idx: 0, row_idx: 0, 'height': 100, 'label': '註記:', 'id': 'sign_date_7', 'type': 'input', 'value': '', 'disabled': false },
    ]);
    const handleChange = (e) => {
        let searchData_temp = [...searchData];
        let row_data = searchData_temp[e.target.attributes.idx.value]
        row_data.value = e.target.value;
        searchData_temp[e.target.attributes.idx.value] = row_data;
        setSearchData(JSON.parse(JSON.stringify(searchData_temp)))
    };
    const handleAdd = (e) => {
        let group_temp = [...group]
        group_temp.push("")
        setGroup(group_temp)
    }
    const handleSelectedOption = (selectedOptions, e) => {
        console.log(e)
        setSelectedOption(selectedOptions);
    }
    console.log(selectedOption)
    return (
        <>
            <Card className="mx-2 my-1 align-top" style={{ display: "inline-block", width: "100%" }}>
                <Card.Header className="justify-content-center align-items-center" style={{ fontWeight: "bold" }} >製程組</Card.Header>
                <Card.Body>
                    <Row style={{ textAlign: 'center', }}>
                        <Col md="3"><h5 style={{ fontWeight: 'bold' }}>組別</h5></Col>
                        <Col md="5"><h5 style={{ fontWeight: 'bold' }}>製程</h5></Col>
                        <Col md="4"><h5 style={{ fontWeight: 'bold' }}>註記</h5></Col>
                    </Row>
                    {
                        group.map((value, index) => {
                            return (
                                <Row>
                                    <Col className='my-2' md="3">
                                        <FormControl
                                            aria-describedby="inputGroup-sizing-default"
                                            type='text'
                                        />
                                    </Col>
                                    <Col className='my-2' md="5">
                                        <Select
                                            idx={index}
                                            options={options}
                                            closeMenuOnSelect={false}
                                            onChange={handleSelectedOption}
                                            isMulti
                                        />
                                    </Col>
                                    <Col md="4">
                                        <SearchTextArea onChange={handleChange} searchData={searchData} />
                                    </Col>
                                </Row>
                            )
                        })
                    }

                    <Row className='my-2'>
                        <Col>
                            <Button variant="light" onClick={handleAdd} style={{ fontWeight: "bold", background: "#6b778d", color: "white", borderColor: "#6b778d", borderWidth: "medium" }}>+</Button>
                        </Col>
                    </Row>
                </Card.Body>
            </Card>
        </>
    );
}

export default ProcessGroup;
