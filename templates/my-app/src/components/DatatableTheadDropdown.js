import React, { useState, useEffect } from 'react';
import { Table, Col, Row } from "react-bootstrap";
import { FaTimes } from "react-icons/fa";

const DatatableTheadDropdown = (props) => {
    const editMode = true;
    const [tableData, setTableData] = useState([]);
    const [tableOption, setTableOption] = useState([]);
    const [tableDataEnsure, setTableDataEnsure] = useState(false);
    useEffect(() => {
        setTableData(props.tableData)
        setTableOption(props.tableOption)
    }, []);

    useEffect(() => {
        setTableData(props.tableData)
        setTableDataEnsure(false)
    }, [props.tableData]);

    const deleteCol = (e,col_id) => {
        const index = col_id;
        let temp_tableData = JSON.parse(JSON.stringify(tableData));
        temp_tableData.map((value, key) => (
            temp_tableData[key].splice(index, 1)
        ))
        setTableData(temp_tableData);
    }
    const remainCol = (e,col_id) => {
        const index = col_id;
        let temp_tableData = JSON.parse(JSON.stringify(tableData));
        temp_tableData.map((value, key) => (
            temp_tableData[key] = [temp_tableData[key][index]]
        ))
        temp_tableData = temp_tableData.filter((value, key) => {
            if(value.length===0){
                return false;
            }else{
                if(value[0]==='')
                    return false;
            }
            return true;
        })
        setTableData(temp_tableData);
        setTableDataEnsure(true);
    }

    const deleteRow = (e,row_id) => {
        const index = row_id;
        let temp_tableData = JSON.parse(JSON.stringify(tableData));
        temp_tableData.splice(index, 1)
        setTableData(temp_tableData);
    }
    useEffect((e)=>{
        if(tableDataEnsure)
            props.onSubmit(e,tableData);
    },[tableData]);

    return (
        <>
            <div className="col-12" style={{overflowX:'scroll'}}>
                <Table className='my-4' style={{minWidth: ((tableData.length!==0?tableData[0].length:0)*200)+'px'}}>
                    <thead>
                        {tableData.map( (data, row_id)=> {
                            return row_id===0?(
                                <tr>
                                    <th style={{minWidth: '200px'}}>#</th>
                                    {tableData[row_id].map( (item, col_id, array)=>(
                                        <th style={{minWidth: '200px'}} >
                                            {editMode?
                                            <>
                                                <Row style={{ position: "relative" }}>
                                                    <Col md="auto">
                                                        <button type="button" className='btn btn-outline-primary'onClick={e=>remainCol(e,col_id)}>此為圖號</button>
                                                    </Col>
                                                </Row>
                                            </>:
                                            <>
                                                <Row style={{ position: "relative" }}>
                                                    <Col md="auto">
                                                        <select className='form-control'>
                                                            <option value={null}>無對應</option>
                                                            {Object.keys(tableOption).map(key=>{
                                                                return <option value={key}>{tableOption[key]}</option>
                                                            })}
                                                        </select>
                                                    </Col>
                                                    <Col style={{ position: "absolute", top: -20, padding: '0.2rem', display: "flex", justifyContent: "right" }}>
                                                        <button
                                                            className="btn"
                                                            style={{ height: 10, position: "absolute", top: 15, }}
                                                            onClick={e=>deleteCol(e,col_id)}
                                                            >
                                                        <FaTimes style={{ position: "absolute", top: -1, right: 4, color: "#8c979b" }} />
                                                        </button>
                                                    </Col>
                                                </Row>
                                            </>}
                                        </th>
                                    ))}
                                </tr>
                            ):<></>
                        })}
                    </thead>
                    <tbody>
                        {tableData.map((data, row_id) => {
                            return(
                            <tr>
                                <td>
                                    <button
                                        className="btn"
                                        style={{ backgroundColor: "transparent" }}
                                        onClick={(e)=>{deleteRow(e,row_id)}}
                                    >
                                        <FaTimes style={{color: "#8c979b" }} />
                                    </button>
                                </td>
                                {data.map(function (item, index, array) {
                                    return <td>{item}</td>
                                })}
                            </tr>
                        )})}

                    </tbody>
                </Table>
            </div>
        </>
    );
}

export default DatatableTheadDropdown;
