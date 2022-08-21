import Fuse from 'fuse.js'
import React, { useState, useEffect, useCallback } from 'react';
import Pictures from '../components/Pictures';
import DatatableTheadDropdown from '../components/DatatableTheadDropdown';
import DataTable from 'react-data-table-component';
import { FaLock, FaLockOpen } from 'react-icons/fa'
import { Card, Container, Row, Col, Tabs, Tab, Modal } from 'react-bootstrap';
import axios from 'axios'

function IndexMsgUpload() {
    const options = {
        threshold: 0.4,
        keys: [
            "order_name.text"
        ]
    };
    const [quotation, setQuotation, creating, setCreating] = useQuotationCreating(false);
    const [fuse, setFuse] = useState(new Fuse([], options));
    const [dataType, setDataType] = useState('1');
    const handleDataTypeSelect = (e) => setDataType(e);
    const [listMessageValue, setListMessageValue] = useState('報價');
    const listMessages = [
        { name: '報價', value: '報價' },
        { name: '訂單', value: '訂單' },
    ];
    const tableNoData = '目前查無資料'
    const [picutresLoading, setPicutresLoading] = useState(false);
    const [fileList, setFileList] = useState([]);
    const [message, setMessage] = useState([]);
    const [messageLoading, setMessageLoading] = useState(false);
    const [messageContent, setMessageContent] = useState([]);
    const [setRunning, progress] = useOrderSearching({ pictures: messageContent, setPictures: setMessageContent });
    /* 
        const [quotationsNew, setQuotationsNew] = useState([]);
        const [quotationsOld, setQuotationsOld] = useState([]);
     */
    const [datas, setDatas] = useState([]);
    const [datasChecking, setDatasChecking] = useState(false);
    const [dataOld, setDataOld] = useState([]);
    const handleFileNameOnChangeOld = (e, index) => {
        let temp_dataOld = JSON.parse(JSON.stringify(dataOld));
        temp_dataOld[index]['fileName'] = e.target.value || '';
        setDataOld(temp_dataOld)
    }
    const handleFileNameOnChangeNew = (e, index) => {
        let temp_dataNew = JSON.parse(JSON.stringify(dataNew));
        temp_dataNew[index]['fileName'] = e.target.value || '';
        setDataNew(temp_dataNew)
    }
    const [dataNew, setDataNew] = useState([]);
    const [fileclick, setFileClick] = useState(null);
    const [confirmModalShow, setConfirmModalShow] = useState(false);
    const [confirmModalShowFlag, setConfirmModalShowFlag] = useState(null);
    const [confirmDataTemp, setConfirmDataTemp] = useState({});
    /* 
        const handleDataOldOnClick = (e, order_name, index) => {
            if (quotationsOld[index] !== undefined || quotationsOld[index] != null) window.open(`/home?id=${quotationsOld[index]}&file_id_dest=${quotationsOld[index]}`, '_blank').focus();
            else
                axios
                    .post(`/file/message/order_name`, { order_name: order_name, file_name: fileNameOld[index] !== undefined ? messageContent[fileNameOld[index]]['file_name'] : '' })
                    .then((response) => {
                        let temp_quotationsOld = JSON.parse(JSON.stringify(quotationsOld));
                        temp_quotationsOld[index] = response.data['file_id'];
                        setQuotationsOld(temp_quotationsOld)
                        window.open(`/home?id=${temp_quotationsOld[index]}&file_id_dest=${temp_quotationsOld[index]}`, '_blank').focus();
                    })
        }
        const handleDataNewOnClick = (e, order_name, index) => {
            console.log(quotationsNew[index])
            if (quotationsNew[index] !== undefined || quotationsNew[index] != null) window.open(`/home?id=${quotationsNew[index]}&file_id_dest=${quotationsNew[index]}`, '_blank').focus();
            else
                axios
                    .post(`/file/message/order_name`, { order_name: order_name, file_name: fileNameNew[index] !== undefined ? messageContent[fileNameNew[index]]['file_name'] : '' })
                    .then((response) => {
                        let temp_quotationsNew = JSON.parse(JSON.stringify(quotationsNew));
                        temp_quotationsNew[index] = response.data['file_id'];
                        setQuotationsNew(temp_quotationsNew)
                        window.open(`/home?id=${temp_quotationsNew[index]}&file_id_dest=${temp_quotationsNew[index]}`, '_blank').focus();
                    })
        }
     */
    function handleOnQuotationTypeChangOld(e, index, type) {
        let temp_dataOld = [...dataOld];
        temp_dataOld[index]['type'] = type === temp_dataOld[index]['type'] ? '' : type;
        setDataOld(temp_dataOld)
    }
    function handleOnQuotationTypeChangNew(e, index, type) {
        let temp_dataNew = [...dataNew];
        temp_dataNew[index]['type'] = type === temp_dataNew[index]['type'] ? '' : type;
        setDataNew(temp_dataNew)
    }
    function handleOnClickFile(e, file_name) {
        if (confirmModalShowFlag === false || confirmModalShowFlag === null) {
            setPicutresLoading(true);
            setMessageContent([]);
            setDataNew([])
            setDataOld([])
            setFileClick(file_name);
            setConfirmModalShowFlag(true);
            /*             
                        setQuotationsNew([])
                        setQuotationsOld([])
             */
            axios
                .post(`/file/message`, { file_name: file_name })
                .then((response) => {
                    response.data.map((value) => {
                        setMessageContent(prevMessageContent => [...prevMessageContent, { ...value, rotate: 0 }]);
                    })
                    setPicutresLoading(false);
                })
        } else {
            setConfirmModalShow(true)
            setConfirmDataTemp([e, file_name]);
        }

    }
    useEffect(() => {
        axios
            .get(`/file/message`, { params: { type: listMessageValue } })
            .then(response => {
                setFileList(response.data);
            })
    }, [listMessageValue]);
    useEffect(() => {
        if (datas.length === 0 || !datasChecking) {
            if (!datasChecking) {
                setDataNew([])
                setDataOld([])
            }
            return
        } else {
            axios
                .get(`/file/message/history`, { params: { order_name: datas[0] } })
                .then(response => {
                    let temp_datas = JSON.parse(JSON.stringify(datas));
                    let temp_dataNew = JSON.parse(JSON.stringify(dataNew));
                    let temp_dataOld = JSON.parse(JSON.stringify(dataOld));
                    if (response.data.length === 0) {
                        messageContent.map((value, index) => {
                            return < option value={value}>{(index + 1)}</option>;
                        })
                        temp_dataNew.push({
                            "圖號": datas[0],
                            "type": "new"
                        });
                        setDataNew(temp_dataNew);
                    } else {
                        response.data[0]['type'] = 'new';
                        temp_dataOld.push(response.data[0]);
                        setDataOld(temp_dataOld)
                    }
                    temp_datas.splice(0, 1);
                    setDatas(temp_datas)
                })
        };
    }, [datas])

    useEffect(() => {
        if (confirmModalShowFlag === false) {
            handleOnClickFile(confirmDataTemp[0], confirmDataTemp[1]);
        }
    }, [confirmModalShowFlag])

    const changeModalSelect = (modal_show, modal_show_flag) => {
        setConfirmModalShow(modal_show);
        setConfirmModalShowFlag(modal_show_flag);
    }

    const handleTableOnSubmit = (e, order_names) => {
        setDataOld([]);
        setDataNew([]);
        setDatasChecking(true);
        setMessage([])
        let temp_dataNew = [];
        temp_dataNew = order_names.filter((order_name) => {
            return (order_name.length > 0)
        }).map((order_name) => {
            return order_name[0]
        })
        setDatas(temp_dataNew)
    }
    const handlePicturesOnClick = (e, file_name) => {
        setMessageLoading(true);
        setMessage([])
        setDataNew([])
        setDataOld([])
        setDatas([])
        setDatasChecking(false);
        setRunning(true);

        /*         
                setQuotationsNew([])
                setQuotationsOld([])
         */
        axios
            .get(`/file/message/quotation`, { params: { file_name: file_name } })
            .then(response => {
                setMessageLoading(false);
                setMessage(response.data)
            })
    }

    const chageLock = (e, key, changeSet) => {
        let fileList_temp = { ...fileList };
        fileList_temp[key]['is_locked'] = changeSet;
        fileList_temp[key]['fileClick'] = false;
        setFileList(fileList_temp);
        axios
            .post(`/file/message/locked`, { file_path: key })
    }

    const handleOnSubmitQuotation = (e) => {
        let temp_quotation = [].concat(JSON.parse(JSON.stringify(dataNew)).map((element, index) => {
            if (element.type !== '') {
                return element;
            }
        })).concat(JSON.parse(JSON.stringify(dataOld)).map((element, index) => {
            if (element.type !== '') {
                return element;
            }
        })).filter(element => {
            if (element !== undefined) {
                if (element.hasOwnProperty('fileName')) {
                    element.fileName = messageContent[element.fileName]['file_name']
                }
                return element;
            }
        });
        setQuotation(temp_quotation);
        setCreating(true);
        /* 
                if (quotationsNew[index] !== undefined || quotationsNew[index] != null) window.open(`/home?id=${quotationsNew[index]}&file_id_dest=${quotationsNew[index]}`, '_blank').focus();
                else{
                    axios
                        .post(`/file/message/order_name`, { order_name: order_name, file_name: fileNameNew[index] !== undefined ? messageContent[fileNameNew[index]]['file_name'] : '' })
                        .then((response) => {
                            let temp_quotationsNew = JSON.parse(JSON.stringify(quotationsNew));
                            temp_quotationsNew[index] = response.data['file_id'];
                            setQuotationsNew(temp_quotationsNew)
                            window.open(`/home?id=${temp_quotationsNew[index]}&file_id_dest=${temp_quotationsNew[index]}`, '_blank').focus();
                        })
                }
         */
    }
    useEffect(() => {
        if (progress === 0 && datas.length === 0) {
            setFuse(new Fuse(messageContent, options))
            let temp_dataNew = [...dataNew];
            temp_dataNew.forEach((value, index) => {
                Object.keys(value).forEach(key => {
                    if (key === "圖號") {
                        let results = fuse.search(value[key])
                        if (results.length !== 0) {
                            results.forEach((result) => {
                                if (value.fileName === undefined)
                                    temp_dataNew[index].fileName = result.refIndex.toString()
                            })
                        }
                    }
                })
            })
            let temp_dataOld = [...dataOld];
            temp_dataOld.forEach((value, index) => {
                Object.keys(value).forEach(key => {
                    if (key === "客戶圖號") {
                        let results = fuse.search(value[key])
                        if (results.length !== 0) {
                            results.forEach((result) => {
                                if (value.fileName === undefined)
                                    temp_dataOld[index].fileName = result.refIndex.toString()
                            })
                        }
                    }
                })
            })
            setDataNew(temp_dataNew)
            setDataOld(temp_dataOld)
        }
    }, [progress, datas])
    return (
        <Container fluid className="d-grid gap-2 my-3">
            <Card>
                <Card.Header>
                    新舊圖辨識
                </Card.Header>
                <Card.Body>
                    <Row>
                        <Col>
                            <div className="btn-group btn-group-toggle" data-toggle="buttons">
                                {listMessages.map((radio, idx) => (
                                    <label className={(idx % 2 ? 'btn btn-outline-success' : 'btn btn-outline-danger') + (listMessageValue === radio.value ? " active" : "")}>
                                        <input
                                            key={idx}
                                            id={`radio-${idx}`}
                                            type="radio"
                                            name="radio"
                                            value={radio.value}
                                            checked={listMessageValue === radio.value}
                                            onChange={(e) => {
                                                setListMessageValue(e.currentTarget.value)
                                            }}
                                        />
                                        {radio.name}
                                    </label>
                                ))}
                            </div>
                        </Col>
                    </Row>
                    <Row>
                        <Col>
                            <div className="row d-flex flex-nowrap" style={{ overflowX: 'scroll' }} >
                                {Object.keys(fileList).map((key) => {
                                    return (
                                        <div className="col-4" key={key}>
                                            <div className="card">
                                                <div className="card-body" style={fileclick !== key ? { position: 'relative' } : { position: 'relative', backgroundColor:'pink' }} >
                                                    {fileList[key]['is_locked'] ? (
                                                        <FaLock onClick={(e) => { chageLock(e, key, !fileList[key]['is_locked']) }} style={{ position: 'absolute', top: 0, right: 0 }} />
                                                    ) : (
                                                        <FaLockOpen onClick={(e) => { chageLock(e, key, !fileList[key]['is_locked']) }} style={{ position: 'absolute', top: 0, right: 0 }} />
                                                    )}
                                                    <h5 className="card-title" onClick={(e) => fileList[key]['is_locked'] ? '' : handleOnClickFile(e, key)}>{key}</h5>
                                                    <p className="card-text">{fileList[key]['datetime'] || ''}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )
                                })}
                            </div>
                        </Col>
                    </Row>
                    <hr />
                    {messageContent.length !== 0 ? (
                        <>
                            <Row>
                                <Pictures pictures_data={messageContent} loading={picutresLoading} pickTitle={'選擇辨識'} handleOnClick={handlePicturesOnClick} />
                            </Row>
                            <hr />
                        </>
                    ) :
                        <>
                            {picutresLoading ? <>附檔解析中...<hr /></> : <></>}
                        </>
                    }
                    {messageContent.length !== 0 && message.length !== 0 ? (
                        <>
                            <Row>
                                <DatatableTheadDropdown tableData={message}
                                    tableOption={{
                                        "order_name": "客戶圖號",
                                        "customer": "客戶名稱",
                                    }}
                                    onSubmit={handleTableOnSubmit}
                                />
                            </Row>
                            <hr />
                        </>
                    ) :
                        <>
                            {messageLoading ? <>辨識中...<hr /></> : <></>}
                        </>
                    }
                    {dataOld.length !== 0 || dataNew.length !== 0 ? (

                        <Row>
                            <Col xs="12">
                                {datas.length !== 0 ? (
                                    <div className='row'>
                                        <div className="spinner-grow text-primary" role="status">
                                        </div>
                                        檢查歷史資料庫，進度剩餘 {datas.length} 筆)
                                    </div>
                                ) : <></>}
                            </Col>
                            <Col xs="12">
                                {datas.length !== 0 ?
                                    <></> : <button type="button" className='btn btn-primary float-right' onClick={handleOnSubmitQuotation}>統一報價</button>
                                }
                                <Modal centered show={creating} size="xl" onHide={(e) => setCreating(false)}>
                                    <Modal.Header>
                                        <Modal.Title>統一報價</Modal.Title>
                                        <button type="button" className="close" onClick={e => setCreating(false)} aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </Modal.Header>
                                    <Modal.Body>
                                        <DataTable
                                            data={quotation}
                                            columns={[
                                                {
                                                    name: '報價圖號',
                                                    selector: row => row.hasOwnProperty('圖號') ? row.圖號 : row.客戶圖號,
                                                    wrap: true,
                                                    minWidth: '200px',
                                                },
                                                {
                                                    name: '連結',
                                                    selector: row => row.hasOwnProperty('file_id') ?
                                                        <button type="button" className="btn btn-outline-secondary" onClick={() => { window.open(`/${row.type !== 'old' ? 'home' : 'finish'}?id=${row.file_id}&file_id_dest=${row.file_id}`, '_blank').focus() }}>進入報價單</button>
                                                        : <></>
                                                    ,
                                                    wrap: true,
                                                    minWidth: '200px',
                                                },
                                                /* {
                                                    name: '最近報價',
                                                    selector: row => row.hasOwnProperty('history') ?
                                                        row.history.map((row_data, index) => {
                                                            if (index === 0)
                                                                return Object.keys(row_data).map((key) =>
                                                                    key === '報價日期' || key === '報價單價' || key === '報價金額' || key === '幣別'
                                                                        ? <><strong>{key}：</strong>{row_data[key]}<br /></> : <></>
                                                                )
                                                        })
                                                        : <></>,
                                                    style: {
                                                        fontSize: '6px'
                                                    },
                                                    // ).slice(0, 200)}...`,
                                                    wrap: true,
                                                    minWidth: '200px',
                                                }, */
                                            ]}
                                            fixedHeaderScrollHeight="300px"
                                            responsive
                                            subHeaderAlign="right"
                                            subHeaderWrap
                                            customStyles={{
                                                headCells: {
                                                    style: {
                                                        fontSize: '20px',
                                                    },
                                                },
                                                cells: {
                                                    style: {
                                                        fontSize: '20px',
                                                    },
                                                },
                                            }}
                                        />
                                    </Modal.Body>
                                    <Modal.Footer>
                                        <button type="button" className="btn btn-secondary" onClick={e => setCreating(false)}>
                                            關閉
                                        </button>
                                    </Modal.Footer>
                                </Modal>
                                <Tabs
                                    defaultActiveKey={dataType}
                                    variant='pills'
                                    onSelect={(k) => handleDataTypeSelect(k)}
                                >
                                    <Tab tabClassName='primary' eventKey="1" title="舊圖">
                                        <Col xs="12">
                                            <Row>
                                                <label>
                                                    舊圖
                                                </label>
                                            </Row>
                                            <Row>
                                                <DataTable
                                                    columns={[
                                                        {
                                                            name: '報價單編號',
                                                            selector: row => row.報價單編號,
                                                            wrap: true,
                                                            minWidth: '200px',
                                                        },
                                                        {
                                                            name: '最近報價',
                                                            selector: row => row.hasOwnProperty('history') ?
                                                                row.history.map((row_data, index) => {
                                                                    if (index === 0)
                                                                        return Object.keys(row_data).map((key) =>
                                                                            key === '報價日期' || key === '報價單價' || key === '報價金額' || key === '幣別'
                                                                                ? <><strong>{key}：</strong>{row_data[key]}<br /></> : <></>
                                                                        )
                                                                })
                                                                : <></>,
                                                            style: {
                                                                fontSize: '6px'
                                                            },
                                                            // ).slice(0, 200)}...`,
                                                            wrap: true,
                                                            minWidth: '200px',
                                                        },
                                                        {
                                                            name: '開單日期',
                                                            selector: row => row.開單日期,
                                                            minWidth: '200px',
                                                        },
                                                        {
                                                            name: '客戶圖號',
                                                            selector: row => row.客戶圖號,
                                                            wrap: true,
                                                            minWidth: '200px',
                                                        },
                                                        {
                                                            name: '目前狀況',
                                                            selector: row => row.目前狀況,
                                                            minWidth: '200px',
                                                        },
                                                        {
                                                            name: '客戶代號',
                                                            selector: row => row.客戶代號,
                                                            minWidth: '200px',
                                                        },
                                                        {
                                                            name: '圖檔',
                                                            selector: (row, index) =>
                                                                <select className='form-control' disabled={datas.length !== 0} onChange={(e) => handleFileNameOnChangeOld(e, index)}>
                                                                    <option selected={row.fileName === undefined}>請選擇</option>
                                                                    {messageContent.map((value, index) => {
                                                                        let messageContent_options = Object.keys(value).map((key) =>
                                                                            key === "order_name" ?
                                                                                <option value={index} selected={row.fileName === index.toString()}>
                                                                                    {value[key].hasOwnProperty('text') ?
                                                                                        `圖${(index + 1)}：${value[key]['text']}`
                                                                                        : `圖${(index + 1)}：無圖號辨識`}
                                                                                </option>
                                                                                : null
                                                                        );
                                                                        return messageContent_options.filter((each_option) => each_option !== null).length === 0 ?
                                                                            <option value={index}>{`圖${(index + 1)}：無圖號辨識`}</option>
                                                                            : messageContent_options
                                                                    })}
                                                                </select >
                                                            ,
                                                            minWidth: '200px'
                                                        },
                                                        {
                                                            name: '動作',
                                                            selector: (row, index) => {
                                                                return datas.length !== 0 ?
                                                                    <></> : <>
                                                                        <div className="btn-group btn-group-toggle" data-toggle="buttons">
                                                                            <label className={("btn btn-outline-primary ") + (row.type === 'new' ? 'active' : '')}>
                                                                                <input type="radio" name="type" checked={row.type === 'new'} onClick={(e) => handleOnQuotationTypeChangOld(e, index, 'new')} /> 新報價
                                                                            </label>
                                                                            <label className={("btn btn-outline-primary ") + (row.type === 'old' ? 'active' : '')}>
                                                                                <input type="radio" name="type" checked={row.type === 'old'} onClick={(e) => handleOnQuotationTypeChangOld(e, index, 'old')} /> 歷史報價
                                                                            </label>
                                                                        </div>
                                                                        <button type="button" className="btn btn-outline-warning">納</button>
                                                                        <button type="button" className="btn btn-outline-success">訂</button>
                                                                    </>
                                                            },
                                                            minWidth: '300px',
                                                        },
                                                    ]}
                                                    data={dataOld}
                                                    fixedHeaderScrollHeight="300px"
                                                    noDataComponent={tableNoData}
                                                    responsive
                                                    subHeaderAlign="right"
                                                    subHeaderWrap
                                                    expandableRows
                                                    expandOnRowClicked={row => row.defaultExpanded}
                                                    expandableRowsComponent={({ data }) => {
                                                        return data.hasOwnProperty('history') ?
                                                            <div className='d-flex flex-nowrap'>
                                                                {data.history.map((row) =>
                                                                (
                                                                    <div className='col-4'>
                                                                        <div className='card'>
                                                                            <div className='card-body'>
                                                                                {Object.keys(row).map((key) =>
                                                                                    key.indexOf('TB') === -1 ? <p><strong>{key}：</strong>{row[key]}</p> : <></>
                                                                                )}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                            : (<>查無歷史紀錄</>)
                                                    }}
                                                    customStyles={{
                                                        headCells: {
                                                            style: {
                                                                fontSize: '20px',
                                                            },
                                                        },
                                                        cells: {
                                                            style: {
                                                                fontSize: '20px',
                                                            },
                                                        },
                                                    }}
                                                />
                                            </Row>
                                        </Col>
                                    </Tab>
                                    <Tab eventKey="2" title="新圖">
                                        <Col xs="12">
                                            <Row>
                                                <label>
                                                    新圖
                                                </label>
                                            </Row>
                                            <Row>
                                                <DataTable
                                                    columns={[
                                                        {
                                                            name: '圖號',
                                                            selector: row => row.圖號,
                                                            minWidth: '200px',
                                                        },
                                                        {
                                                            name: '圖檔',
                                                            selector: (row, index) => {
                                                                return <select disabled={datas.length !== 0} className='form-control' onChange={(e) => handleFileNameOnChangeNew(e, index)}>
                                                                    <option selected={row.fileName === undefined}>請選擇</option>
                                                                    {messageContent.map((value, index) => {
                                                                        let messageContent_options = Object.keys(value).map((key) =>
                                                                            key === "order_name" ?
                                                                                <option value={index} selected={row.fileName === index.toString()}>
                                                                                    {value[key].hasOwnProperty('text') ?
                                                                                        `圖${(index + 1)}：${value[key]['text']}`
                                                                                        : `圖${(index + 1)}：無圖號辨識`}
                                                                                </option>
                                                                                : null
                                                                        );
                                                                        return messageContent_options.filter((each_option) => each_option !== null).length === 0 ?
                                                                            <option value={index} selected={row.fileName === index.toString()}>{`圖${(index + 1)}：無圖號辨識`}</option>
                                                                            : messageContent_options
                                                                    })}
                                                                </select >
                                                            },
                                                            minWidth: '200px'
                                                        },
                                                        {
                                                            name: '動作',
                                                            selector: (row, index) => {
                                                                return datas.length !== 0 ?
                                                                    <></> : <>
                                                                        <div className="btn-group btn-group-toggle" data-toggle="buttons">
                                                                            <label className={("btn btn-outline-primary ") + (row.type === 'new' ? 'active' : '')}>
                                                                                <input type="radio" name="type" checked={row.type === 'new'} onClick={(e) => handleOnQuotationTypeChangNew(e, index, 'new')} /> 新報價
                                                                            </label>
                                                                            <label className={("btn btn-outline-primary ") + (row.type === 'old' ? 'active' : '')}>
                                                                                <input type="radio" name="type" checked={row.type === 'old'} onClick={(e) => handleOnQuotationTypeChangNew(e, index, 'old')} /> 歷史報價
                                                                            </label>
                                                                        </div>
                                                                    </>
                                                                // return (
                                                                //     <>
                                                                //         <div className="btn-group btn-group-toggle" data-toggle="buttons">
                                                                //             <label className="btn btn-outline-primary active">
                                                                //                 <input type="radio" name="options" checked /> 新報價
                                                                //             </label>
                                                                //             <label className="btn btn-outline-primary">
                                                                //                 <input type="radio" name="options" /> 歷史報價
                                                                //             </label>
                                                                //         </div>
                                                                //         {/* <button type="button" className="btn btn-outline-primary" onClick={(e)=>{
                                                                //             handleDataNewOnClick(e,row.圖號,index)
                                                                //         }}>報</button> */}
                                                                //     </>
                                                                // )
                                                            },
                                                            minWidth: '200px',
                                                        },
                                                    ]}
                                                    data={dataNew}
                                                    fixedHeaderScrollHeight="300px"
                                                    noDataComponent={tableNoData}
                                                    responsive
                                                    subHeaderAlign="right"
                                                    subHeaderWrap
                                                    expandableRows
                                                    expandOnRowClicked={row => row.defaultExpanded}
                                                    expandableRowsComponent={({ data }) => {
                                                        return data.hasOwnProperty('history') ?
                                                            <div className='d-flex flex-nowrap'>
                                                                {data.history.map((row) =>
                                                                (
                                                                    <div className='col-4'>
                                                                        <div className='card'>
                                                                            <div className='card-body'>
                                                                                {Object.keys(row).map((key) =>
                                                                                    <p><strong>{key}：</strong>{row[key]}</p>
                                                                                )}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                            : (<>查無歷史紀錄</>)
                                                    }}
                                                    customStyles={{
                                                        headCells: {
                                                            style: {
                                                                fontSize: '20px',
                                                            },
                                                        },
                                                        cells: {
                                                            style: {
                                                                fontSize: '20px',
                                                            },
                                                        },
                                                    }}
                                                />
                                            </Row>
                                        </Col>
                                    </Tab>
                                </Tabs>
                            </Col>
                        </Row>
                    ) : <></>}
                </Card.Body>
            </Card>
            <Modal centered show={confirmModalShow} size="md">
                <Modal.Header>
                    <Modal.Title>警告！</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row>
                        確定要更換檔案嗎?
                    </Row>
                </Modal.Body>
                <Modal.Footer>
                    <button type="button" className="btn btn-primary" onClick={e => { changeModalSelect(false, false, confirmDataTemp) }}>
                        確定
                    </button>
                    <button type="button" className="btn btn-secondary" onClick={e => { setConfirmModalShow(false); setConfirmModalShowFlag(null) }}>
                        返回
                    </button>
                </Modal.Footer>
            </Modal>
        </Container>
    )
}
export default IndexMsgUpload;
function useOrderSearching(props) {
    const [running, setRunning] = useState(false);
    useEffect(() => {
        if (running && props.pictures.filter((value) => {
            return value.order_name === undefined;
        }).length !== 0) {
            props.pictures.every((element, index) => {
                if (element.order_name !== undefined) return;
                axios
                    .get(`/file/message/recognize`, { params: { FileName: element.file_name } })
                    .then((response) => {
                        let temp_pictures = [...props.pictures];
                        temp_pictures.forEach((element, index) => {
                            if (element.order_name !== undefined) return;
                            temp_pictures[index].order_name = response.data;
                        });
                        props.setPictures(temp_pictures);
                    })
                return false;
            });
            if (props.pictures.filter((value) => {
                return value.order_name === undefined;
            }).length === 0)
                setRunning(false);
        }
    }, [running, props.pictures]);
    return [setRunning, props.pictures.filter((value) => {
        return value.order_name === undefined;
    }).length, running];
}
const useQuotationCreating = (props) => {
    const [quotation, setQuotation] = useState([]);
    const [creating, setCreating] = useState(props);
    useEffect(() => {
        if (quotation.some((each_quotation) => {
            return !each_quotation.hasOwnProperty('file_id')
        }) && creating) {
            let temp_quotation = JSON.parse(JSON.stringify(quotation))
            let findEmptyIndex = temp_quotation.findIndex((each_quotation, index) => {
                return !each_quotation.hasOwnProperty('file_id')
            })
            if (temp_quotation[findEmptyIndex].type === 'new') {
                if (!temp_quotation[findEmptyIndex].hasOwnProperty('file_id')) {
                    axios
                        .post(`/file/message/order_name`, { order_name: temp_quotation[findEmptyIndex].hasOwnProperty('圖號') ? temp_quotation[findEmptyIndex].圖號 : temp_quotation[findEmptyIndex].客戶圖號, file_name: temp_quotation[findEmptyIndex].fileName })
                        .then((response) => {
                            temp_quotation[findEmptyIndex].file_id = response.data['file_id'];
                            setQuotation(temp_quotation)
                        })
                }
            } else if (temp_quotation[findEmptyIndex].type === 'old') {
                if (!temp_quotation[findEmptyIndex].hasOwnProperty('file_id')) {
                    if (temp_quotation[findEmptyIndex].hasOwnProperty('history')) {
                        let firstIndex = temp_quotation[findEmptyIndex].history.findIndex(() => true);
                        if (firstIndex !== -1) {
                            axios
                                .post(`/file/message/history`, { fk: JSON.stringify({ TB001: temp_quotation[findEmptyIndex].history[firstIndex].TB001, TB002: temp_quotation[findEmptyIndex].history[firstIndex].TB002, TB003: temp_quotation[findEmptyIndex].history[firstIndex].TB003, ClientName: temp_quotation[findEmptyIndex].fileName || '', FileName: temp_quotation[findEmptyIndex].fileName || '' }) })
                                .then((response) => {
                                    temp_quotation[findEmptyIndex].file_id = response.data['file_id'] | response.data['id'];
                                    setQuotation(temp_quotation)
                                })
                        }
                    } else {
                        axios
                            .post(`/file/message/order_name`, { order_name: temp_quotation[findEmptyIndex].hasOwnProperty('圖號') ? temp_quotation[findEmptyIndex].圖號 : temp_quotation[findEmptyIndex].客戶圖號, file_name: temp_quotation[findEmptyIndex].fileName })
                            .then((response) => {
                                temp_quotation[findEmptyIndex].file_id = response.data['file_id'];
                                setQuotation(temp_quotation)
                            })
                    }
                    // axios
                    //     .post(`/file/message/history`, { order_name: temp_quotation[findEmptyIndex].hasOwnProperty('圖號')?temp_quotation[findEmptyIndex].圖號:temp_quotation[findEmptyIndex].客戶圖號, ClientName: temp_quotation[findEmptyIndex].fileName | '', FileName: temp_quotation[findEmptyIndex].fileName | '' })
                    //     .then((response) => {
                    //         temp_quotation[findEmptyIndex].file_id = response.data['file_id'];
                    //         setQuotation(temp_quotation)
                    //     })
                }
            }
            // setQuotation(temp_quotation)
        }
        console.log(quotation);
    }, [quotation, creating])



    /*
        temp_quotation.forEach(each_quotation=>{
            let paramter = [];
            if(each_quotation.type==='new'){
    
            }else if(each_quotation.type==='old'){
            }
            if(each_quotation.hasOwnProperty('fileName')){
                // messageContent[each_quotation.fileName]['file_name']
            }else{
                // each_quotation.hasOwnProperty('圖號')?each_quotation.圖號:each_quotation.客戶圖號
            }
        })
     */
    return [quotation, setQuotation, creating, setCreating];
}