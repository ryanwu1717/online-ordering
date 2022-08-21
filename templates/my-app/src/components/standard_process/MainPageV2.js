import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Container, InputGroup, Table, Card, Button, FloatingLabel, Col, Row, FormControl, Form, Accordion } from 'react-bootstrap';
import SortableComponentV2 from './SortableComponentV2';
import { arrayMoveImmutable } from 'array-move';
import BasicModal from '../BasicModal';
import Search from '../Search';
import Datatable from '../Datatable';
import axios from 'axios';
import 'antd/dist/antd.css';
import { Image, DatePicker, Drawer, Popconfirm } from 'antd';
import moment from 'moment';
import DataTable from "react-data-table-component";
import SortableInput from './SortableInput';
import './MainPageV2.css'

const { RangePicker } = DatePicker;

class MainPageV2 extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			all_processes: [],
			modal: {
				show: false,
				modal_body: '',
				modal_footer: '',
				modal_title: '',
				size: 'lg',
			},
			process_name: [],
			process_id: [],
			category: [
				{ name: '06-前沖棒+07-後沖棒-(WC)', category: ['06', '07'] },
				{ name: '03-模仁+05-INSERT(嵌入件)(WC)', category: ['03', '05'] },
				{ name: '08-通孔沖棒-(銲接MIL-TIP)', category: ['08'] },
				{ name: '02-模組(兩種以上鋼料)', category: ['02'] },
				{ name: '03-模仁(HSS-1整體鋼料)', category: ['03'] },
				{ name: '01-切刀', category: ['01'] },
				{ name: '04-模殼', category: ['04'] },
				{ name: '09-套管', category: ['09'] },
				{ name: '10-墊塊', category: ['12'] },
				{ name: '11-沖棒固定塊', category: ['12'] },
				{ name: '12-公牙', category: ['12'] },
				{ name: '13-夾子', category: ['12'] },
				{ name: '14-零件', category: ['12'] },
				{ name: '15-棘輪', category: ['12'] },
				{ name: '16-PIN', category: ['12'] },
				{ name: '17-通孔管', category: ['12'] },
				{ name: '18-其他', category: ['12'] },
			],
			categoryRef: [],
			categorySearch: [],
			Search: {
				Select_row: [
					{ 'label': '製程1:', 'id': 'process_1', 'type': 'input', 'value': '', 'disabled': false },
					{ 'label': '製程2:', 'id': 'process_2', 'type': 'input', 'value': '', 'disabled': false },
					{ 'label': '製程3:', 'id': 'process_3', 'type': 'input', 'value': '', 'disabled': false },
				],
				Select_row1: [
					{ 'label': '材質:', 'id': 'material', 'data_name': 'Select_row1', 'type': 'input', 'value': '', 'disabled': false },
					{ 'label': '鍍鈦:', 'id': 'ti', 'data_name': 'Select_row1', 'type': 'input', 'value': '', 'disabled': false },
				],
			},
			datatables_range: {
				require: {
				},
				thead: [
					{
						name: '',
						cell: row => <Col >
							<Row>

								<Image.PreviewGroup >
									{
										row.file_id.map((value, index) => (
											<Image style={{ border: "1px solid #a39e9e" }} width={200} src={`/3DConvert/PhaseGallery/order_image/${row.file_id[0]}`} />
										))
									}
								</Image.PreviewGroup>
							</Row>
						</Col>,
						center: true,
						width: '40%',

					},
					{
						name: '',
						cell: row =>
							<Col>
								<Row>
									<Col>
										<FloatingLabel label="品號" className="mb-2">
											<Form.Control
												autoComplete="off"
												type='input'
												value={row.number}
												disabled
											/>
										</FloatingLabel>
									</Col>
									<Col>
										<FloatingLabel label="材質" className="mb-2">
											<Form.Control
												autoComplete="off"
												type='input'
												value={row.material}
												disabled
											/>
										</FloatingLabel>
									</Col>
									<Col>
										<FloatingLabel label="鍍鈦" className="mb-2">
											<Form.Control
												autoComplete="off"
												type='input'
												value={row.ti}
												disabled
											/>
										</FloatingLabel>
									</Col>
								</Row>
								<Row>
									<Table responsive="sm">
										<thead>
											<tr>
												<th>順序</th>
												<th>製程代號</th>
												<th>製程名稱</th>
											</tr>
										</thead>
										<tbody>
											{
												row.processes.map((value, index) => (

													value.index !== null ?

														<tr>
															<td>{index + 1}</td>
															<td>{value.processes_id}</td>
															<td>{value.processes_name}</td>
														</tr> : null
												))
											}
										</tbody>
									</Table>
								</Row>
							</Col>,
						center: true,
						width: '60%'
					},
				]
			},
			date_start: (new Date().getFullYear() - 2) + "-" + (new Date().getMonth() + 1) + "-" + new Date().getDate(),
			date_end: new Date().getFullYear() + "-" + (new Date().getMonth() + 1) + "-" + new Date().getDate(),
			customCategory: "",
			searchProcess: [],
			searchProcessId: [],
			visible: false,
			custom_category_arr: [
				{ standard_processes_id: 1, custom_category: '類1', },
				{ standard_processes_id: 2, custom_category: '類2', },
			],
			customCategoryRef: [],
			currentCategory: {
				custom_category: '類1',

				processes: [
					{ index: 0, processes_id: '005', processes_name: 'xxx' },
					{ index: 1, processes_id: '006', processes_name: 'xxx2' },
					{ index: 2, processes_id: '007', processes_name: 'xxx3' },
				]
			},
			standard_processes_id: 0,
			old_standard_processes_id: "",
			edited: false,
			saveShow: false,
			isNew: false,
			first_click: true,
		}
		this.modalRef = React.createRef();
		this.searchRef = React.createRef(null);
		this.searchRef1 = React.createRef(null);
		this.searchRef2 = React.createRef(null);
		this.child_Search = React.createRef(null);
		this.searchBtnRef = React.createRef(null);
		this.addRef = React.createRef(null);
		this.subjectRef = React.createRef(null);
		this.warningRef = React.createRef(null);
		this.handleClickProcesses = this.handleClickProcesses.bind(this);
		this.handleProcessesCheck = this.handleProcessesCheck.bind(this);
		this.handleDelete = this.handleDelete.bind(this);
		this.handleDeleteAdvanced = this.handleDeleteAdvanced.bind(this);
		this.handleAddProcessOptions = this.handleAddProcessOptions.bind(this);
		this.handleEnlargeImg = this.handleEnlargeImg.bind(this);
		this.postProcess = this.postProcess.bind(this);
		this.handleChangeCategory = this.handleChangeCategory.bind(this);
		this.handleSelected = this.handleSelected.bind(this);
		this.handleSearch = this.handleSearch.bind(this);
		this.handleChangeDate = this.handleChangeDate.bind(this);
		this.handleSave = this.handleSave.bind(this);
		this.onSortInputEnd = this.onSortInputEnd.bind(this);
		this.handleCategorySelected = this.handleCategorySelected.bind(this);

	}

	componentDidMount() {
		let line_processes_temp = {};
		axios
			.get('/RFID/process/names')
			.then(response => {
				this.setState({ all_processes: response.data });
				let modal_body = '';
				response.data.map((value, index) => (
					value['製程'] !== null ?
						line_processes_temp[value['線別代號'].trim()] = {}
						: null
				))
				response.data.map((value, index) => (
					value['製程'] !== null ?
						value['製程'].map((process_value, process_index) => (
							line_processes_temp[value['線別代號'].trim()][process_value['processes_id']] = process_value['製程名稱'][0].trim()
						))
						: null
				))
				this.setState({ line_processes: line_processes_temp });
			})
			.catch(function (error) {
				console.log(error);
			});
		let ref_arr = []
		this.state.category.map((value, index) => (
			ref_arr.push(React.createRef())
		))
		this.setState({
			categoryRef: ref_arr,
		});
		axios
			.get('/develop/custom_processes/all', {
				params: {
					cur_page: 1,
					size: 0,
				},
			})
			.then(response => {
				let category_ref_arr = []
				let category_id = [];
				this.setState({ custom_category_arr: response.data })
				this.state.custom_category_arr.map((value, index) => {
					category_ref_arr.push(React.createRef())
					category_id.push(value.standard_processes_id)
				})
				this.setState({
					customCategoryRef: category_ref_arr,
				});
				let row = this;
				setTimeout(function () {
					row.setState({ visible: true });

					let standard_processes_id = window.location.href.split('/')[window.location.href.split('/').length - 1]
					console.log(category_id)
					standard_processes_id === '0' ? row.addRef.current.click() : row.state.customCategoryRef[category_id.indexOf(parseInt(standard_processes_id))].current.click()
					row.setState({ visible: false });

				}, 50);
			})
			.catch(function (error) {
				console.log(error);
			});

	}

	handleProcessesCheck = (e) => {
		this.setState({ edited: true })
		if (e.target.checked === true) {
			let process_name = this.state.process_name;
			process_name.push(e.target.attributes.process_name.value);
			this.setState({ process_name: process_name });

			let process_id = this.state.process_id;
			process_id.push(e.target.attributes.process_id.value);
			this.setState({ process_id: process_id });
		} else {
			let process_id = this.state.process_id;
			let process_idx = process_id.indexOf(e.target.attributes.process_id.value);
			process_id.splice(process_idx, 1);
			this.setState({ process_id: process_id });

			let process_name = this.state.process_name;
			process_name.splice(process_idx, 1);
			this.setState({ process_name: process_name });

		}
	};

	handleProcessesCheckAdvanced = (e) => {
		if (e.target.checked === true) {
			let searchProcess = [...this.state.searchProcess];
			searchProcess.push(e.target.attributes.process_name.value);
			this.setState({ searchProcess: searchProcess });

			let searchProcessId = [...this.state.searchProcessId];
			searchProcessId.push(e.target.attributes.process_id.value);
			this.setState({ searchProcessId: searchProcessId });
		} else {
			let searchProcessId = [...this.state.searchProcessId];
			let process_idx = searchProcessId.indexOf(e.target.attributes.process_id.value);
			searchProcessId.splice(process_idx, 1);
			this.setState({ searchProcessId: searchProcessId });

			let searchProcess = [...this.state.searchProcess];
			searchProcess.splice(process_idx, 1);
			this.setState({ searchProcess: searchProcess });

		}
	};

	handleClickProcesses = (e) => {
		this.setState({
			modal: {
				modal_body:
					<h5>
						<Row>
							{Object.entries(this.state.line_processes[e.target.attributes.line.value]).map(([key, item]) => {
								return (
									<Col md='3' className='my-1'>
										<Form.Group controlId={key}>
											{
												e.target.attributes.process_area.value === 'advanced' ?
													<Form.Check type="checkbox" onClick={event => this.handleProcessesCheckAdvanced(event)} label={item} name={e.target.attributes.line.value} process_id={key} process_name={item} />
													: <Form.Check type="checkbox" onClick={event => this.handleProcessesCheck(event)} label={item} name={e.target.attributes.line.value} process_id={key} process_name={item} />
											}

										</Form.Group>
									</Col>
								)
							})
							}
						</Row>
					</h5>,
				modal_title: e.target.attributes.line_name.value,
				modal_footer: <Button className="mx-2" onClick={this.modalRef.current.closeModal} variant="light" style={{ background: "#5e789f", color: "white", border: "none" }}>確定</Button>,
				show: true,
				size: 'lg'
			}
		});
		this.modalRef.current.openModal();

	}

	onSortEnd = ({ oldIndex, newIndex }) => {
		this.setState({ edited: true })
		this.setState(({ process_name, process_id }) => ({
			process_name: arrayMoveImmutable(process_name, oldIndex, newIndex),
			process_id: arrayMoveImmutable(process_id, oldIndex, newIndex),
		}));
	};
	onSortInputEnd = ({ oldIndex, newIndex }) => {
		this.setState(({ searchProcess, searchProcessId }) => ({
			searchProcess: arrayMoveImmutable(searchProcess, oldIndex, newIndex),
			searchProcessId: arrayMoveImmutable(searchProcessId, oldIndex, newIndex),
		}));
	};
	postProcess(response) {
		console.log(response.data)
		return response;
	}
	handleDelete = (e) => {
		this.setState({ edited: true })
		let idx = parseInt(e.target.attributes.idx.value);
		let process_name = this.state.process_name;
		process_name.splice(idx, 1);
		this.setState({ process_name: process_name });

		let process_id = this.state.process_id;
		process_id.splice(idx, 1);
		this.setState({ process_id: process_id });
	}
	handleDeleteAdvanced = (e) => {
		let idx = parseInt(e.target.attributes.idx.value);
		let process_name = [...this.state.searchProcess];
		process_name.splice(idx, 1);
		this.setState({ searchProcess: process_name });

		let process_id = [...this.state.searchProcessId];
		process_id.splice(idx, 1);
		this.setState({ searchProcessId: process_id });
	}
	handleAddProcessOptions = (e) => {
		let search = this.state.Search;
		let search_row1 = search.Select_row;
		search_row1.push({ 'label': `製程${search_row1.length + 1}:`, 'id': '', 'type': 'input', 'value': '', 'disabled': false },)
		search.Select_row = search_row1;
		this.setState({ Search: search })
	}
	handleEnlargeImg = (e) => {
		this.setState({
			modal: {
				modal_body:
					<>
						<img src="https://livingtechlearn.files.wordpress.com/2017/02/iphone-ed.png" alt="" />
					</>,
				modal_title: "圖片放大",
				modal_footer: <Button className="mx-2" onClick={this.modalRef.current.closeModal} variant="light" style={{ background: "#5e789f", color: "white", border: "none" }}>關閉</Button>,
				show: true,
				size: "xl"
			}
		});
		this.modalRef.current.openModal();
	}
	handleSelected = (e) => {
		this.state.category.map((value, index) => (
			Object.assign(this.state.categoryRef[index].current.style, { fontWeight: "bold", background: "white", color: "#6b778d", borderColor: "#6b778d", borderWidth: "medium" })
		))
		Object.assign(e.target.style, { background: "#6b778d", color: "white", borderColor: "#6b778d" });
		this.setState({ categorySearch: this.state.category[e.target.attributes.idx.value].category })

		let row = this
		setTimeout(function () {
			row.searchBtnRef.current.click()
		}, 50);
	}

	handleCategorySelected = (e, index, standard_processes_id, edited) => {
		if (this.state.customCategory === '' && !this.state.first_click) {
			Object.assign(this.subjectRef.current.style, { borderColor: "red" })
			Object.assign(this.warningRef.current.style, { display: "block" })
		} else {
			this.setState({
				standard_processes_id: standard_processes_id,
			})

			if (edited) {
				this.handleSave();
			} else {
				this.setState({
					old_standard_processes_id: standard_processes_id,
					edited: false,
					standard_processes_id: standard_processes_id,
					customCategory: "",
					process_id: [],
					process_name: [],
				})
			}

			if (!this.state.isNew) {
				axios
					.get('/develop/custom_processes/one', {
						params: { standard_processes_id: standard_processes_id },
					})
					.then(response => {
						let process_id = [];
						let process_name = [];
						response.data.processes.map((value, index) => {
							process_id.push(value.processes_id)
							process_name.push(value.processes_name)
						})
						this.setState({
							customCategory: response.data.custom_category,
							process_id: process_id,
							process_name: process_name
						})
					})
					.catch(function (error) {
						console.log(error);
					});
			} else {

				this.setState({
					standard_processes_id: standard_processes_id,
					customCategory: "",
					process_id: [],
					process_name: [],
					isNew: false,
				})
			}
		}

	}

	handleSearch = (e) => {
		let process_arr = this.state.searchProcessId
		let datatables_range = { ...this.state.datatables_range }
		datatables_range['require'] = {
			date_start: moment(new Date(this.state.date_start)).format('YYYY-MM-DD'),
			date_end: moment(new Date(this.state.date_end)).format('YYYY-MM-DD'),
			category: this.state.categorySearch,
			processes_id: process_arr,
			material: this.state.Search.Select_row1[0].value,
			ti: this.state.Search.Select_row1[1].value,
		}
		this.setState({ datatables_range: datatables_range })

		let row = this
		setTimeout(function () {
			row.child_Search.current.fetchUsers()
		}, 50);

	}
	handleChangeDate = (date, dateString) => {
		this.setState({
			date_start: dateString[0],
			date_end: dateString[1],
		})
	}
	handleSearchChange = (event) => {
		let select_row = { ...this.state.Search.Select_row };
		let select_row1 = { ...this.state.Search.Select_row1 };
		Object.entries(select_row).map(([key, value]) => (
			value.id === event.id ? value.value = event.value : null
		))
		Object.entries(select_row1).map(([key, value]) => (
			value.id === event.id ? value.value = event.value : null
		))
		let search = { ...this.state.Search }
		search['Select_row'] = select_row
		search['Select_row1'] = select_row1
		this.setState({
			Search: search,
		})
	}

	handleSave = (e) => {
		if (this.state.customCategory === '') {
			Object.assign(this.subjectRef.current.style, { borderColor: "red" })
			Object.assign(this.warningRef.current.style, { display: "block" })

		} else {
			this.setState({ first_click: false })
			Object.assign(this.subjectRef.current.style, { borderColor: "#ced4da" })
			Object.assign(this.warningRef.current.style, { display: "none" })
			let processes = [];
			this.state.process_id.map((value, index) => (
				processes.push({ index: index, processes_id: value })
			))
			let custom_category_arr = [...this.state.custom_category_arr]
			custom_category_arr.map((value, index) => (
				value.standard_processes_id === this.state.old_standard_processes_id ? value.custom_category = this.state.customCategory : null
			))
			this.setState({ custom_category_arr: custom_category_arr })
			let data = {};
			data['custom_category'] = this.state.customCategory;
			data['processes'] = processes;
			data['standard_processes_id'] = this.state.old_standard_processes_id;
			axios
				.post('/develop/standard_processes', data)
				.then(response => {
					this.setState({ edited: false })
					if (this.state.old_standard_processes_id === "") {
						let custom_category_arr = [...this.state.custom_category_arr]
						custom_category_arr.map((value, index) => (
							value.standard_processes_id === "" ? value.standard_processes_id = response.data.standard_processes_id : null
						))
						this.setState({
							custom_category_arr: custom_category_arr,
							old_standard_processes_id: response.data.standard_processes_id,
							standard_processes_id: response.data.standard_processes_id,
						})
					}
					this.setState({
						old_standard_processes_id: this.state.standard_processes_id
					})
					this.setState({ saveShow: true })
					let row = this
					setTimeout(function () {
						row.setState({ saveShow: false })
					}, 2000);
				})
				.catch((error) => console.log(error))
		}
	}

	handleChangeCategory = (e) => {
		this.setState({ customCategory: e.target.value })
		this.setState({ edited: true })
	}

	onClose = (e) => {
		this.setState({ visible: false })
	}

	openDrawer = (e) => {
		this.setState({ visible: true })
	}

	handleNewCategory = (e) => {
		if (this.state.standard_processes_id !== '') {

			let custom_category_arr = [...this.state.custom_category_arr]
			custom_category_arr.push({ standard_processes_id: '', custom_category: '[尚未保存類別]', },)

			let customCategoryRef = [...this.state.customCategoryRef];
			customCategoryRef.push(React.createRef())
			this.setState({
				custom_category_arr: custom_category_arr,
				customCategoryRef: customCategoryRef
			})
			if (this.state.edited) {
				this.setState({ visible: true })
			} else {
				this.setState({ visible: true })
				this.setState({ visible: false })
			}

			let row = this
			setTimeout(function () {
				row.state.customCategoryRef[customCategoryRef.length - 1].current.click()
			}, 50);

			this.setState({ isNew: true })
		}

	}

	render() {
		console.log(this.state.standard_processes_id)
		return (
			<Container fluid>
				<BasicModal
					modal_title={this.state.modal.modal_title}
					modal_body={this.state.modal.modal_body}
					modal_footer={this.state.modal.modal_footer}
					show={this.state.modal.show}
					size={this.state.modal.size}
					ref={this.modalRef}
					height={600}
				></BasicModal>
				<Drawer title="歷史類別" placement="right" onClose={this.onClose.bind(this)} visible={this.state.visible}>
					{this.state.custom_category_arr.map((value, index) => (
						!this.state.edited ?
							<Button className="mx-2 my-1" idx={index} standard_processes_id={value.standard_processes_id} onClick={e => this.handleCategorySelected(e, index, value.standard_processes_id, false)} ref={this.state.customCategoryRef[index]} variant="light" style={this.state.standard_processes_id === value.standard_processes_id ? { textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: "nowrap", width: '300px', maxWidth: '300px', height: "50px", background: "#6b778d", color: "white", borderColor: "#6b778d", fontSize: "18px", fontWeight: "bold", } : { textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: "nowrap", width: '300px', maxWidth: '300px', height: "50px", fontSize: "18px", fontWeight: "bold", background: "white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium" }}>{value.custom_category}</Button>
							:
							<Popconfirm
								title="您尚未儲存更改，請問是否需儲存變更?"
								onConfirm={e => this.handleCategorySelected(e, index, value.standard_processes_id, true)}
								onCancel={e => this.handleCategorySelected(e, index, value.standard_processes_id, false)}
								okText="是"
								cancelText="否">
								<Button className="mx-2 my-1" variant="light" ref={this.state.customCategoryRef[index]} style={this.state.standard_processes_id === value.standard_processes_id ? { textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: "nowrap", width: '300px', maxWidth: '300px', height: "50px", background: "#6b778d", color: "white", borderColor: "#6b778d", fontSize: "18px", fontWeight: "bold", } : { textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: "nowrap", width: '300px', maxWidth: '300px', height: "50px", fontSize: "18px", fontWeight: "bold", background: "white", color: "#5e789f", borderColor: "#5e789f", borderWidth: "medium" }}>{value.custom_category}</Button>
							</Popconfirm>
					))
					}
				</Drawer>
				<Row className="my-2">
					<Col md="12">
						<Card className='shadow'>
							<Card.Header>
								標準製程
							</Card.Header>
							<Card.Body>
								<Row>
									<Col md="7">
										<Row className='mb-3'>
											<Col md="7">
												<Row>
													<InputGroup>
														<InputGroup.Text>類別</InputGroup.Text>
														<FormControl
															aria-describedby="inputGroup-sizing-default"
															onChange={this.handleChangeCategory}
															value={this.state.customCategory}
															placeholder="必填*"
															ref={this.subjectRef}
														/>
													</InputGroup>
												</Row>
												<Row>
													<h6 className='ml-4 my-1' ref={this.warningRef} style={{ color: 'red', display: 'none' }}>此欄位必填*</h6>
												</Row>
											</Col>
											<Col md="auto">
												<Button className="" ref={this.addRef} variant="light" onClick={this.handleNewCategory.bind(this)} style={{ fontWeight: "bold", background: "#2470a0", color: "white", }}>新增</Button>
											</Col>
											<Col md="auto">
												<Button className="mx-1" onClick={this.handleSave} variant="light" style={{ fontWeight: "bold", background: "#ffbc00", color: "white", }}>儲存</Button>
											</Col>
											<Col md="auto">
												<Button className="" variant="light" onClick={this.openDrawer.bind(this)} style={{ fontWeight: "bold", background: "#67bbbd", color: "white", }}>歷史類別</Button>
											</Col>

										</Row>
										<Row>
											{this.state.all_processes.map((value, index) => (
												value['製程'] !== null ?
													<Button className="mx-1 my-1" variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.handleClickProcesses} process_area="standard" line={value['線別代號'].trim()} line_name={value['線別名稱']} >{value['線別名稱']}</Button>
													: null
											))}
										</Row>
										<Row>
											<label className='my-2' style={{ display: this.state.saveShow ? 'block' : 'none', fontWeight: 'bold', color: 'red' }}>已儲存</label>
										</Row>
									</Col>
									<Col md="5">
										<SortableComponentV2 process_id={this.state.process_id} process_name={this.state.process_name} handleDelete={this.handleDelete} onSortEnd={this.onSortEnd} />
									</Col>
								</Row>
							</Card.Body>
						</Card>
					</Col>
				</Row>
				<Row>
					<Col md="12">
						<Card className='shadow'>
							<Card.Header>
								製程參考
							</Card.Header>
							<Card.Body>
								<Row>
									<Col md="auto" className='my-2'>
										<h5>起迄日期：</h5>
									</Col>
									<Col md="auto">
										<RangePicker
											style={{ borderRadius: '2%' }}
											size='large'
											value={[this.state.date_start === '' ? null : moment(this.state.date_start, 'YYYY-MM-DD'), this.state.date_end === '' ? null : moment(this.state.date_end, 'YYYY-MM-DD')]}
											onChange={this.handleChangeDate}
											placeholder={['請選擇開始日期', '請選擇結束日期']}
											format='YYYY-MM-DD'
										/>
									</Col>
								</Row>
								<Row>
									{this.state.category.map((value, index) => (
										<Button className="mx-1 my-1" variant="light" idx={index} onClick={this.handleSelected} ref={this.state.categoryRef[index]} style={{ width: 'auto', background: "white", color: "#6b778d", borderColor: "#6b778d", fontWeight: "bold", borderWidth: "medium" }} >{value.name}</Button>
									))}
								</Row>
								<Row>
									<Col md="12">
										<Accordion className='my-2'>
											<Accordion.Item eventKey="0">
												<Accordion.Header>進階選項</Accordion.Header>
												<Accordion.Body>
													<Row>
														<h6 style={{ fontWeight: 'bold' }} >選擇製程：</h6>
													</Row>
													<Row className='mb-2'>
														{this.state.all_processes.map((value, index) => (
															value['製程'] !== null ?
																<Button className="mx-1 my-1" variant="light" style={{ width: 'auto', background: "white", color: "#7a6b97", borderColor: "#7a6b97", fontWeight: "bold", borderWidth: "medium" }} onClick={this.handleClickProcesses} process_area="advanced" line={value['線別代號'].trim()} line_name={value['線別名稱']} >{value['線別名稱']}</Button>
																: null
														))}

													</Row>
													<Row>
														<SortableInput axis="xy" search_process_id={this.state.searchProcessId} search_process_name={this.state.searchProcess} handleDelete={this.handleDeleteAdvanced} onSortEnd={this.onSortInputEnd} />
													</Row>
													<Row >
														<Search resetData={this.handleSearchChange.bind(this)} ref={this.searchRef1} name={this.state.Search.Select_row1}></Search>
														<Col md='auto'>
															<Button className="mx-2 my-2" variant="light" ref={this.searchBtnRef} onClick={this.handleSearch} style={{ background: "#6b778d", color: "white", fontWeight: "bold", }} >確定</Button>
														</Col>
													</Row>
												</Accordion.Body>
											</Accordion.Item>
										</Accordion>

									</Col>
								</Row>
								<Row>
									<Col className="mainPage" md="12">
										<Datatable datatables={this.state.datatables_range} postProcess={this.postProcess} ref={this.child_Search} api_location="/3DConvert/PhaseGallery/category_processes" />
									</Col>
								</Row>
							</Card.Body>
						</Card>

					</Col>
				</Row>
				<Row>
				</Row>
			</Container>
		);
	}
}

export default MainPageV2;