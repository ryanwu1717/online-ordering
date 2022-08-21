import React, { useState, useEffect, useRef } from 'react';
import { Col, Row, Button, Card, Form, InputGroup, Container } from "react-bootstrap";
import { Tooltip, Steps, Popover } from 'antd';
import { BulbFilled, DeleteTwoTone } from '@ant-design/icons';
import Datatable from '../Datatable';
import axios from 'axios';

const { Step } = Steps;
const Overview = (props) => {
    const [datatables, setDatatables] = useState({
        require: {
        },
        thead: [
            {
                name: '類別',
                cell: row => <Tooltip placement="rightTop" title='查看詳細內容' >
                    <Button onClick={openCategory} standard_processes_id={row.standard_processes_id} variant="link">{row.custom_category}</Button>
                </Tooltip>,
                width: "18%",
                center: true,
            },
            {
                name: '建立者',
                cell: row => '',
                width: "18%",
                center: true,
            },
            {
                name: '建立日期',
                cell: row => '',
                width: "18%",
                center: true,
            },
            {
                name: '編輯者',
                cell: row => '',
                width: "18%",
                center: true,
            },
            {
                name: '編輯日期',
                cell: row => '',
                width: "18%",
                center: true,
            },
            {
                name: <h5><DeleteTwoTone style={{ fontSize: '22px' }} twoToneColor="#ff6768" /></h5>,
                cell: row => <Tooltip className='my-2' placement="left" title={row.custom_category}>
                    <Form.Check onClick={handleDeleteCheck} standard_processes_id={row.standard_processes_id} type="checkbox" style={{ fontSize: '22px' }} />
                </Tooltip>,
                right: true,
            },
        ]
    })
    const [processesList, setProcessesList] = useState([]);
    const [deleteArr, setDeleteArr] = useState([]);
    const tableRef = useRef(null);

    const handleDeleteCheck = (e) => {
        if (deleteArr.indexOf(e.target.attributes.standard_processes_id.value) === -1) {
            let delete_arr_temp = deleteArr
            delete_arr_temp.push(e.target.attributes.standard_processes_id.value)
            setDeleteArr(delete_arr_temp)
        } else {
            let delete_arr_temp = deleteArr
            delete_arr_temp.splice(deleteArr.indexOf(e.target.attributes.standard_processes_id.value), 1)
            setDeleteArr(delete_arr_temp)
        }
    }

    const handleDelete = (e) => {
        let data = {}
        data['standard_processes_id'] = deleteArr
        axios
            .delete('/develop/custom_processes', { data: data })
            .then(response => {
                tableRef.current.fetchUsers();
            })
            .catch(function (error) {
                console.log(error);
            });
    }

    const openCategory = (e) => {
        let url = `standard_processes/${e.target.attributes.standard_processes_id.value}`;
        window.open(url, '_blank');
    }


    useEffect(() => {

    }, []);

    const postProcess = (response) => {
        setDeleteArr([])
        return response
    }
    return (
        <Container fluid>
            <Row className="my-2">
                <Col md="12">
                    <Card className='shadow'>
                        <Row>
                            <Col md="auto">
                                <Card.Title md='12' as="h3" className='mb-3'>
                                    <span className="badge rounded rfid_title p-3 text-center ">標準製程總覽</span>
                                </Card.Title>
                            </Col>
                            <Col>
                                <Tooltip className='my-2' placement="rightTop" title="刪除：勾選欲刪除的類別，再按刪除按鈕一次刪除">
                                    <BulbFilled className='my-2 bulb' />
                                </Tooltip>
                            </Col>
                            <Col style={{ display: 'flex', justifyContent: 'right' }}>
                                <Button onClick={openCategory} standard_processes_id="0" className=' my-2' style={{ height: '40px', width: 'auto', background: "#5870a0", color: "white", border: 'none', fontWeight: "bold" }} variant="light" >新增</Button>
                                <Button onClick={handleDelete} className='mx-2 my-2' style={{ width: 'auto', height: '40px', background: "#ff6768", color: "white", border: 'none', fontWeight: "bold" }} variant="light" >刪除</Button>
                            </Col>
                        </Row>
                        <Row>
                            <Col className='customerCategory mx-2 my-2' >
                                <Datatable
                                    ref={tableRef}
                                    datatables={datatables}
                                    api_location="/develop/custom_processes/all"
                                    postProcess={postProcess}
                                    expandableRows={true}
                                    expandedComponent={ExpandedComponent}
                                />
                            </Col>
                        </Row>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
}
const customDot = (dot, { status, index }) => (
    <Popover
        content={
            <span>
                step {index} status: {status}
            </span>
        }
    >
        {dot}
    </Popover>
);
const ExpandedComponent: React.FC<ExpanderComponentProps<DataRow>> = ({ data }) => {
    return (
        <div style={{ display: 'flex', width: '1200px', overflowX: 'scroll', whiteSpace: 'nowrap' }}>
            <Steps className='my-2' current={data.processes.length - 1} progressDot>
                {
                    Object.entries(data.processes).map(([key, value]) => {
                        return (
                            <Step title={`${(value.processes_id) || ''}`} description={(value.processes_name) || ''} />

                        );
                    })
                }
            </Steps>
        </div>
    )
};

export default Overview;
