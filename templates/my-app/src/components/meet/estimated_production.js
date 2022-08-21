import React, { useState, useEffect, useCallback, useMemo } from 'react';
import DataTable from 'react-data-table-component';
import { FloatingLabel, Button, Row, Col, Form } from 'react-bootstrap';
import { CSVLink } from 'react-csv';

import "bootstrap/dist/js/bootstrap.bundle.js";
import "bootstrap/dist/css/bootstrap.css";
import axios from 'axios';
import './datatable.css'
import * as FileSaver from "file-saver";
import * as XLSX from "xlsx";
class EstimatedProductionBlock extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      customStyles : {
        rows: {
          highlightOnHoverStyle: {
            backgroundColor: 'rgb(230, 244, 244)',
            shadow: "12px 12px 7px rgba(0, 0, 0, 0.7)"
          },
        },
      },
      columns: [
        {
          name: '編號',
          selector: row => parseInt(row.編號),
          width: "12%",
          sortable: true,
          center: true,
          cell:	(row, index, column, id)=>{
            let text = <h5 onMouseEnter={this.onRowMouseEnter} >{row.編號}</h5>
            return text
          }
        },
        {
          name: '類別',
          selector: row => row.類別,
          sortable: true,
          width: "38%"
        },
        {
          name: '訂單數量',
          selector: row => Math.round(row.訂單數量),
          sortable: true,
          width: "25%"

        },
        {
          name: '預計產量',
          selector: row => Math.round(row.預計產量),
          sortable: true,
          width: "25%"
        },
      ],
      
      default_date: null,
      date_begin: null,
      date_end: null,
      datatable_col: [],
      csvDataHeader:
        [
          { label: "編號", key: "編號" },
          { label: "類別", key: "類別" },
          { label: "訂單數量", key: "訂單數量" },
          { label: "預計產量", key: "預計產量" },
        ],
    };
    this.getPreproduction = this.getPreproduction.bind(this);
    this.changeDateBegin = this.changeDateBegin.bind(this);
    this.changeDateEnd = this.changeDateEnd.bind(this);
    this.getChartData = this.getChartData.bind(this);
    // this.isHoliday = this.isHoliday.bind(this);
    this.dateChange = this.dateChange.bind(this);
    this.createCsvDataToday = this.createCsvDataToday.bind(this);
    this.onRowMouseEnter = this.onRowMouseEnter.bind(this);

  }
  componentDidMount() {
    let date_start_tmp = new Date();
    let date_end_tmp = new Date();
    date_start_tmp.setDate(date_start_tmp.getDate() - 7)
    let end = this.dateChange(date_end_tmp, "-")
    let start = this.dateChange(date_start_tmp, "-")
    this.setState({
      date_begin: start,
      date_end: end
    });
    this.getPreproduction(start, end)

  }

  getPreproduction(start, end) {
    let date_begin = null
    let date_end = null
    if (this.state.date_begin != null && this.state.date_end != null) {
      date_begin = this.state.date_begin.replaceAll('-', '')
      date_end = this.state.date_end.replaceAll('-', '')
    }
    if (date_begin == null || date_end == null) {
      date_begin = start.replaceAll('-', '')
      date_end = end.replaceAll('-', '')
    }
    axios.get('/Report/orderProductCategory', {
      //URL参數放在params屬性裏面
      params: {
        date_begin: date_begin,
        date_end: date_end
      }
    })
      .then((response) => {
        const item = response.data;
        this.setState({
          datatable_col: item
        })
        this.getChartData(item);

      })
      .catch((error) => console.log(error))
    let backgroundColor = [
      "rgba(95, 173, 86,0.5)", "rgba(169, 183, 82,0.5)", "rgba(242, 193, 78,0.5)", "rgba(245, 161, 81,0.5)",
      "rgba(247, 129, 84,0.5)", "rgba(205, 133, 93,0.5)", "rgba(162, 137, 102,0.5)", "rgba(77, 144, 120,0.5)",
      "rgba(180, 67, 108,0.5)", "rgba(50, 57, 57,0.5)", "rgba(129, 106, 114,0.5)",
      "rgba(164, 186, 183,0.5)", "rgba(202, 214, 188,0.5)", "rgba(239, 242, 192,0.5)", "rgba(202, 139, 113,0.5)",
      "rgba(158, 112, 163,0.5)", "rgba(113, 85, 213,0.5)", "rgba(86, 101, 99,0.5)",]
    let temp = []
    backgroundColor.map((value, key) => {
      let obj = {
        when: (row) => row.編號 == key + 1,
        style: {
          backgroundColor: value,
        }
      }
      temp.push(obj)
    });
    this.setState({
      columns: [

        {
          name: '編號',
          selector: row => parseInt(row.編號),
          width: "12%",
          sortable: true,
          conditionalCellStyles: temp,
          center: true
        },
        {
          name: '類別',
          selector: row => row.類別,
          sortable: true,
          width: "38%"
        },
        {
          name: '訂單數量',
          selector: row => Math.round(row.訂單數量),
          sortable: true,
          width: "25%"

        },
        {
          name: '預計產量',
          selector: row => Math.round(row.預計產量),
          sortable: true,
          width: "25%"
        },
      ]
    })
  }
  dateChange(date, text) {
    let dd = date.getDate();
    let mm = date.getMonth() + 1;
    let yyyy = date.getFullYear();
    if (dd < 10) {
      dd = "0" + dd
    }
    if (mm < 10) {
      mm = "0" + mm
    }
    let temp = `${yyyy}${text}${mm}${text}${dd}`
    return temp
  }
  getChartData(item) {
    let row = item
    let label_arr = []
    let data_arr = []
    Object.keys(row).map((key) => {
      let item = row[key]
      Object.keys(item).map((item_key) => {
        if (item_key == "類別") {
          label_arr.push(item[item_key])
        }
        else if (item_key == "訂單數量") {
          data_arr.push(item[item_key])
        }
      })
    })
    this.props.resetData(
      {
        labels: label_arr,
        data: data_arr
      }
    );
   
  }
  changeDateBegin(event) {
    this.setState({
      date_begin: event.target.value,
    });
  }
  changeDateEnd(event) {
    this.setState({
      date_end: event.target.value,
    });
  }
  createCsvDataToday(e) {
    if (e.target.attributes.output.value === 'csv') {
      document.getElementById("csvEst").click();
    } else {
      let res_data = [];
      this.state.datatable_col.map((value, index) => (
        res_data.push({
          "編號": value.編號,
          "類別": value.類別,
          "訂單數量": value.訂單數量,
          "預計產量": value.預計產量
        })
      ))

      let ws = XLSX.utils.json_to_sheet(res_data);
      let wb = { Sheets: { data: ws }, SheetNames: ["data"] };
      let excelBuffer = XLSX.write(wb, { bookType: e.target.attributes.output.value, type: "array" });
      let data = new Blob([excelBuffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8" });
      FileSaver.saveAs(data, '訂單產品類別預計產量.' + e.target.attributes.output.value);

    }
  }
  onRowMouseEnter(event){
    console.log(123)
  }
  render() {

    return (
      <Row>
        <Form.Group as={Row} controlId="formGridState">
          <Col>
            <FloatingLabel label="起:" className="mb-2">
              <Form.Control
                id="date_begin" type="date" defaultValue={this.state.date_begin} onChange={this.changeDateBegin} className="form-control"
              />
            </FloatingLabel>
          </Col>
          <Col>
            <FloatingLabel label="迄:" className="mb-2">
              <Form.Control
                id="date_end" type="date" defaultValue={this.state.date_end} onChange={this.changeDateEnd} className="form-control" value={this.state.date_end}
              />
            </FloatingLabel>
          </Col>
          <Col md="auto">
            <Button variant="primary" onClick={this.getPreproduction}>確定</Button>{' '}

          </Col>
          <Col md="auto">
            <CSVLink id="csvEst" filename='訂單產品類別預計產量' data={this.state.datatable_col} headers={this.state.csvDataHeader} ></CSVLink>
            <Button output="csv" variant="success" onClick={this.createCsvDataToday}>CSV</Button>
          </Col>
          <Col md="auto">
            <Button output="xlsx" variant="light" onClick={this.createCsvDataToday} style={{ width: 'auto', background: "#507958", color: "white", }}>XLSX</Button>
          </Col>
          <Col md="auto">
            <Button output="xls" variant="light" onClick={this.createCsvDataToday} style={{ width: 'auto', background: "#135721", color: "white", }}>XLS</Button>
          </Col>
        </Form.Group>
        <h5 style={{ fontWeight: "bold" }}>註：資料數據不含指定結案及製令5202、5203、5204、5205、5198、5199、5207</h5>
        <Form.Group as={Row} >
          <DataTable
            columns={this.state.columns}
            data={this.state.datatable_col}
            fixedHeaderScrollHeight="300px"
            pagination
            responsive
            subHeaderAlign="right"
            subHeaderWrap
            paginationPerPage={20}
            // conditionalRowStyles={this.state.conditionalRowStyles}
            highlightOnHover
            defaultSortFieldId={3}
            defaultSortAsc={false}
            // onRowDoubleClicked={this.onRowMouseEnter}
            customStyles={this.state.customStyles}
          />
        </Form.Group>
      </Row>
    );
  }
}
export default EstimatedProductionBlock;