import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import DataTable from "react-data-table-component";
import { Card } from "react-bootstrap";

function Datatable(props) {
    const [columns, setColumns] = useState();
    const [data, setData] = useState([]);
    const [checkedList, setCheckedList] = useState([]);
    const [List, setList] = useState([]);
    const [modalLoading, setModalLoading] = useState(true);
    const [totalRows, setTotalRows] = useState(0);
    const [title, setTitle] = useState("");
    const [module, setModule] = useState({});

    const handleShow = (e) => {
        setModalLoading(true);
        setTitle((
            <>編輯權限</>
        ));
        setModule({
            module_id: e.module_id,
            module_name: e.module_name
        });
        props.handleRowClick(e);
    }

    useEffect(() => {
        setColumns(props.columns)
        setData(props.data)
        setTotalRows(props.totalRows)
        setModalLoading(props.modalLoading)
        setList(props.List)
    }, [props.columns, props.List])

    useEffect(() => {
        setCheckedList(props.checkedList)
        setModalLoading(false)
    }, [props.checkedList]);

    return (
        <>
            <div className="card">
                <div className="card-header">權限設定</div>
                <div className="card-body">
                    <DataTable
                        columns={columns}
                        data={data}
                        // progressPending={loading}
                        pagination
                        paginationServer
                        highlightOnHover
                        pointerOnHover
                        paginationTotalRows={totalRows}
                        onRowClicked={handleShow}
                        onChangeRowsPerPage={() => { }}
                        onChangePage={() => { }}
                        onSelectedRowsChange={({ selectedRows }) => console.log(selectedRows)}
                        expandableRows
                        expandableRowsComponent={ExpandedComponent}
                        expandOnRowClicked={true}
                        expandableRowsHideExpander={true}
                        expandableRowsComponentProps={{ "List": List, "checkedList": checkedList, "modalLoading": modalLoading, "title": title, "module": module, "expandableRowsOnSubmit": (e) => props.handleOnSubmit(e) }}
                    />
                </div>
            </div>
        </>
    );
}

function ExpandedComponent(prop) {
    const [checkedList, setCheckedList] = useState([])
    useEffect(() => {
        setCheckedList(prop.checkedList)
    }, [prop.checkedList])
    const handleOnChange = (e) => {
        if (e.target.checked) {
            setCheckedList([...checkedList || [], { permission_id: parseInt(e.target.value, 10) }]);
        } else {
            setCheckedList(checkedList.filter(function (checkedList_each) {
                return checkedList_each.permission_id !== parseInt(e.target.value, 10)
            }));
        }
    }

    const handleOnSubmit = (e) => {
        e.preventDefault();
        prop.expandableRowsOnSubmit(checkedList);
    }

    return (
        <Card>
            <Card.Header>
                {prop.title}
            </Card.Header>
            <Card.Body>
                {prop.modalLoading ? <>讀取中...</> : (
                    <form onSubmit={handleOnSubmit}>
                        <div className="form-group row">
                            <label className="col col-sm-3 col-form-label d-flex flex-row-reverse">部門名稱：</label>
                            <div className="col col-sm">
                                <input type="text" readOnly className="form-control-plaintext" value={prop.data.module_name} />
                            </div>
                        </div>
                        <div className="form-group row">
                            <label className="col col-sm-3 form-check-label d-flex flex-row-reverse">權限：</label>
                            <div className="col col-sm">
                                <div className="row form-inline">
                                    {
                                        prop.List.map((List_value) => {
                                            return (
                                                <div className="col-xs-12 col-sm-6 col-lg-4 col-xl-3 d-flex flex-row" key={List_value.permission_id}>
                                                    <div className="form-check">
                                                        <label className="form-check-label">
                                                            <input className="form-check-input" type="checkbox" onChange={handleOnChange} value={List_value.permission_id} defaultChecked={prop.checkedList.some(checkedList => checkedList.permission_id === List_value.permission_id)} />
                                                            {List_value.permission_name}
                                                        </label>
                                                    </div>
                                                </div>
                                            )
                                        })
                                    }
                                </div>
                            </div>
                        </div>
                        <button className="btn btn-primary" type="submit">送出</button>
                    </form>
                )}
            </Card.Body>
        </Card>
    )
}
export default Datatable;