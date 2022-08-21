import React, { useState, useEffect, useRef } from "react";
import Async from "react-select";
import axios from 'axios';
import { Row, Col, InputGroup, Button, Form } from 'react-bootstrap';
import { Input, Select, Space } from 'antd';
import 'antd/dist/antd.css';
import FrequentModal from "./FrequentModal";

function Participant(props, ref) {
    const [loadOptions, setLoadOptions] = useState(false);
    const [optionSelectedList, setOptionSelectedList] = useState([]);
    const FrequentModalRef = useRef()
    useEffect(() => {
        axios.get('/CRM/all_user')
            .then((response) => {
                let option = []
                response.data.map((value, key) => {
                    option.push({ value: value["id"], label: value["name"] })
                })
                setLoadOptions(option)
            })
            .catch((error) => console.log(error))
        setOptionSelectedList([
            ...optionSelectedList,
        ]);
    }, [])
    useEffect(() => {
        if (props.defaltParticipant.length > 0) {
            axios.get('/CRM/all_user')
                .then((response) => {
                    let selected = []
                    response.data.map((value, key) => {
                        props.defaltParticipant.map((val) => {
                            if (value["id"] === parseInt(val)) {
                                selected.push({ value: value["id"], label: value["name"] })
                            }
                        })
                    })
                    setOptionSelectedList(
                        selected,
                    );
                })
                .catch((error) => console.log(error))
        }

    }, [props.defaltParticipant])

    const handleClick = (frequent_group_id) => {
        axios.get('/CRM/frequent_user', {
            params: { frequent_group_id:  frequent_group_id},
        })
            .then((response) => {
                let selected = []
                if (optionSelectedList != "") {
                    selected.push(...optionSelectedList)
                }
                let frequent = response.data
                let temp = []
                optionSelectedList.map((value) => {
                    temp.push(parseInt(value.value))
                })
                Object.keys(frequent).map((select_value) => {
                    if (temp.includes(parseInt(frequent[`${select_value}`]["value"])) == false) {
                        selected.push({
                            value: parseInt(frequent[`${select_value}`]["value"]), label: frequent[`${select_value}`]["label"]
                        })
                    }
                })
                setOptionSelectedList(
                    selected,
                );
                let participant = []
                selected.map((option) => (
                    participant.push(option.value)
                ))
                props.getParticipant(participant)
            })
            .catch((error) => console.log(error))

    };
    const onhandleChange = (options) => {
        setOptionSelectedList(
            options,
        );
        let participant = []
        console.log(options);
        console.log(optionSelectedList)
        options.map((option) => (
            participant.push(option.value)
        ))
        props.getParticipant(participant)
    };
    return (
        <>
            <Row>
                <Col>
                    <FrequentModal handleClick={handleClick} ref={FrequentModalRef} show={false} />
                </Col>
                <Col md={"auto"}>
                    <InputGroup className="mb-3">
                        <InputGroup.Text >
                            與會人
                        </InputGroup.Text>
                        <Async
                            options={loadOptions}
                            onChange={onhandleChange}
                            isMulti
                            value={optionSelectedList}
                        />
                    </InputGroup>
                </Col>
                <Col md={"auto"}>
                    <Button variant="primary" onClick={() => FrequentModalRef.current.openModal()}>常用人名單</Button>{' '}
                </Col>
            </Row>


        </>
    );
}

export default Participant;
