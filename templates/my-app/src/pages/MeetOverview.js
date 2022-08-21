import DatatableCard from '../components/DatatableCard';
import BoxTabs from '../components/BoxTabs';
import 'bootstrap/dist/css/bootstrap.min.css';
import React from "react";

class MeetOverview extends React.Component {
    constructor(props) {
        super(props);
        this.state = (
            {
                tabs_data: [
                    { title: "全部", eventKey: "home" },
                    { title: "產出", eventKey: "shop" },
                    { title: "交期", eventKey: "time" },
                    { title: "客訴", eventKey: "customer" },
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
                        name: '會議名',
                        recorder_name: '紀錄人',
                        meet_date: '日期',
                    },
                    request: '/CRM/complaint/meets',
                },
            }
        )
        this.child = React.createRef();
        this.onDatatablesReponse = this.onDatatablesReponse.bind(this);
        this.customizeCardGrandParent = this.customizeCardGrandParent.bind(this);
        this.imgTransport = this.imgTransport.bind(this);
    }

    onDatatablesReponse(response) {
        let need_column = [];
        response.data.data.map((data_row, id) => {
            need_column = [];
            data_row.map((data, i) => {
                need_column.push(
                    {
                        name: data.name,
                        recorder_name: data.recorder_name,
                        meet_date: data.meet_date || '',
                    }
                )
            })
            response.data.data[id] = need_column;
        })
        return response
    }

    imgTransport(){

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

    render() {
        return <>
            <BoxTabs tabsControl={this.state.tabs_data}></BoxTabs>
            <DatatableCard datatables={this.state.datatables} ref={this.child} postProcess={this.onDatatablesReponse} customizeCardGrandParent={this.customizeCardGrandParent} imgTransport={this.imgTransport} />
        </>
    }
}

export default MeetOverview