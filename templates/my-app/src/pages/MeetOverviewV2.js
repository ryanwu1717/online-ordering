import DatatableCard from '../components/DatatableCard';
import BoxTabs from '../components/BoxTabs';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Button, Form, ButtonGroup, Row, Col } from "react-bootstrap";
import React from "react";
import { Tooltip } from 'antd';
import Datatable from '../components/Datatable';
import moment from 'moment';

class MeetOverviewV2 extends React.Component {
    constructor(props) {
        super(props);
        this.state = (
            {
                tabs_data: [
                    { id: 0, title: "會議記錄", eventKey: "record", api_location: "/CRM/record_meet" },
                    { id: 1, title: "客訴", eventKey: "customer", api_location: "/CRM/complaint/complaint" },
                    { id: 2, title: "追蹤事項", eventKey: "track", api_location: "/CRM/complaint/tracking" },
                ],
                datatables: {
                    require: {
                        coptd_td001: null,
                        coptd_td002: null,
                        coptd_td003: null,
                        coptc_tc003: null,
                        date_begin: null,
                        date_end: null,
                    },
                    request_label: {
                        name: '會議名稱',
                        recorder_name: '紀錄人',
                        meet_date: '日期',
                    },
                },
                datatables_range: [
                    {
                        require: {
                            meet_id: 1,
                        },
                        thead: [

                            {
                                name: '主旨',
                                cell: row => <Tooltip placement="rightTop" title='查看詳細內容'>
                                    <Button onClick={this.openMeet} meet_id={row.meet_id} variant="link">{row.meet_name}</Button>
                                </Tooltip>,
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '建立者',
                                cell: row => row.recorder_name || '',
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '建立日期',
                                cell: row => row.modify_time === null ? '' : row.modify_time.split(" ")[0],
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '修改者',
                                cell: row => row.recorder_name || '',
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '修改日期',
                                cell: row => row.modify_time === null ? '' : row.modify_time.split(" ")[0],
                                width: 'auto',
                                center: true,
                                sortable: true,
                            },
                        ]
                    },
                    {
                        require: {
                            complaint_id: []
                        },
                        thead: [
                            {
                                name: '主旨',
                                cell: row => <Tooltip placement="rightTop" title='查看詳細內容'>
                                    <Button onClick={this.openComplaint} complaint_id={row.complaint_id} variant="link">{row.subject}</Button>
                                </Tooltip>,
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '建立者',
                                cell: row => row.name || '',
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '建立日期',
                                selector: row => row.complaint_date === null ? '' : row.complaint_date.split(" ")[0],
                                cell: row => row.complaint_date === null ? '' : row.complaint_date.split(" ")[0],
                                width: 'auto',
                                center: true,
                                sortable: true,
                            },
                            {
                                name: '客訴單',
                                cell: row => <Tooltip placement="rightTop" title='查看客訴單' >
                                    <Button onClick={this.openSheet} complaint_id={row.complaint_id} variant="link">{row.subject}</Button>
                                </Tooltip>,
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '修改者',
                                cell: row => row.edit_user_name || '',
                                width: 'auto',
                                center: true,
                            },
                            {
                                name: '修改日期',
                                selector: row => row.edit_date,
                                cell: row => row.edit_date === null ? '' : moment(this.state.edit_date).format('YYYY-MM-DD'),
                                width: 'auto',
                                center: true,
                                sortable: true,
                            },
                        ]
                    },
                    {
                        require: {
                            tracking_id: []
                        },
                        thead: [
                            {
                                name: '標題',
                                cell: row => row.name,
                                center: true,
                            },
                            {
                                name: '說明',
                                cell: row => row.content,
                                center: true,
                            },
                            {
                                name: '權責單位',
                                cell: row => row.module_name,
                                center: true,
                            },
                            {
                                name: '追蹤人',
                                cell: row => row.person_in_charge_name,
                                center: true,
                            },
                            {
                                name: '建立日期',
                                cell: row => row.create_date,
                                center: true,
                            },
                            {
                                name: '完成日期',
                                cell: row => row.complete_date || "-" ,
                                center: true,
                            },
                            
                            
                        ]
                    },
                ],
                delete_arr: [],
                tableRef: [],
                activity: 0,
                row: [],
            }
        )
        this.postProcess = this.postProcess.bind(this);
        this.openSheet = this.openSheet.bind(this);
        this.openMeet = this.openMeet.bind(this);
        this.customizeCardGrandParent = this.customizeCardGrandParent.bind(this);
        this.rowClickedHandler = this.rowClickedHandler.bind(this);
        this.handlerClick = this.handlerClick.bind(this);
        this.handlerDelete = this.handlerDelete.bind(this);
        this.handlerCreate = this.handlerCreate.bind(this);

        this.addz = this.addz.bind(this);
        this.childref = React.createRef();
    }
    addz(num, length) {
        if (num.length >= length) { return num }
        else {
            return this.addz(("0" + num), length)
        }
    }

    openSheet(e) {
        let url = `/CRM/complaint/qualityForm/${e.target.attributes.complaint_id.value}`;
        window.open(url, '_blank');
    }

    openComplaint(e) {
        let url = `CRM/complaint/customer/${e.target.attributes.complaint_id.value}`;
        window.open(url, '_blank');
    }

    postProcess(response) {
        this.setState({ delete_arr: [] })
        return response
    }
    openMeet(e) {
        let url = `CRM/complaint/meet/customer/${e.target.attributes.meet_id.value}`;
        window.open(url, '_blank');
    }

