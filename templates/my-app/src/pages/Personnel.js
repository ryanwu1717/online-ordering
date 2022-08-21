import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Datatable from "../components/permission/Datatable";

const Personnel = (props) => {
    const [List, setList] = useState([])
    const [checkedList, setCheckedList] = useState([])
    const [columns, setColumns] = useState([])
    const [data, setData] = useState([])
    const [loading, setLoading] = useState(null)
    const [totalRows, setTotalRows] = useState(0)
    const [module, setModule] = useState([])

    useEffect(() => {
        setLoading(true)
        setList([
            {
                "user_id": 1,
                "user_name": 'Thomas'
            }, {
                "user_id": 2,
                "user_name": 'Kevin'
            }, {
                "user_id": 3,
                "user_name": 'David'
            },
        ])
    }, [])

    useEffect(() => {
        if (loading) {
            setData([
                {
                    "module_id": 1,
                    "module_name": '業務',
                    "users": "Thomas, Kevin, David"
                }, {
                    "module_id": 2,
                    "module_name": '研發',
                    "users": "Thomas, Kevin, David"
                }, {
                    "module_id": 3,
                    "module_name": '製圖',
                    "users": "Thomas, Kevin, David"
                }
            ])
            setColumns([
                {
                    name: '部門名稱',
                    selector: row => row.module_name
                }, {
                    name: '部門人員',
                    selector: row => row.users
                }
            ])
        }
    }, [loading])

    useEffect(() => {
        setTotalRows(data.length)
    }, [data])

    const handleRowClick = (e) => {
        setModule({
            module_id: e.module_id,
            module_name: e.module_name
        })
    }

    useEffect(() => {
        setCheckedList([
            { user_id: 1 },
            { user_id: 2 },
            { user_id: 3 }
        ])
        console.log(checkedList)
    }, [module])

    const handleOnSubmit = () => {

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
        >
        </Datatable>
    )
}

export default Personnel;