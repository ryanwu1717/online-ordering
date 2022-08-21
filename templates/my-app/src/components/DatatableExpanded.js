import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import DataTable from "react-data-table-component";
import Select from 'react-select'
import { Card, Form, Button, Tabs, Tab, Row, Col } from "react-bootstrap";

function DatatableExpanded(props) {
    const [columns, setColumns] = useState();
    const [data, setData] = useState([]);
    const [expandLoading, setExpandLoading] = useState(true);
    const [totalRows, setTotalRows] = useState(0);
    const [activeKey, setActiveKey] = useState();

    useEffect(() => {
        setColumns(props.columns)
        setData(props.data)
        setTotalRows(props.totalRows)
    }, [props.columns, props.data, props.modules, props.patchCheck])

    useEffect(() => {
        setExpandLoading(false)
    }, [props.selectedUser]);

    const handleShow = (e) => {
        setExpandLoading(true);
        props.handleRowClick(e);
    }

    const onModuleSelect = (key) => {
        props.setSelectedTab(parseInt(key))
        setActiveKey(key)
        props.onTabChange(parseInt(key))
    }

    return (
        <>
            <Card>
                <Card.Header>人員管理</Card.Header>
                <Card.Body>
                    <Tabs
                        id="controlled-tab-example"
                        defaultActiveKey="0"
                        activeKey={activeKey}
                        onSelect={(key) => onModuleSelect(key)}
                        className="mb-3"
                    >
                        <Tab eventKey="0" title="所有部門"></Tab>
                        {props.modules.map((module, index) => {
                            return (
                                <Tab key={module.label} eventKey={module.value} title={module.label}></Tab>
                            )
                        })}
                    </Tabs>
                    <Row>
                        <Button className="float-right col-auto mx-2" variant="light" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={() => props.setModalShow(true)}>
                            新增人員
                        </Button>
                        <Button className="float-right col-auto mx-2" variant="light" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={() => props.setBatchModalShow(true)}>
                            批次上傳
                        </Button>
                    </Row>
                    <DataTable
                        columns={columns}
                        data={data}
                        pagination
                        paginationServer
                        highlightOnHover
                        pointerOnHover
                        paginationTotalRows={totalRows}
                        onRowClicked={handleShow}
                        expandableRows
                        expandableRowsComponent={ExpandedComponent}
                        expandOnRowClicked={true}
                        expandableRowsComponentProps={{
                            "expandLoading": expandLoading,
                            "modules": props.modules,
                            "formError": props.formError,
                            "patchCheck": props.patchCheck,
                            "setPatchCheck": (e) => props.setPatchCheck(e),
                            "expandableRowsOnSubmit": (e) => props.expandableRowsOnSubmit(e),
                            "onChangeData": (e) => props.onChangeData(e),
                        }}
                    />
                </Card.Body>
            </Card>
        </>
    );
}

