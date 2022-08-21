import React, { useState, useEffect, useCallback, useMemo } from 'react';
// import Card from 'react-bootstrap/Card';
// import React, {} from 'react';
import Container from 'react-bootstrap/Container';
import Button from 'react-bootstrap/Button';
import Card from 'react-bootstrap/Card';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import Form from 'react-bootstrap/Form';

import Alert from 'react-bootstrap/Alert';
import { useDropzone } from 'react-dropzone'
import Image from 'react-bootstrap/Image'
import ToggleButton from 'react-bootstrap/ToggleButton'
import ToggleButtonGroup from 'react-bootstrap/ToggleButtonGroup'
import DataTable from 'react-data-table-component';
import "bootstrap/dist/js/bootstrap.bundle.js";
import "bootstrap/dist/css/bootstrap.css";
import axios from 'axios';
class MeetingSetting extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      customer_name: "",
      customer_number: "",
      hardness: "",
      meet_id: null,
      number: null,
      titanium_plating: null,
      material: null,
      factory_delivery_date: null,
      customer_expected_delivery_date: null
    }
    this.changeCustomerName = this.changeCustomerName.bind(this);
    this.changeCustomerNumber = this.changeCustomerNumber.bind(this);
    this.changeTitaniumPlating = this.changeTitaniumPlating.bind(this);
    this.changeMaterial = this.changeMaterial.bind(this);
    this.changeHardness = this.changeHardness.bind(this);
    this.changeNumber = this.changeNumber.bind(this);
    this.changeFactoryDeliveryDate = this.changeFactoryDeliveryDate.bind(this);
    this.changeCustomerExpectedDeliveryDate = this.changeCustomerExpectedDeliveryDate.bind(this);
  }

  componentDidMount() {
    // console.log(this.props.test1)
    let information = this.props.test1
    let content = this.props.test1.content["0"]
    console.log(information);
    if (information != undefined) {
      console.log(information);
      this.setState({
        customer_number: content.customer_code,
        meet_id: information.meet_id,
        number: content.number,
        hardness:content.hardness,
        titanium_plating: content.titanium_plating,
        material: content.material,
        factory_delivery_date: content.factory_delivery_date,
        customer_expected_delivery_date: information.content.customer_expected_delivery_date
      })
    }
  }
  changeCustomerName(event) {
    this.setState({
      customer_name: event.target.value,
    });
  }
  changeCustomerNumber(event) {
    this.setState({
      customer_number: event.target.value,
    });
  }
  changeTitaniumPlating(event) {
    this.setState({
      titanium_plating: event.target.value,
    });
  }
  changeMaterial(event) {
    this.setState({
      material: event.target.value,
    });
  }
  changeHardness(event) {
    this.setState({
      hardness: event.target.value,
    });
  }
  changeNumber(event) {
    this.setState({
      number: event.target.value,
    });
  }
  changeFactoryDeliveryDate(event) {
    this.setState({
      factory_delivery_date: event.target.value,
    });
  }
  changeCustomerExpectedDeliveryDate(event) {
    this.setState({
      customer_expected_delivery_date: event.target.value,
    });
  }
  render() {
    return (
      <><Col md="12">
        <Card className='shadow'>
          <Card.Header>
            <label> </label>
          </Card.Header>
          <Card.Body>
            <Form.Group as={Row} controlId="formGridState" className='gy-3 row-cols-2 row-cols-lg-5'>
              <Col md="auto">
                <Form.Label>客戶名稱:</Form.Label>
                <Form.Control type="text" defaultValue={this.state.customer_name} value={this.state.customer_name || ''} onChange={this.changeCustomerName} />
              </Col>
              <Col md="auto">
                <Form.Label>客戶代號:</Form.Label>
                <Form.Control type="text" defaultValue={this.state.customer_number} value={this.state.customer_number || ''} onChange={this.changeCustomerNumber} />
              </Col>
              <Col md="auto">
                <Form.Label>鍍鈦:</Form.Label>
                <Form.Control type="text" defaultValue={this.state.titanium_plating} value={this.state.titanium_plating || ''} onChange={this.changeTitaniumPlating} />
              </Col>
              <Col md="auto">
                <Form.Label>材質:</Form.Label>
                <Form.Control type="text" defaultValue={this.state.material} value={this.state.material || ''} onChange={this.changeMaterial} />
              </Col>
              <Col md="auto">
                <Form.Label>硬度:</Form.Label>
                <Form.Control type="text" defaultValue={this.state.hardness} value={this.state.hardness || ''} onChange={this.changeHardness} />
              </Col>
              <Col md="auto">
                <Form.Label>數量:</Form.Label>
                <input type="number" min="0" className="form-control" defaultValue={this.state.number} value={this.state.number || 0} onChange={this.changeNumber} />
              </Col>
              <Col md="auto">
                <Form.Label>廠內交期:</Form.Label>
                <Form.Control type="date" defaultValue={this.state.factory_delivery_date} value={this.state.factory_delivery_date || ''} onChange={this.changeFactoryDeliveryDate} />
              </Col>
              <Col md="auto">
                <Form.Label>客戶預期交期:</Form.Label>
                <Form.Control type="date" defaultValue={this.state.customer_expected_delivery_date} value={this.state.customer_expected_delivery_date || ''} onChange={this.changeCustomerExpectedDeliveryDate} />
              </Col>
            </Form.Group>
          </Card.Body>
        </Card>
      </Col>

      </>
    );
  }
}
export default MeetingSetting;
