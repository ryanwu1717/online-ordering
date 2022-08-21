import React, { useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom';
import EstimatedProductionBlockV2 from './estimatedProductionBlockV2.js';
import PreproductionBlockV2 from './preproductionV2.js';
import CategoryBlock from './cetegory.js';
import Container from 'react-bootstrap/Container';
import Card from 'react-bootstrap/Card';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';

import axios from 'axios';
class AddDeliveryMeeting extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			datas: {
				labels: [],
				data: [],
				pre_data: []
			},
			meet_setting: {

			},
			modal_data: {
				show: false,
				title: "",
				body: "",
				footer: "",
				size: "lg"
			},
			number: null,
			table_number: null,
		}
		this.chertChild = React.createRef();
		this.meetingChild = React.createRef();
		this.modalChild = React.createRef();
		this.changeDatas = this.changeDatas.bind(this);
		this.changeMeetSearch = this.changeMeetSearch.bind(this);
		this.setModal = this.setModal.bind(this);
		this.handleSetLabel = this.handleSetLabel.bind(this);
		this.handleSetTable = this.handleSetTable.bind(this);
		this.handleSetLabelLeave = this.handleSetLabelLeave.bind(this);

	}
	componentDidMount() {

	}
	componentDidUpdate() {

	}
	changeDatas(data) {
		this.setState({
			datas: {
				labels: data.labels,
				data: data.data,
				pre_data: data.pre_data
			}
		})
		this.chertChild.current.changeLabelAndData(data.labels, data.data)
	}
	changeMeetSearch(data) {
		let datas = data.data
		this.setState({
			meet_setting: datas
		})
	}
	setModal(data) {
		this.setState({
			modal_data: {
				title: data.title,
				body: data.body,
				footer: data.footer,
				size: data.size
			}
		})
		this.modalChild.current.change(data.data)
		this.modalChild.current.openModal()
	}
	handleSetLabel(number) {
		this.setState({ number: parseInt(number) - 1 })
		this.setState({ table_number: parseInt(number) - 1 })
	}
	handleSetLabelLeave () {
		this.setState({ number: null })
		this.setState({ table_number: null })
	}
	handleSetTable(number) {
		this.setState({ number: parseInt(number) })
		this.setState({ table_number: parseInt(number) })
	}
	render() {
		
		return (
			<Container fluid className="d-grid gap-2 my-3">
				{/* <ChangeModal ref={this.modalChild} setting={this.state.modal_data} />
        <Row>
          <Col md="12">
            <Card className='shadow'>
              <Card.Body>
                <RatioBlock />
              </Card.Body>
            </Card>
          </Col>
        </Row>
        <Row>
          <Col md="12">
            <MeetingInformationNew setModal={this.setModal} resetData={this.changeMeetSearch} />
          </Col>
        </Row>
        <Row>
          {this.state.meet_setting.meet_id > 0 ? <Col md="12"><MeetingSettingNew ref={this.meetingChild} datas={this.state.meet_setting} /></Col> : null}
        </Row>
        <Row>
          <Col md="12">
            <Card className='shadow'>
              <Card.Header>
                <label> </label>
              </Card.Header>
              <Card.Body>
                <div>
                  <UploadBlock />
                </div>
              </Card.Body>
            </Card>
          </Col>
        </Row> */}
				<Row>
					<Col md="12">
						<Card className='shadow'>
							<Card.Header>
								<h5>預計生產報表</h5>
							</Card.Header>
							<Card.Body>
								<div>
									<PreproductionBlockV2 />
								</div>
							</Card.Body>
						</Card>
					</Col>
				</Row>
				<Row>
					<Col md="7">
						<Card className='shadow'>
							<Card.Header>
								<h5>訂單產品類別產量</h5>
							</Card.Header>
							<Card.Body>
								<EstimatedProductionBlockV2 handleSetLabel={this.handleSetLabel} handleSetLabelLeave={this.handleSetLabelLeave} resetData={this.changeDatas} />
							</Card.Body>
						</Card>
					</Col>
					<Col md="5">

						<Card className='shadow'>
							<Card.Header>
								<h5>訂單數量統計圓餅圖</h5>
							</Card.Header>
							<Card.Body>
								<CategoryBlock ref={this.chertChild} datas={this.state.datas} number={this.state.number} handleSetLabelLeave={this.handleSetLabelLeave} handleSetTable={this.handleSetTable} />
							</Card.Body>
						</Card>
						{
							this.state.table_number !== null ?
								<Card className='shadow'>
									<Card.Body>
										<Row>
											<Col md="2"><h6>{this.state.table_number + 1}</h6></Col>

											<Col md="6"><h6>{this.state.datas.labels[this.state.table_number]}</h6></Col>

											<Col md="4"><h6>訂單數量：{Math.round(this.state.datas.data[this.state.table_number])}</h6></Col>
										</Row>
										<Row>
											<Col md="2"></Col>

											<Col md="6"></Col>

											<Col md="4"><h6>預計產量：{Math.round(this.state.datas.pre_data[this.state.table_number])}</h6></Col>
										</Row>
									</Card.Body>
								</Card> : null
						}

					</Col>
				</Row>
			</Container>
		);
	}
}

export default AddDeliveryMeeting;