    imgTransport() {

    }

    rowClickedHandler = (row, e) => {
        let tab_key = this.childref.current.state.key
        if (tab_key === "customer") {
            if (this.state.delete_arr.indexOf(row.complaint_id) === -1) {
                Object.assign(e.target.parentElement.style, { background: "#ffe8e8" });
                let delete_arr_temp = [...this.state.delete_arr]
                delete_arr_temp.push(row.complaint_id)
                this.setState({ delete_arr: delete_arr_temp })
            } else {
                Object.assign(e.target.parentElement.style, { background: "#ffffff" });
                let delete_arr_temp = [...this.state.delete_arr]
                delete_arr_temp.splice(this.state.delete_arr.indexOf(row.complaint_id), 1)
                this.setState({ delete_arr: delete_arr_temp })
            }
        }
        else if (tab_key === "record") {
            if (this.state.delete_arr.indexOf(row.meet_id) === -1) {
                Object.assign(e.target.parentElement.style, { background: "#ffe8e8" });
                let delete_arr_temp = [...this.state.delete_arr]
                delete_arr_temp.push(row.meet_id)
                this.setState({ delete_arr: delete_arr_temp })
            } else {
                Object.assign(e.target.parentElement.style, { background: "#ffffff" });
                let delete_arr_temp = [...this.state.delete_arr]
                delete_arr_temp.splice(this.state.delete_arr.indexOf(row.meet_id), 1)
                this.setState({ delete_arr: delete_arr_temp })
            }
        }
        else if (tab_key === "track") {
            console.log(row)
            if (this.state.delete_arr.indexOf(row.id) === -1) {
                Object.assign(e.target.parentElement.style, { background: "#ffe8e8" });
                let delete_arr_temp = [...this.state.delete_arr]
                delete_arr_temp.push(row.id)
                this.setState({ delete_arr: delete_arr_temp })
            } else {
                Object.assign(e.target.parentElement.style, { background: "#ffffff" });
                let delete_arr_temp = [...this.state.delete_arr]
                delete_arr_temp.splice(this.state.delete_arr.indexOf(row.id), 1)
                this.setState({ delete_arr: delete_arr_temp })
            }

        }

    }

    customizeCardGrandParent(response) {
        let data = {};
        let return_package = {};
        response.map((row, i) => {
            if (i === response.req_id) {
                Object.keys(row).map((key) => {
                    if (key !== "src") {
                        data[key] = row[key];
                    }
                })
                return_package['datas'] = data;
                return_package['image_temp'] = row.src;
                return_package['image'] = [];
            }
        })
        return return_package;
    }

    handlerClick(tab) {
        let activity = '';
        this.state.tabs_data.map((value, index) => (
            value.eventKey === tab ? activity = value.id : null
        ))
        // console.log(activity)
        this.setState({ activity: activity })
    }
    handlerCreate() {
        let tab_key = this.childref.current.state.key
        if (tab_key === "customer") {
            let url = `CRM/complaint/customer/0`;
            window.open(url, '_blank');
        }
        else if (tab_key === "record") {
            let url = `CRM/complaint/meet/customer/0`;
            window.open(url, '_blank');
        }
        else {
            console.log("it is " + tab_key)
        }

    }
    handlerDelete() {
        let datatables_range = [...this.state.datatables_range];
        let tab_key = this.childref.current.state.key
        if (tab_key === "customer") {
            let customer_datatable = datatables_range[1].require;
            customer_datatable.complaint_id = this.state.delete_arr;
            datatables_range[1].require = customer_datatable;
            this.setState({ datatables_range: datatables_range })
        }
        else if (tab_key === "record") {
            let record_datatable = datatables_range[0].require;
            record_datatable.meet_id = this.state.delete_arr;
            datatables_range[0].require = record_datatable;
            this.setState({ datatables_range: datatables_range })
        }
        else if (tab_key === "track") {
            let tracking_datatable = datatables_range[2].require;
            tracking_datatable.tracking_id = this.state.delete_arr;
            datatables_range[2].require = tracking_datatable;
            this.setState({ datatables_range: datatables_range })
        }
        this.state.tableRef[this.state.activity].current.handleDelete();
    }

    componentDidMount() {
        let table_ref = [];
        this.state.tabs_data.map((value, index) => (
            table_ref.push(React.createRef())
        ))
        this.setState({
            tableRef: table_ref,
        });
    }

    render() {
        return <>
            <Row>
                <Col md="12" style={{ display: 'flex', justifyContent: 'right' }}>
                    {
                        this.state.activity!==2 ? 
                        <>
                        <Button className='mx-3' onClick={this.handlerCreate} style={{ width: 'auto', background: "#6b778d", color: "white", border: 'none', fontWeight: "bold" }} variant="light" >新增</Button>
                        </>: null
                    }
                    <Button onClick={this.handlerDelete} style={{ width: 'auto', background: "#ff6768", color: "white", border: 'none', fontWeight: "bold" }} variant="light" >刪除</Button>
                </Col>
            </Row>
            <BoxTabs ref={this.childref} handlerClick={this.handlerClick} tableRef={this.state.tableRef} datatables={this.state.datatables_range} postProcess={this.postProcess} rowClickedHandler={this.rowClickedHandler} tabsControl={this.state.tabs_data}></BoxTabs>
            {/* <Datatable api_location="/CRM/complaint/complaint" /> */}
        </>
    }
}

export default MeetOverviewV2