import React, { useState, useEffect } from 'react';
import { Table, Col, Row, Button, Card, Container, Accordion } from "react-bootstrap";
import { FaIndustry } from "react-icons/fa";
const MachineOverview = (props) => {
    const [group, setGroup] = useState([
        {
            name: '自訂',
            content: []
        },
        {
            name: '放電加工組',
            content: [
                { activity: 1, floor: 2, operator: "操作人員01", time: 1, machine_id: "B01", running_count: 12, img: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRB1AitkBHrOD9MA5MUcvyB4dOdU9Kr81UiMPr8JOeT3u5dKYnplTmLd5TeMugRT9VU0WM&usqp=CAU" },
                { activity: 0, floor: 2, operator: "操作人員02", time: 1.5, machine_id: "B02", running_count: 3, img: "data:image/jpeg;base64,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" },
            ]
        },
        {
            name: '拋光組',
            content: [
                { activity: 1, floor: 2, operator: "操作人員02", time: 0.5, machine_id: "C01", running_count: 10, img: "https://www.yuli9698.com/archive/image/article3/u_2057100976,3975258290_fm_214_gp_0.jpg" },
            ]
        },
        {
            name: '工務室',
            content: [
                { activity: 0, floor: 2, operator: "操作人員01", time: 1, machine_id: "C11", running_count: 0, img: "" },
            ]
        },

    ]);

    const [groupRef, setGroupRef] = useState([]);
    const [eventKey, setEventKey] = useState([]);
    const [openCloseButton, setOpenCloseButton] = useState("一鍵全關");
    const [currentGroup, setCurrentGroup] = useState([
        {
            name: '自訂',
            content: []
        },
    ]);

    useEffect(() => {
        let ref_arr = [React.createRef()];
        group.map((value, index) => (
            ref_arr.push(React.createRef())
        ))
        setGroupRef(ref_arr)
        
    }, []);
    useEffect(() => {
        if(groupRef[1] !== undefined) {
            groupRef[1].current.click()
        }
    }, [groupRef]);
    

    const handleSelect = (e) => {
        group.map((value, index) => (
            Object.assign(groupRef[index].current.style, { width: 'auto', fontSize: "18px", fontWeight: "bold", background: "white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium" })
        ))
        Object.assign(e.target.style, { width: 'auto', background: "#5e789f", color: "white", borderColor: "#5e789f" });
        setCurrentGroup([group[e.target.attributes.group_idx.value]])

        let eventKeyArr = [];
        group[e.target.attributes.group_idx.value].content.map((value, index) => (
            eventKeyArr.push("1")
        ))
        setEventKey(eventKeyArr)
    }

    const handleClickAccordion = (e) => {
        if (eventKey[e.target.attributes.idx.value] === '0') {
            let eventKeyArr = [...eventKey];
            eventKeyArr[e.target.attributes.idx.value] = '1'
            setEventKey(eventKeyArr)
        } else {
            let eventKeyArr = [...eventKey];
            eventKeyArr[e.target.attributes.idx.value] = '0'
            setEventKey(eventKeyArr)
        }
        
    }

    const handleOpenAll = (e) => {
        if (openCloseButton === "一鍵全開") {
            setOpenCloseButton("一鍵全關")
            let eventKeyArr = eventKey;
            eventKeyArr.map((value, index) => (
                eventKeyArr[index] = '1'
            ))
            setEventKey(eventKeyArr)
        } else {
            setOpenCloseButton("一鍵全開")
            let eventKeyArr = eventKey;
            eventKeyArr.map((value, index) => (
                eventKeyArr[index] = '0'
            ))
            setEventKey(eventKeyArr)
        }

    }

    return (

        <div>
            <Row>
                <Col md='10'>
                    {group.map((value, index) => (
                        <Button className="mx-2 my-2" ref={groupRef[index]} group_idx={index} onClick={e => handleSelect(e)} variant="light" style={{ fontSize: "18px", fontWeight: "bold", background: "white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium" }}>{value.name}</Button>
                    ))
                    }
                </Col>
                <Col md='2' style={{ display: 'flex', justifyContent: 'right' }}>
                    <Button className="my-2" variant="light" onClick={e => handleOpenAll(e)} style={{ fontSize: "18px", fontWeight: "bold", background: "#204B57", color: "white", }}>{openCloseButton}</Button>
                </Col>
            </Row>
            <Row className='mx-1'>
                {
                    currentGroup.map((group_value, group_index) => (
                        group_value['content'].map((value, index) => {
                            return (
                                <Col md="3">
                                    <Accordion activeKey='1' flush>
                                        <Accordion.Item eventKey={eventKey[index]}>
                                            <Accordion.Button idx={index} variant="light" onClick={handleClickAccordion} style={{ backgroundColor: value.activity === 1 ? '#b5ffd7' : '#ccd2d6', fontWeight: 'bold', color: 'black' }}>{value.floor}F - {group_value.name} - {value.machine_id}</Accordion.Button>
                                            <Accordion.Body style={{ borderStyle: 'none solid solid solid', borderColor: '#ccd2d6' }}>
                                                {
                                                    <>
                                                        <Row className='mb-4'>
                                                            <img src={value.img} alt="" className="img-fluid" style={{ width: 350, border: "1px solid #a39e9e" }} />
                                                        </Row>
                                                        <Row className='my-1'>
                                                            <h5>running : {value.running_count} 件</h5>
                                                        </Row>
                                                        <Row className='my-1'>
                                                            <h5>操作人員 : {value.operator}</h5>
                                                        </Row>
                                                        <Row className='my-1'>
                                                            <h5>運作時間 : {value.time} HR</h5>
                                                        </Row>
                                                        <Row className='my-1 mx-1' style={{ display: 'flex', justifyContent: 'right' }}>
                                                            <Col md="2">
                                                                <Button variant="light" style={{ fontWeight: "bold", background: "#F38181", color: "white", borderColor: "#F38181", borderRadius: '100%', borderWidth: "medium" }}>+</Button>
                                                            </Col>
                                                        </Row>
                                                    </>
                                                }
                                            </Accordion.Body>
                                        </Accordion.Item>
                                    </Accordion>
                                </Col>
                            )
                        })
                    ))
                }
            </Row>
        </div>
    );
}

export default MachineOverview;
