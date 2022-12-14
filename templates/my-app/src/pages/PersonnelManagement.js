import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import { Button, Form, Modal, Row, Col, Tabs, Tab } from 'react-bootstrap';
import { BsUpload } from 'react-icons/bs';
import Select from 'react-select'
import DataTable from "react-data-table-component";
import DatatableExpanded from "../components/DatatableExpanded"
import InputFiles from "react-input-files";
import * as FileSaver from "file-saver";
import * as XLSX from "xlsx";

const PersonnelManagement = (props) => {
    const [columns, setColumns] = useState([])
    const [data, setData] = useState([])
    const [loading, setLoading] = useState(null)
    const [submitLoading, setSubmitLoading] = useState(null)
    const [totalRows, setTotalRows] = useState(0)
    const [reviewColumns, setReviewColumns] = useState([])
    const [reviewData, setReviewData] = useState([])
    const [modules, setModules] = useState([])
    const [selectedUser, setSelectedUser] = useState([])
    const [modalShow, setModalShow] = useState(null)
    const [batchModalShow, setBatchModalShow] = useState(null)
    const [regData, setRegData] = useState({})
    const [formError, setFormError] = useState({})
    const [patchCheck, setPatchCheck] = useState(false);
    const [registerOption, setRegisterOption] = useState([])
    const [selectedTab, setSelectedTab] = useState(null)
    const regSubmit = useRef()

    useEffect(() => {
        setLoading(true)
        axios.get('/system/modules/permission')
            .then(response => {
                let cur_modules = []
                response.data.map(value => {
                    cur_modules.push({
                        value: value.module_id,
                        label: value.module_name
                    })
                })
                setModules(cur_modules)
            })
    }, [submitLoading])

    useEffect(() => {
        if (loading) {
            onTabChange(null)
            setColumns([
                {
                    name: '',
                    center: true,
                    width: '5rem',
                    cell: (row, index) =>
                        <button type="button" className="btn-close" style={{ border: "1px solid #a39e9e" }} aria-label="Close" index={index} user_id={row.user_id} onClick={deleteUser}></button>
                }, {
                    name: '????????????',
                    center: true,
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.user_name}</div>
                }, {
                    name: '??????',
                    center: true,
                    width: '15rem',
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.module_name.length > 1 ? row.module_name.join(",") : row.module_name}</div>
                }, {
                    name: '??????(?????????',
                    center: true,
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.uid}</div>
                }, {
                    name: '?????????',
                    width: '7rem',
                    center: true,
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.editor}</div>
                }, {
                    name: '????????????',
                    width: '8rem',
                    center: true,
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.edit_time}</div>
                }
            ])
        }
    }, [loading])

    useEffect(() => {
        setReviewColumns([
            {
                name: '',
                center: true,
                width: '4rem',
                cell: (row, index) =>
                    <button type="button" className="btn-close" style={{ border: "1px solid #a39e9e" }} aria-label="Close" index={index} onClick={deleteReviewData}></button>
            }, {
                name: '????????????',
                center: true,
                width: '5rem',
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.user_name}</div>
            }, {
                name: '??????',
                width: '6rem',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.country}</div>
            }, {
                name: '??????',
                width: '4rem',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.gender}</div>
            }, {
                name: '??????',
                width: '10rem',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.module_name.join(',')}</div>
            }, {
                name: '????????????',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.email}</div>
            }, {
                name: '??????',
                width: '10rem',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>
                        <input readOnly className="col-12 text-center" type='password' value={row.password} style={{ border: 'none' }} />
                    </div>
            }
        ])
    }, [reviewData])

    useEffect(() => {
        setTotalRows(data.length)
    }, [data])

    const onTabChange = (module_id) => {
        let cur_regData = JSON.parse(JSON.stringify(regData))
        cur_regData['module_id'] = []
        cur_regData['module_name'] = []
        if (module_id === 0) {
            module_id = null
            setRegisterOption([])
        }
        modules.map((module, index) => {
            if (module.value === module_id) {
                setRegisterOption(module)
                cur_regData['module_name'].push(module.label)
            }
        })
        cur_regData['module_id'].push(module_id)
        setRegData(cur_regData)
        axios.get('/system/user/detail', {
            params: {
                module_id: module_id
            }
        })
            .then(response => {
                setData(response.data)
                setSubmitLoading(true)
            })
    }

    const handleRowClick = (e) => {
        setSelectedUser({
            user_id: e.user_id,
        })
    }

    const onChangeData = (e) => {
        let cur_data = [...data]
        setData(cur_data.filter(value => value.user_id = e.user_id))
    }

    const onChangeRegister = (e, params) => {
        let cur_module_id = []
        let cur_module_name = []
        if (params === 'module_name') {
            e.map((value, index) => {
                cur_module_id.push(value.value)
                cur_module_name.push(value.label)
            })
            setRegData((regData) => ({
                ...regData,
                ['module_name']: cur_module_name,
                ['module_id']: cur_module_id
            }));
        } else {
            setRegData((regData) => ({
                ...regData,
                [params]: e.target.value,
            }));
        }
    }

    const expandableRowsOnSubmit = (data) => {
        if (validateForm(data, "patch") || !patchCheck) {
            var date = new Date(Date.now());
            data['edit_time'] = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + " " + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds()
            console.log(data)
            axios.patch('/system/user/detail', [data])
                .then(response => {
                    data['editor'] = response.data[0].editor
                    data['status'] = '?????????'
                    data['status_color'] = 'text-success'
                    setLoading(false);
                    setSubmitLoading(false)
                    setPatchCheck(false)
                })
                .catch(error => {
                    data['status'] = '?????????...'
                    data['status_color'] = 'text-secondary'
                    let cur_formError = { ...formError }
                    if (error.response.data.updatePassword.message === '??????????????????')
                        cur_formError.oldpassword = '??????????????????'
                    else
                        delete cur_formError.oldpassword
                    if (error.response.data.updatePassword.message === '??????????????????????????????')
                        cur_formError.password1 = '??????????????????????????????'
                    else
                        delete cur_formError.password1
                    if (error.response.data.updatePassword.message === '????????????????????????????????????')
                        cur_formError.password = '????????????????????????????????????'
                    else
                        delete cur_formError.password
                    setFormError(cur_formError)
                })
        }
    }

    const onSubmitAddUser = (e) => {
        e.preventDefault()
        if (validateForm(regData, "add")) {
            let cur_data = [...data]
            let cur_regData = { ...regData }
            var date = new Date(Date.now());
            cur_regData['edit_time'] = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + " " + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds()
            console.log(cur_regData)
            axios.post('/register/batch', [cur_regData])
                .then(response => {
                    console.log(response.data)
                    cur_regData['editor'] = response.data.editor
                    setRegData(cur_regData)
                    cur_data.push(cur_regData)
                    setData(cur_data)
                    setModalShow(false)
                    onTabChange(selectedTab)
                })
                .catch(error => {
                    let cur_formError = { ...formError }
                    if (error.response.data.message === '??????????????????????????????')
                        cur_formError.user_name = '??????????????????????????????'
                    else
                        delete cur_formError.user_name
                    setFormError(cur_formError)
                })
        }
    }

    const closeAddUser = (e) => {
        setModalShow(false)
        setRegData({})
        setFormError({})
    }

    const validateForm = (params, type) => {
        let errors = {}
        if (!params.user_name) {
            errors.user_name = "???????????????"
        }
        if (!params.gender) {
            errors.gender = "???????????????"
        }
        if (!params.country) {
            errors.country = "???????????????"
        }
        if (!params.module_id || params.module_id[0] === null) {
            errors.module_id = "???????????????"
        }
        if (!params.email) {
            errors.email = "?????????Email"
        } else if (!/\S+@\S+\.\S+/.test(params.email)) {
            errors.email = "Email???????????????"
        }
        if (!params.oldpassword && type === "patch") {
            errors.oldpassword = "??????????????????"
        }
        if (!params.password) {
            errors.password = "???????????????"
        }
        if (!params.password1 && type === "patch") {
            errors.password1 = "?????????????????????"
        }
        console.log(errors)
        setFormError(errors)
        if (Object.keys(errors).length === 0)
            return true
        else
            return false
    }

    const deleteUser = (e) => {
        let index = parseInt(e.target.getAttribute('index'))
        let cur_data = [...data]
        axios.delete(`/system/user/detail`, {
            data: [{
                user_id: parseInt(e.target.getAttribute('user_id'))
            }]
        })
            .then(response => {
                cur_data.splice(index, 1)
                setLoading(false);
                setSubmitLoading(false)
            })

    }

    const deleteReviewData = (e) => {
        let cur_reviewData = JSON.parse(JSON.stringify(reviewData))
        cur_reviewData.splice(parseInt(e.target.getAttribute('index')), 1)
        console.log(cur_reviewData)
        setReviewData(cur_reviewData)
    }

    const createExcel = (e) => {
        let res_data = [];
        res_data.push({
            "????????????": '',
            "??????": '',
            "??????": '',
            "??????": '',
            "????????????": '',
            "??????": '',
        })
        let ws = XLSX.utils.json_to_sheet(res_data);
        let wb = { Sheets: { data: ws }, SheetNames: ["data"] };
        let excelBuffer = XLSX.write(wb, { bookType: e.target.attributes.output.value, type: "array" });
        let data = new Blob([excelBuffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8" });
        FileSaver.saveAs(data, 'Example.' + e.target.attributes.output.value);
    }

    const uploadExcel = (files) => {
        const fileReader = new FileReader();
        for (let index = 0; index < files.length; index++) {
            fileReader.name = files[index].name
        }
        fileReader.onload = e => {
            try {
                const validType = new Array(".csv", ".xlsx", ".xls")
                const fileName = e.target.name
                if (fileName === null)
                    throw "????????????"
                const fileType = fileName.substring(fileName.lastIndexOf("."))
                if (validType.indexOf(fileType) === -1)
                    throw "??????????????????,????????????????????????" + validType.join("???")
                const { result } = e.target
                const workbook = XLSX.read(result, { type: "binary" })
                let data = []
                for (const sheet in workbook.Sheets) {
                    if (workbook.Sheets.hasOwnProperty(sheet)) {
                        data = data.concat(
                            XLSX.utils.sheet_to_json(workbook.Sheets[sheet], {
                                blankrows: false
                            })
                        );
                    }
                }
                let tmp = []
                data.map((value, index) => {
                    let module_id = [];
                    let module_name = [];
                    modules.map((module, mindex) => {
                        value['??????'].split(',').map((value, vindex) => {
                            if (module.label === value) {
                                module_id.push(module.value)
                                module_name.push(module.label)
                            }
                        })
                    })
                    tmp.push({
                        user_name: value['????????????'],
                        country: value['??????'],
                        gender: value['??????'],
                        module_id: module_id,
                        module_name: module_name,
                        email: value['????????????'],
                        password: value['??????']
                    })
                })
                setReviewData(tmp)
            } catch (error) {
                alert(error)
                return
            }
        }
        fileReader.readAsBinaryString(files[0])
    }

    const addbatchUser = (e) => {
        var date = new Date(Date.now());
        let cur_data = JSON.parse(JSON.stringify(data))
        let cur_reviewData = JSON.parse(JSON.stringify(reviewData))
        cur_reviewData.map((value, index) => {
            value['edit_time'] = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + " " + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds()
        })
        console.log(cur_reviewData)
        axios.post('/register/batch', cur_reviewData)
            .then((response) => {
                cur_reviewData.map((value, index) => {
                    value['editor'] = response.data.editor
                    cur_data.push(value)
                })
                setReviewData([])
            })
            .catch(error => {
                alert(error.response.data.message)
            })
    }

    return (
        <>
            <DatatableExpanded
                columns={columns}
                data={data}
                totalRows={totalRows}
                loading={loading}
                modules={modules}
                selectedUser={selectedUser}
                formError={formError}
                patchCheck={patchCheck}
                setPatchCheck={setPatchCheck}
                expandableRowsOnSubmit={expandableRowsOnSubmit}
                handleRowClick={handleRowClick}
                onChangeData={onChangeData}
                setModalShow={setModalShow}
                onTabChange={onTabChange}
                setBatchModalShow={setBatchModalShow}
                setSelectedTab={setSelectedTab}
            ></DatatableExpanded>
            <Modal
                show={modalShow}
                // scrollable={true}
                style={{ height: '700px' }}
            >
                <Modal.Header>
                    <Modal.Title>
                        ????????????
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <form onSubmit={onSubmitAddUser}>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">???????????????</label>
                            <div className="col col-sm">
                                <input type="text" className="form-control" onChange={(e) => onChangeRegister(e, 'user_name')} value={data.user_name} />
                                {formError.user_name && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.user_name}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">?????????</label>
                            <div className="col col-sm">
                                <Form.Select aria-label="Default select example" onChange={(e) => onChangeRegister(e, 'gender')} value={data.gender}>
                                    <option value={0}>???????????????</option>
                                    <option value='???'>???</option>
                                    <option value='???'>???</option>
                                </Form.Select>
                                {formError.gender && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.gender}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">?????????</label>
                            <div className="col col-sm">
                                <Form.Select aria-label="Default select example" onChange={(e) => onChangeRegister(e, 'country')} value={data.country}>
                                    <option value={null}>???????????????</option>
                                    <option value='?????????'>?????????</option>
                                    <option value='????????????'>????????????</option>
                                </Form.Select>
                                {formError.country && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.country}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2" style={{ position: "relative", zIndex: "1" }}>
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">?????????</label>
                            <div className="col col-sm">
                                <Select
                                    options={modules}
                                    onChange={(e) => onChangeRegister(e, 'module_name')}
                                    defaultValue={registerOption}
                                    isMulti
                                    name="colors"
                                    className="basic-multi-select"
                                    classNamePrefix="select"
                                >
                                </Select>
                                {formError.module_id && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.module_id}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">???????????????</label>
                            <div className="col col-sm">
                                <input type="text" className="form-control" onChange={(e) => onChangeRegister(e, 'email')} value={data.email} />
                                {formError.email && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.email}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">?????????</label>
                            <div className="col col-sm">
                                <input type="password" className="form-control" onChange={(e) => onChangeRegister(e, 'password')} value={data.password} />
                                {formError.password && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.password}</p>
                                )}
                            </div>
                        </div>
                        <input hidden ref={regSubmit} type="submit" ></input>
                    </form>
                </Modal.Body>
                <Modal.Footer style={{ position: "relative", zIndex: "0" }}>
                    <Button variant="secondary" onClick={closeAddUser}>
                        ??????
                    </Button>
                    <Button variant="primary" style={{ background: "#5e789f", color: "white", border: "none" }} onClick={() => { regSubmit.current.click() }}>
                        ??????
                    </Button>
                </Modal.Footer>
            </Modal>
            <Modal
                show={batchModalShow}
                style={{ height: '800px' }}
                size="xl"
            >
                <Modal.Header>
                    <Modal.Title>
                        ????????????
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row className='mb-3'>
                        <Col className='col-auto'>
                            <Button output="csv" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={createExcel}>
                                ??????CSV
                            </Button>
                        </Col>
                        <Col className='col-auto'>
                            <Button output="xlsx" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={createExcel}>
                                ??????XLSX
                            </Button>
                        </Col>
                        <Col className='col-auto'>
                            <Button output="xls" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={createExcel}>
                                ??????XLS
                            </Button>
                        </Col>
                        <Col className='col-auto'>
                            <InputFiles accept=".csv, .xlsx, .xls" onChange={uploadExcel}>
                                <Button style={{ width: 'auto', background: "#5e789f", color: "white", borderWidth: "medium", borderColor: "#5e789f" }}>
                                    <BsUpload className="m-1" style={{ fontSize: "1em" }} />
                                    <span className='m-1'>????????????</span>
                                </Button>
                            </InputFiles>
                            <p className='text-secondary' style={{ fontSize: '10px' }}>????????? .csv???.xlsx???.xls ???????????????</p>
                        </Col>
                    </Row>
                    <Tabs
                        id="controlled-tab-example"
                        defaultActiveKey="0"
                        className="mb-2"
                    >
                        <Tab eventKey="0" title="????????????"></Tab>
                    </Tabs>
                    <DataTable
                        columns={reviewColumns}
                        data={reviewData}
                        pagination
                        paginationServer
                        highlightOnHover
                        pointerOnHover
                    // paginationTotalRows={totalRows}
                    />
                </Modal.Body>
                <Modal.Footer style={{ position: "relative", zIndex: "0" }}>
                    <Button variant="secondary" onClick={() => setBatchModalShow(false)}>
                        ??????
                    </Button>
                    <Button style={{ background: "#5e789f", color: "white", border: "none" }} onClick={addbatchUser}>
                        ??????
                    </Button>
                </Modal.Footer>
            </Modal>
        </>
    )
}

export default PersonnelManagement;