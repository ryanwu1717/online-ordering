import React, { useRef, useState, useEffect, useCallback, useMemo } from 'react';
import UploadEmail from './upload_email';
import { Tab, Tabs, FormControl, InputGroup, Button, Container, Card, Row, Col } from 'react-bootstrap';
import axios from 'axios';
import Select from 'react-select';
import "react-draft-wysiwyg/dist/react-draft-wysiwyg.css";
import "../meet/datatable.css"
import EditorConvertToHTML from './meet_record';
import TrackingMatters from './tracking_matters';
import ElseDiscuss from './else_discuss';
import Complaint from './complaint'
import CustomerComplaint from './CustomerComplaint';
import History from './history';
import Participant from './participant';
import { Drawer, Tooltip } from 'antd';
import { BulbFilled } from '@ant-design/icons';
import MyEditor from './test';
class MainPage extends React.Component {
	constructor(props) {
		super(props);
		this.can = false;
		this.participant = []
		this.state = {
			datas: {
				labels: [],
				data: []
			},
			meet_id: 0,
			today: "",
			options: [
			],
			user_id: "",
			user_name: "",
			recorder_user_name: "",
			recorder_user_id: "",
			record_content: "",
			modify_time: "",
			modify_user_id: "",
			modify_user_name: "",
			mail_name: "",
			need_upload: false,
			selectedOptions: [],
			name: "",
			trackActiveKey: "tracking",
			uploadActiveKey: "email",
			factory_img: [],
			customer_img: [],
			show: false,
			isInvalid: false,
			select_style:
			{

			},
			participant: [],
			defaltParticipant: [],
			meet_date: "",
			save_success: false,
			text: `主旨、與會人為必填 新增完才可上傳檔案`,

		}
		this.modalChild = React.createRef();
		this.chertChild = React.createRef();
		this.meetingChild = React.createRef();
		this.canvasRef = React.createRef(null);
		this.customerRef = React.createRef();
		this.factoryRef = React.createRef();
		this.historyRef = React.createRef();
		this.selectRef = React.createRef();
		this.trackingRef = React.createRef(null);
		this.mailRef = React.createRef(null);
		this.changeDatas = this.changeDatas.bind(this);
		this.changeMeetSearch = this.changeMeetSearch.bind(this);
		this.getAllUser = this.getAllUser.bind(this);
		this.getRecorder = this.getRecorder.bind(this);
		this.sendTextToEditor = this.sendTextToEditor.bind(this);
		this.addMeet = this.addMeet.bind(this);
		this.saveMeet = this.saveMeet.bind(this);
		this.changeName = this.changeName.bind(this);
		this.changeUpload = this.changeUpload.bind(this);
		this.getContent = this.getContent.bind(this);
		this.getMailName = this.getMailName.bind(this);
		this.onTabSelect = this.onTabSelect.bind(this);
		this.showHistory = this.showHistory.bind(this);
		this.getMeetInfo = this.getMeetInfo.bind(this);
		this.getParticipant = this.getParticipant.bind(this);
		this.chackSaveSuccess = this.chackSaveSuccess.bind(this);
		this.changeMeet = this.changeMeet.bind(this);
		this.changeMeetRecord= this.changeMeetRecord.bind(this);
	}
	componentDidMount() {
		// let meet_id = window.location.href.split('/')[window.location.href.split('/').length - 1]
		// if (meet_id!==0) {
		// 	this.setState({
		// 		meet_id: meet_id
		// 	})
		// }
	}
	componentDidUpdate(prevProps, prevState, snapshot) {
		let meet_id = parseInt(window.location.href.split('/')[window.location.href.split('/').length - 1])
		if (this.state.meet_id !== prevState.meet_id) {
			this.can = false
			if (this.state.meet_id !== 0) {
				this.getMeetInfo(this.state.meet_id)
			}
		}
		if (this.state.save_success !== prevState.save_success) {
			this.historyRef.current.saveSuccess(this.state.save_success)
		}
		if (meet_id !== 0 && this.state.meet_id === 0) {
			this.setState({
				meet_id: meet_id
			})
		}

	}
	componentWillMount() {
		const current = new Date();
		let dd = current.getDate();
		let mm = current.getMonth() + 1;
		let yyyy = current.getFullYear();
		if (dd < 10) {
			dd = "0" + dd
		}
		if (mm < 10) {
			mm = "0" + mm
		}
		let today = `${yyyy}/${mm}/${dd}`;
		this.setState({
			today: today,
			meet_date: today,
			modify_time: today
		});
		this.getAllUser()
		this.getRecorder()
	}
	getRecorder() {
		axios.get(`/CRM/user`)
			.then((response) => {
				let user = response.data[0]
				this.setState({
					user_id: user.id,
					user_name: user.name,
					modify_user_id: user.id,
					modify_user_name: user.name,
					recorder_user_name: user.name,
					recorder_user_id: user.id,
				});
			});
	}
	changeDatas(data) {
		this.setState({
			datas: {
				labels: data.labels,
				data: data.data
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
	changeImg(e) {
		let new_src = e.target.src
		this.canvasRef.current.changeImage(new_src)
	}
	getAllUser() {
		axios.get('/CRM/all_user')
			.then((response) => {
				let option = []
				response.data.map((value, key) => {
					option.push({ value: `${value["id"]}`, label: `${value["name"]}` })
				})
				this.setState({
					options: option
				})
			})
			.catch((error) => console.log(error))
	}
	sendTextToEditor(e) {
		this.trackingRef.current.sendTextToEditor(e)
	}
	getContent() {
		let record_content = this.trackingRef.current.state.editorState.getCurrentContent().getPlainText()
		this.setState({
			record_content: record_content
		})
		console.log(record_content)
		return record_content
	}
	getMailName() {
		let mail_name = this.mailRef.current.state.mail_name
		this.setState({
			mail_name: mail_name
		})
		return mail_name
	}
	addMeet() {
		let data = new Object
		let datas = new Object
		datas["name"] = this.state.name
		datas["meet_type_id"] = 1
		datas["meet_date"] = this.state.today
		datas["participant"] = this.state.participant
		datas["recorder_user_id"] = this.state.recorder_user_id
		data[0] = datas
		if (this.state.name != "" && datas["participant"] != "") {
			axios.post('/CRM/complaint/meet', data
			)
				.then((response) => {
					let meet_id = response.data["0"]["id"]
					this.setState({
						meet_id: meet_id,
						isInvalid: false,
						select_style: {

						}
					})
				})
				.catch((error) => console.log(error))
		}
		else {
			this.setState({
				isInvalid: true,
				select_style: {
					control: (base, state) => ({
						...base,
						borderColor: 'red',
					})
				}
			})

		}
	}
	saveMeet(attr) {
		// this.getMailName()
		let data = new Object
		data["meet_id"] = this.state.meet_id
		data["mail_name"] = this.state.mail_name
		data["recorder_user_id"] = this.state.recorder_user_id
		data["record_content"] = this.state.record_content
		data["participant"] = this.state.participant
		console.log(data)
		axios.patch('/CRM/sale_meet', data)
			.then((response) => {
				if (attr == "modal") {
					this.chackSaveSuccess(response.data.status, 1)
				}
			})
			.catch((error) => console.log(error))
		let save_data = new Object
		save_data['id'] = this.state.meet_id
		save_data['name'] = this.state.name
		save_data['recorder_user_id'] = this.state.recorder_user_id
		save_data['meet_date'] = this.state.meet_date
		save_data['meet_type_id'] = 1
		save_data['modify_user_id'] = this.state.user_id
		axios.patch('/CRM/record_meet', save_data)
			.then((response) => {
				if (attr == "modal") { }
				this.chackSaveSuccess(response.data.status, 2)
			})
			.catch((error) => console.log(error))
	}
	chackSaveSuccess(response, time) {
		if (response === "success") {
			if (time == 1 && this.state.save_success == false) {
				this.setState({
					save_success: true
				})
			}
		}
		else {
			this.setState({
				save_success: false
			})
		}
	}
	changeName(e) {
		this.setState({
			name: e.target.value,
			save_success: false
		})
	}
	changeUpload() {
		this.setState({
			need_upload: true,
			save_success: false
		})
	}
	onTabSelect(index) {
		if (index == "tracking" || index == "complete") {
			this.setState({
				trackActiveKey: index
			})
		}
		else {
			this.setState({
				uploadActiveKey: index
			})
			if (index == "factory") {
				this.factoryRef.current.setActiveKey(index)
			}
			else if (index == "customer") {
				this.customerRef.current.setActiveKey(index)
			}
		}
	};
	getMeetInfo(meet_id) {
		let data = new Object
		data["meet_id"] = meet_id
		if (meet_id !== 0) {
			this.setState({
				save_success: true
			})
		}
		axios.get('/CRM/sale_meet', {
			params: {
				meet_id: meet_id
			}
		})
			.then((response) => {
				let datas = response.data[0]
				console.log(datas)
				this.setState({
					recorder_user_name: datas.recorder_name || " ",
					recorder_user_id: datas.recorder_user_id || " ",
					record_content: datas.meet_record || " ",
					name: datas.meet_name || "",
					meet_date: datas.meet_date || this.state.meet_date,
					modify_time: datas.modify_time || this.state.today,
					modify_user_id: datas.modify_user_id || this.user_id,
					modify_user_name: datas.modify_user_name || this.user_name,
					factory_img: datas.factory_img || [],
					customer_img: datas.customer_img || [],
					participant: datas.participant || [],
					defaltParticipant: datas.participant || []
				})
				if (datas.meet_record !== null) {
					this.trackingRef.current.changeText(datas.meet_record)
				}
				else{
					this.trackingRef.current.changeText("")
				}

			})
			.catch((error) => console.log(error))
	}
	showHistory() {
		this.historyRef.current.handleShow();
	}
	getParticipant(value) {
		this.setState({
			participant: value
		})
	}
	changeMeet(meet_id) {
		this.setState({
			meet_id: meet_id
		})
	}
	changeMeetRecord(text){
		this.setState({
			record_content: text,
			save_success: false
		})
	}
	showFrequentModal(){

	}
	render() {
		return (
			<Container fluid className="my-3">
				<Row className="my-3">
					<History addMeet={this.addMeet} meet_id={this.state.meet_id} changeMeet={this.changeMeet} save_success={this.state.save_success} saveMeet={this.saveMeet} show={false} ref={this.historyRef} />
				</Row >
				<Row className="my-3">
					<Col md="12">
						<Card>
							<Row>
								<Col md="auto">
									<Card.Title md='12' as="h3" className='mb-3'>
										<span className="badge rounded rfid_title p-3 text-center ">會議系統</span>
									</Card.Title>
								</Col>
								<Col>
									<Tooltip className='my-2' placement="rightTop" title={this.state.text}>
										<BulbFilled className='my-2 bulb' />
									</Tooltip>
								</Col>
							</Row>
							<Card.Body>
								<Row className='mb-3'>
									<Col md="3">
										<InputGroup className="mb-3">
											<InputGroup.Text id="modify_time">
												修改日期
											</InputGroup.Text>
											<FormControl id="modify_time" aria-describedby="modify_time" value={this.state.modify_time} disabled={true} />
										</InputGroup>
									</Col>
									<Col md="3">
										<InputGroup className="mb-3">
											<InputGroup.Text id="modify_user_name">
												修改人
											</InputGroup.Text>
											<FormControl aria-describedby="modify_user_name" value={this.state.modify_user_name} disabled={true} />
										</InputGroup>
									</Col>
									<Col md="3">
										<InputGroup className="mb-3">
											<InputGroup.Text id="date_id">
												建立日期
											</InputGroup.Text>
											<FormControl id="date_name" aria-describedby="date_id" value={this.state.meet_date} disabled />
										</InputGroup>
									</Col>
									<Col md="3">
										<InputGroup className="mb-3">
											<InputGroup.Text id="recorder_id">
												建立人
											</InputGroup.Text>
											<FormControl id="recorder_name" aria-describedby="recorder_id" value={this.state.recorder_user_name} disabled />
										</InputGroup>
									</Col>

								</Row>
								<Row className='d-flex justify-content-end my-3'>
									<Col md="auto">
										{this.state.meet_id !== 0 ? <Button variant="outline-primary" onClick={this.changeUpload} disabled={this.state.need_upload}>上傳檔案</Button> : <Button variant="outline-primary" onClick={this.addMeet}>儲存</Button>}
									</Col>
									<Col md="auto">
										{this.state.meet_id !== 0 ? <Button variant="outline-success" onClick={this.saveMeet} >保存</Button> : null}
									</Col>
									<Col md="auto">
										<Button variant="outline-success" onClick={this.showHistory} >歷史會議</Button>
									</Col>
								</Row>

								<Row className='mb-3'>
									<Col md="4">
										<InputGroup className="mb-3" >
											<InputGroup.Text id="main_content">
												會議主旨
											</InputGroup.Text>
											<FormControl aria-describedby="main_content" isInvalid={this.state.isInvalid} value={this.state.name} onChange={this.changeName} />
										</InputGroup>
									</Col>
									<Col md="8">
										<Participant defaltParticipant={this.state.defaltParticipant} meet_id={this.state.meet_id} getParticipant={this.getParticipant} />
									</Col>
								</Row>

							</Card.Body>
						</Card>
					</Col>
				</Row>
				{
					this.state.need_upload ?
						<Row className="my-3">
							<Col md="12">
								<Card>
									<Row>
										<Col md="auto">
											<Card.Title md='12' as="h3" className='mb-3'>
												<span className="badge rounded rfid_title p-3 text-center ">劃記圖片</span>
											</Card.Title>
										</Col>
									</Row>
									<Card.Body className='shadow'>
										<Tabs
											defaultActiveKey={this.state.uploadActiveKey}
											transition={false}
											className="mb-3"
											activeKey={this.state.uploadActiveKey}
											onSelect={this.onTabSelect}
										>
											<Tab eventKey="email" title="上傳Email">
												<UploadEmail ref={this.mailRef} />

											</Tab>
											<Tab eventKey="factory" title="廠內圖">
												<CustomerComplaint id="factory" ref={this.factoryRef} activeKey={this.state.uploadActiveKey} image={this.state.factory_img} meet_id={this.state.meet_id} />
											</Tab>
											<Tab eventKey="customer" title="客戶圖" >
												<CustomerComplaint id="customer" ref={this.customerRef} activeKey={this.state.uploadActiveKey} image={this.state.customer_img} meet_id={this.state.meet_id} />
											</Tab>
										</Tabs>

									</Card.Body>
								</Card>
							</Col>
						</Row> : null
				}
				<Row className="my-3">
					<Col md="12">
						<Card className='shadow'>
							<Row>
								<Col md="auto">
									<Card.Title md='12' as="h3" className='mb-3'>
										<span className="badge rounded rfid_title p-3 text-center ">客訴內容</span>
									</Card.Title>
								</Col>
								<Col>
									<Tooltip className='my-2' placement="rightTop" title="今日客訴內容">
										<BulbFilled className='my-2 bulb' />
									</Tooltip>
								</Col>
							</Row>
							<Card.Body>
								<Container fluid="xxl" >
									<Complaint meet_id={this.state.meet_id} />
								</Container>
							</Card.Body>
						</Card>
					</Col>
				</Row>
				<Row className="my-3">
					<Col md="6">
						<Card className='shadow'>
							<Row>
								<Col md="auto">
									<Card.Title md='12' as="h3" className='mb-3'>
										<span className="badge rounded rfid_title p-3 text-center ">追蹤事項</span>
									</Card.Title>
								</Col>
								<Col>
									<Tooltip className='my-2' placement="rightTop" title="歷史追蹤事項">
										<BulbFilled className='my-2 bulb' />
									</Tooltip>
								</Col>
							</Row>
							<Card.Body>
								<Tabs
									defaultActiveKey="tracking"
									transition={false}
									className="mb-3"
									activeKey={this.state.trackActiveKey}
									onSelect={this.onTabSelect}
								>
									<Tab eventKey="tracking" title="追蹤中">
										{
											this.state.trackActiveKey == "tracking" ? <TrackingMatters complete={false} sendTextToEditor={this.sendTextToEditor} /> : null
										}
									</Tab>
									<Tab eventKey="complete" title="完成追蹤">
										{
											this.state.trackActiveKey == "complete" ? <TrackingMatters complete={true} sendTextToEditor={this.sendTextToEditor} /> : null
										}
									</Tab>

								</Tabs>
							</Card.Body>
						</Card>
					</Col>
					<Col md="6">
						<Card className='shadow'>
							<Row>
								<Col md="auto">
									<Card.Title md='12' as="h3" className='mb-3'>
										<span className="badge rounded rfid_title p-3 text-center ">其他討論事項</span>
									</Card.Title>
								</Col>
								<Col>
									<Tooltip className='my-2' placement="rightTop" title="其他部門討論事項">
										<BulbFilled className='my-2 bulb' />
									</Tooltip>
								</Col>
							</Row>
							<Card.Body>
								<ElseDiscuss sendTextToEditor={this.sendTextToEditor} />
							</Card.Body>
						</Card>
					</Col>
				</Row>
				{this.state.meet_id ?
					<Row>
						<Col md="12">
							<Card className='shadow'>
								<Row>
									<Col md="auto">
										<Card.Title md='12' as="h3" className='mb-3'>
											<span className="badge rounded rfid_title p-3 text-center ">會議紀錄</span>
										</Card.Title>
									</Col>
								</Row>
								<Card.Body>
									<EditorConvertToHTML meet_id = {this.state.meet_id} record_content={this.state.record_content} changeMeetRecord={this.changeMeetRecord} ref={this.trackingRef} />
								</Card.Body>
							</Card>
						</Col>
					</Row> : null}
				
			</Container>
		);
	}
}
export default MainPage;