function ExpandedComponent(prop) {
    const patchSubmit = useRef()
    let options = []
    prop.data.module_name.map((value, index) => {
        prop.modules.map((module, mindex) => {
            if (value === module.label)
                options.push(prop.modules[mindex])
        })
    });
    const handleOnSubmit = (e) => {
        e.preventDefault();
        prop.expandableRowsOnSubmit(prop.data);
    }

    const onChangeData = (e, param) => {
        let cur_data = prop.data
        let module_id = []
        let module_name = []
        if (param === 'module_name') {
            e.map(option => {
                module_id.push(option.value)
                module_name.push(option.label)
            })
            cur_data['module_id'] = module_id
            cur_data['module_name'] = module_name
        } else {
            cur_data[param] = e.target.value
        }
        cur_data['status'] = '編輯中...'
        cur_data['status_color'] = 'secondary'
        prop.onChangeData(prop.data)
    }

    const cancelPatch = (e) => {
        prop.setPatchCheck(false)
        let cur_data = prop.data
        let cur_formError = prop.formError
        delete cur_data['oldpassword']
        delete cur_data['password']
        delete cur_data['password1']
        delete cur_formError['oldpassword']
        delete cur_formError['password']
        delete cur_formError['password1']
    }

    return (
        <Card>
            <Card.Header>編輯資料</Card.Header>
            <Card.Body>
                {prop.expandLoading ? <>讀取中...</> : (
                    <form onSubmit={handleOnSubmit}>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">員工名稱：</label>
                            <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                <input type="text" className="form-control" onChange={(e) => onChangeData(e, 'user_name')} value={prop.data.user_name} />
                            </div>
                            {prop.formError.user_name && (
                                <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.user_name}</p>
                            )}
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">性別：</label>
                            <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                <Form.Select aria-label="Default select example" onChange={(e) => onChangeData(e, 'gender')} value={prop.data.gender}>
                                    <option value={0}>請選擇性別</option>
                                    <option value='男'>男</option>
                                    <option value='女'>女</option>
                                </Form.Select>
                                {prop.formError.gender && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.gender}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">國籍：</label>
                            <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                <Form.Select aria-label="Default select example" onChange={(e) => onChangeData(e, 'country')} value={prop.data.country}>
                                    <option value={null}>請選擇國籍</option>
                                    <option value='本國籍'>本國籍</option>
                                    <option value='非本國籍'>非本國籍</option>
                                </Form.Select>
                                {prop.formError.country && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.country}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">部門：</label>
                            <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                <Select
                                    options={prop.modules}
                                    onChange={(e) => onChangeData(e, 'module_name')}
                                    defaultValue={options}
                                    isMulti
                                    name="colors"
                                    className="basic-multi-select"
                                    classNamePrefix="select"
                                >
                                </Select>
                                {prop.formError.module_id && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.module_id}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">信箱：</label>
                            <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                <input type="text" className="form-control" onChange={(e) => onChangeData(e, 'email')} value={prop.data.email} />
                                {prop.formError.email && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.email}</p>
                                )}
                            </div>
                        </div>
                        {prop.patchCheck ?
                            <>
                                <Row className="mb-2">
                                    <Col md={{ span: 3, offset: 3 }}>
                                        <Button variant="primary" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={() => { cancelPatch() }}>
                                            取消
                                        </Button>
                                    </Col>
                                </Row>
                                <div className="form-group row mb-2">
                                    <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">原密碼：</label>
                                    <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                        <input type="password" className="form-control" onChange={(e) => onChangeData(e, 'oldpassword')} value={prop.data.oldpassword} />
                                        {prop.formError.oldpassword && (
                                            <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.oldpassword}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="form-group row mb-2">
                                    <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">新密碼：</label>
                                    <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                        <input type="password" className="form-control" onChange={(e) => onChangeData(e, 'password')} value={prop.data.password} />
                                        {prop.formError.password && (
                                            <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.password}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="form-group row mb-2">
                                    <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">新密碼確認：</label>
                                    <div className="col col-xs-4 col-sm-6 col-md-7 col-lg-7 col-xl-8 col-xxl-8">
                                        <input type="password" className="form-control" onChange={(e) => onChangeData(e, 'password1')} value={prop.data.password1} />
                                        {prop.formError.password1 && (
                                            <p className="text-danger" style={{ fontSize: '14px' }}>{prop.formError.password1}</p>
                                        )}
                                    </div>
                                </div>
                            </>
                            : <Row>
                                <Col md={{ span: 3, offset: 3 }}>
                                    <Button variant="primary" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={() => { prop.setPatchCheck(true) }}>
                                        修改密碼
                                    </Button>
                                </Col>
                            </Row>
                        }
                        <button hidden ref={patchSubmit} type="submit" ></button>
                    </form>
                )}
            </Card.Body>
            <Card.Footer>
                <Row>
                    <Col className="col-md-10">
                        <Button variant="primary" style={{ background: "#5e789f", color: "white", border: "none" }} onClick={() => { patchSubmit.current.click() }}>
                            送出
                        </Button>
                    </Col>
                    <Col className="col-md-2">
                        <p className={`${prop.data.status_color} text-center my-2`}>{prop.data.status ? prop.data.status : ''}</p>
                    </Col>
                </Row>
            </Card.Footer>
        </Card >
    )
}
export default DatatableExpanded;