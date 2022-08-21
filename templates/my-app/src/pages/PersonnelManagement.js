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
                    name: '員工名稱',
                    center: true,
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.user_name}</div>
                }, {
                    name: '部門',
                    center: true,
                    width: '15rem',
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.module_name.length > 1 ? row.module_name.join(",") : row.module_name}</div>
                }, {
                    name: '帳號(工號）',
                    center: true,
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.uid}</div>
                }, {
                    name: '編輯者',
                    width: '7rem',
                    center: true,
                    cell: (row, index) =>
                        <div style={{ fontSize: 15 }}>{row.editor}</div>
                }, {
                    name: '編輯時間',
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
                name: '人員名稱',
                center: true,
                width: '5rem',
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.user_name}</div>
            }, {
                name: '國籍',
                width: '6rem',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.country}</div>
            }, {
                name: '性別',
                width: '4rem',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.gender}</div>
            }, {
                name: '部門',
                width: '10rem',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.module_name.join(',')}</div>
            }, {
                name: '信箱帳號',
                center: true,
                cell: (row, index) =>
                    <div style={{ fontSize: 15 }}>{row.email}</div>
            }, {
                name: '密碼',
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
                    data['status'] = '已儲存'
                    data['status_color'] = 'text-success'
                    setLoading(false);
                    setSubmitLoading(false)
                    setPatchCheck(false)
                })
                .catch(error => {
                    data['status'] = '編輯中...'
                    data['status_color'] = 'text-secondary'
                    let cur_formError = { ...formError }
                    if (error.response.data.updatePassword.message === '原始密碼錯誤')
                        cur_formError.oldpassword = '原始密碼錯誤'
                    else
                        delete cur_formError.oldpassword
                    if (error.response.data.updatePassword.message === '密碼需與密碼確認相同')
                        cur_formError.password1 = '密碼需與密碼確認相同'
                    else
                        delete cur_formError.password1
                    if (error.response.data.updatePassword.message === '新密碼不可與原始密碼相同')
                        cur_formError.password = '新密碼不可與原始密碼相同'
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
                    if (error.response.data.message === '使用者名稱已經被使用')
                        cur_formError.user_name = '使用者名稱已經被使用'
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
            errors.user_name = "請輸入名稱"
        }
        if (!params.gender) {
            errors.gender = "請選擇性別"
        }
        if (!params.country) {
            errors.country = "請選擇國籍"
        }
        if (!params.module_id || params.module_id[0] === null) {
            errors.module_id = "請選擇部門"
        }
        if (!params.email) {
            errors.email = "請輸入Email"
        } else if (!/\S+@\S+\.\S+/.test(params.email)) {
            errors.email = "Email不符合格式"
        }
        if (!params.oldpassword && type === "patch") {
            errors.oldpassword = "請輸入原密碼"
        }
        if (!params.password) {
            errors.password = "請輸入密碼"
        }
        if (!params.password1 && type === "patch") {
            errors.password1 = "請輸入密碼確認"
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
            "人員名稱": '',
            "國籍": '',
            "性別": '',
            "部門": '',
            "信箱帳號": '',
            "密碼": '',
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
                    throw "檔案為空"
                const fileType = fileName.substring(fileName.lastIndexOf("."))
                if (validType.indexOf(fileType) === -1)
                    throw "檔案類型錯誤,可接受的格式有：" + validType.join("、")
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
                        value['部門'].split(',').map((value, vindex) => {
                            if (module.label === value) {
                                module_id.push(module.value)
                                module_name.push(module.label)
                            }
                        })
                    })
                    tmp.push({
                        user_name: value['人員名稱'],
                        country: value['國籍'],
                        gender: value['性別'],
                        module_id: module_id,
                        module_name: module_name,
                        email: value['信箱帳號'],
                        password: value['密碼']
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
                        新增人員
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <form onSubmit={onSubmitAddUser}>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">員工名稱：</label>
                            <div className="col col-sm">
                                <input type="text" className="form-control" onChange={(e) => onChangeRegister(e, 'user_name')} value={data.user_name} />
                                {formError.user_name && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.user_name}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">性別：</label>
                            <div className="col col-sm">
                                <Form.Select aria-label="Default select example" onChange={(e) => onChangeRegister(e, 'gender')} value={data.gender}>
                                    <option value={0}>請選擇性別</option>
                                    <option value='男'>男</option>
                                    <option value='女'>女</option>
                                </Form.Select>
                                {formError.gender && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.gender}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">國籍：</label>
                            <div className="col col-sm">
                                <Form.Select aria-label="Default select example" onChange={(e) => onChangeRegister(e, 'country')} value={data.country}>
                                    <option value={null}>請選擇國籍</option>
                                    <option value='本國籍'>本國籍</option>
                                    <option value='非本國籍'>非本國籍</option>
                                </Form.Select>
                                {formError.country && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.country}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2" style={{ position: "relative", zIndex: "1" }}>
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">部門：</label>
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
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">信箱帳號：</label>
                            <div className="col col-sm">
                                <input type="text" className="form-control" onChange={(e) => onChangeRegister(e, 'email')} value={data.email} />
                                {formError.email && (
                                    <p className="text-danger" style={{ fontSize: '14px' }}>{formError.email}</p>
                                )}
                            </div>
                        </div>
                        <div className="form-group row mb-2">
                            <label className="col col-sm-4 col-form-label d-flex flex-row-reverse">密碼：</label>
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
                        關閉
                    </Button>
                    <Button variant="primary" style={{ background: "#5e789f", color: "white", border: "none" }} onClick={() => { regSubmit.current.click() }}>
                        送出
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
                        批次上傳
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row className='mb-3'>
                        <Col className='col-auto'>
                            <Button output="csv" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={createExcel}>
                                範例CSV
                            </Button>
                        </Col>
                        <Col className='col-auto'>
                            <Button output="xlsx" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={createExcel}>
                                範例XLSX
                            </Button>
                        </Col>
                        <Col className='col-auto'>
                            <Button output="xls" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={createExcel}>
                                範例XLS
                            </Button>
                        </Col>
                        <Col className='col-auto'>
                            <InputFiles accept=".csv, .xlsx, .xls" onChange={uploadExcel}>
                                <Button style={{ width: 'auto', background: "#5e789f", color: "white", borderWidth: "medium", borderColor: "#5e789f" }}>
                                    <BsUpload className="m-1" style={{ fontSize: "1em" }} />
                                    <span className='m-1'>上傳檔案</span>
                                </Button>
                            </InputFiles>
                            <p className='text-secondary' style={{ fontSize: '10px' }}>僅支持 .csv、.xlsx、.xls 格式的文件</p>
                        </Col>
                    </Row>
                    <Tabs
                        id="controlled-tab-example"
                        defaultActiveKey="0"
                        className="mb-2"
                    >
                        <Tab eventKey="0" title="預覽結果"></Tab>
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
                        關閉
                    </Button>
                    <Button style={{ background: "#5e789f", color: "white", border: "none" }} onClick={addbatchUser}>
                        送出
                    </Button>
                </Modal.Footer>
            </Modal>
        </>
    )
}

export default PersonnelManagement;