import React, { useState, useEffect, useCallback, useMemo } from 'react';
// import Card from 'react-bootstrap/Card';
// import React, {} from 'react';
import Container from 'react-bootstrap/Container';
import Button from 'react-bootstrap/Button';
import Card from 'react-bootstrap/Card';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import Form from 'react-bootstrap/Form';
import SERACH from '../Search';
import Alert from 'react-bootstrap/Alert';
import { useDropzone } from 'react-dropzone'
import Image from 'react-bootstrap/Image'
import ToggleButton from 'react-bootstrap/ToggleButton'
import ToggleButtonGroup from 'react-bootstrap/ToggleButtonGroup'
import DataTable from 'react-data-table-component';
class MeetingSettingNew extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      setting_data: {
        number: 0,
        titanium_plating: "",
        material: "",
        factory_delivery_date: "",
        customer_expected_delivery_date: "",
        customer_code: "",
        customer_id: "",
        hardness: "",
      },
      search_input: [
        { 'label': '客戶名稱:', 'id': 'customer_code', 'type': 'input', 'value': this.props.datas.customer_code,'disabled':true },
        { 'label': '客戶代號:', 'id': 'customer_id', 'type': 'input', 'value': this.props.datas.customer_id,'disabled':true },
        { 'label': '鍍鈦:', 'id': 'titanium_plating', 'type': 'input', 'value': this.props.datas.titanium_plating,'disabled':true },
        { 'label': '材質:', 'id': 'material', 'type': 'input', 'value': this.props.datas.material,'disabled':true },
        { 'label': '硬度:', 'id': 'hardness', 'type': 'input', 'value': this.props.datas.hardness,'disabled':true },
        { 'label': '數量:', 'id': 'number', 'type': 'input', 'value': this.props.datas.number,'disabled':true },
        { 'label': '廠內交期:', 'id': 'factory_delivery_date', 'type': 'date', 'value': this.props.datas.factory_delivery_date,'disabled':true },
        { 'label': '客戶預期交期:', 'id': 'customer_expected_delivery_date', 'type': 'date', 'value': this.props.datas.customer_expected_delivery_date,'disabled':true},
        { 'label': '交期變更:', 'id': 'change_delivery_date', 'type': 'date', 'value': this.props.datas.change_delivery_date },
      ]
    }
  }
  componentDidMount() {

  }
  handleChange(data) {
    const temp = this.state.setting_data;
    Object.keys(temp).map((key, value) => {
      if (key === data.id) {
        temp[key] = data.value.trim() === '' ? null : data.value.trim();
        return;
      }
    })
    this.setState({
      setting_data: temp
    })
    console.log(temp)
  }
  render() {
    return (
      <Col md="12">
        <Card className='shadow'>
          <Card.Header>
            <label> </label>
          </Card.Header>
          <Card.Body>
            <Form.Group as={Row} controlId="formGridState" className='gy-3 row-cols-2 row-cols-lg-5'>
              <SERACH resetData={this.handleChange} name={this.state.search_input}></SERACH>
            </Form.Group>
          </Card.Body>
        </Card>
      </Col>
    );
  }
}
export default MeetingSettingNew;
