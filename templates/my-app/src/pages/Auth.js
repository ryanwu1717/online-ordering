import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Datatable from "../components/permission/Datatable";

const Auth = (props) => {
    const [columns, setColumns] = useState([]);
    const [data, setData] = useState([]);
    const [totalRows, setTotalRows] = useState(0);
    const [checkedList, setCheckedList] = useState([]);
    const [List, setList] = useState([]);
    const [loading, setLoading] = useState(null);
    const [modalLoading, setModalLoading] = useState(true);
    const [module, setModule] = useState({});
    const [submitLoading, setSubmitLoading] = useState(true)

    useEffect(() => {
        setLoading(true);
        axios
            .get('/system/permissions')
            .then((response) => {
                setList(response.data)
            })
    }, [submitLoading]);

    useEffect(() => {
        if (loading) {
            axios
                .get('/system/modules/permission')
                .then((response) => {
                    console.log(response.data)
                    setSubmitLoading(true);
                    setData(response.data);
                    setColumns([
                        {
                            name: '部門名稱',
                            selector: row => row.module_name,
                        },
                        {
                            name: '權限',
                            selector: row => row.permissions,
                        }
                    ]);
                })
        }
    }, [loading]);

    useEffect(() => {
        setTotalRows(data.length);
    }, [data]);

    useEffect(() => {
        axios
            .get('/system/module/permission', { params: { module_id: module.module_id } })
            .then((response) => {
                console.log(response.data)
                response.data.map(value => {
                    if (value.hasOwnProperty('permissions')) {
                        setCheckedList(value.permissions)
                    }
                    return value;
                })
            })
    }, [module]);

    const handleRowClick = (e) => {
        setModule({
            module_id: e.module_id,
            module_name: e.module_name
        })
    }

    const handleOnSubmit = (check) => {
        axios
            .patch(`/system/module/permission`, {
                data: [{
                    module_id: module.module_id,
                    permissions: check
                }]
            })
            .then(response => {
                console.log(response);
                setLoading(false);
                setSubmitLoading(false);
            })
    }

    return (
        <Datatable
            columns={columns}
            data={data}
            totalRows={totalRows}
            loading={loading}
            checkedList={checkedList}
            List={List}
            handleRowClick={handleRowClick}
            handleOnSubmit={handleOnSubmit}
        ></Datatable>
    )
}

export default Auth;