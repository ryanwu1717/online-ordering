import { Card, Row, Col,Tab,Tabs, Button, InputGroup, FormControl, Accordion, Container, Form } from "react-bootstrap";
import 'bootstrap/dist/css/bootstrap.min.css';
import React from "react";
import Datatable from "../components/Datatable";
import AccordionShowProcess from "../components/AccordionShowProcess";
import axios from 'axios';
import { CSVLink } from 'react-csv';
import CardProgess from "../components/CardProgess";
import './RFIDOverview.css'
import { Chart, registerables } from 'chart.js';
import BasicModal from '../components/BasicModal';
import MapLocation from '../components/MapLocation';
import * as FileSaver from "file-saver";
import * as XLSX from "xlsx";

Chart.register(...registerables);

class RFIDOverview extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			chart_labels: [], chart_data: [], backgroundColor: [], borderColor: [],
			datatables_range: {
				require: {
					start: new Date().getFullYear() + "-" + this.addz(((new Date().getMonth() + 1).toString()), 2) + "-" + this.addz((new Date().getDate().toString()), 2),
					end: new Date().getFullYear() + "-" + this.addz(((new Date().getMonth() + 1).toString()), 2) + "-" + this.addz((new Date().getDate().toString()), 2),
				},
				thead: [
					{
						name: '項目',
						cell: row => row.id,
						width: '120px',
						center: true,
					},
					{
						name: '製令 #',
						cell: row => row.order_processes_id,
						width: '100px',
						center: true,
					},
					{
						name: '產品類別',
						cell: row => row.production_name,
						width: '150px',
						center: true,
					},
					{
						name: '線別',
						cell: row => row.line_name,
						width: '150px',
						center: true,
					},
					{
						name: '站別',
						cell: row => row.processes_name,
						width: '150px',
						center: true,
					},
					{
						name: '預計生產數量',
						cell: row => row.preset_count,
						width: '150px',
						center: true,
					},
					{
						name: '機台號碼 #',
						cell: row => row.machine_id,
						width: '150px',
						center: true,
					},
					{
						name: '工時',
						cell: row => row.work_time,
						width: '100px',
						center: true,
					},
					{
						name: '進站時間',
						cell: row => row.inbound_time,
						width: '160px',
						center: true,
					},
					{
						name: '上機時間',
						cell: row => row.board_time,
						width: '160px',
						center: true,
					},
					{
						name: '出站時間',
						cell: row => row.out_bound_time,
						width: '160px',
						center: true,
					},
					{
						name: '預計時間',
						cell: row => row.preset_time,
						width: '160px',
						center: true,
					},
					{
						name: '機台狀況',
						cell: row => row.machine_condition,
						width: '150px',
						center: true,
					},
					{
						name: '圖面',
						cell: row => <Button variant="light" file_id={row.img} style={{ width: 'auto', background: "#5B648B", color: "white", border: "none" }} onClick={e => this.imgHandler(e)}>▶</Button>,
						width: '80px',
						center: true,
					},
				]
			},
			datatables: {
				require: {
					date: "2022-02-22",
				},
				thead: [
					{
						name: '項目',
						cell: row => row.id,
						width: '120px',
						center: true,
					},
					{
						name: '製令 #',
						cell: row => row.order_processes_id,
						width: '100px',
						center: true,
					},
					{
						name: '產品類別',
						cell: row => row.production_name,
						width: '150px',
						center: true,
					},
					{
						name: '線別',
						cell: row => row.line_name,
						width: '150px',
						center: true,
					},
					{
						name: '站別',
						cell: row => row.processes_name,
						width: '150px',
						center: true,
					},
					{
						name: '預計生產數量',
						cell: row => row.preset_count,
						width: '150px',
						center: true,
					},
					{
						name: '機台號碼 #',
						cell: row => row.machine_id,
						width: '150px',
						center: true,
					},
					{
						name: '工時',
						cell: row => row.work_time,
						width: '100px',
						center: true,
					},
					{
						name: '進站時間',
						cell: row => row.inbound_time,
						width: '160px',
						center: true,
					},
					{
						name: '上機時間',
						cell: row => row.board_time,
						width: '160px',
						center: true,
					},
					{
						name: '出站時間',
						cell: row => row.out_bound_time,
						width: '160px',
						center: true,
					},
					{
						name: '預計時間',
						cell: row => row.preset_time,
						width: '160px',
						center: true,
					},
					{
						name: '機台狀況',
						cell: row => row.machine_condition,
						width: '150px',
						center: true,
					},
					{
						name: '圖面',
						cell: row => <Button variant="light" file_id={row.img} style={{ width: 'auto', background: "#5B648B", color: "white", border: "none" }} onClick={e => this.imgHandler(e)}>▶</Button>,
						width: '80px',
						center: true,
					},
				]
			},
			csvData_order: ['order_id', 'processes_name', 'production_name', 'line_name', 'processes_name', 'preset_count', 'machine_id', 'work_time', 'inbound_time', 'board_time', 'out_bound_time', 'preset_time', 'machine_condition',],
			data: [],
			processes_id_show: ['005 ', '202 ', '006 ', '045 ', '058 ', '064 ', '076 ', '079 ', '203 ', '204 '],
			processes_count_id: ['005 ', '202 ', '006 ', '045 ', '058 ', '064 ', '076 ', '079 ', '203 ', '204 '],
			processes_name_show: ['剝皮', '剝皮', '鑽孔', '內孔角度', '外徑精車', '切槽溝', '傳統長度', '端面切(車)溝', '鑽孔', '鉸孔'],
			processes_count: ['剝皮', '剝皮', '鑽孔', '內孔角度', '外徑精車', '切槽溝', '傳統長度', '端面切(車)溝', '鑽孔', '鉸孔'],
			myChart: new Chart(document.getElementById('myChart'), {
				type: 'bar',
				data: {
					labels: [],
					datasets: [{
						label: '各站未來一週預計到站產量',
						data: [],
						backgroundColor: [],
						borderColor: [],
						borderWidth: 1
					}]
				},
				options: {
					scales: {
						y: {
							beginAtZero: true
						}
					}
				}
			}),
			processes_data_id: [],
			processes_data: [
				{ "processes_id": "005 ", "processes_name": "剝皮", "predict_sum": 50, },
				{ "processes_id": "202 ", "processes_name": "剝皮", "predict_sum": 42, },
				{ "processes_id": "006 ", "processes_name": "鑽孔", "predict_sum": 73, },
				{ "processes_id": "045 ", "processes_name": "內孔角度", "predict_sum": 94, },
				{ "processes_id": "058 ", "processes_name": "外徑精車", "predict_sum": 31, },
				{ "processes_id": "064 ", "processes_name": "切槽溝", "predict_sum": 46, },
				{ "processes_id": "076 ", "processes_name": "傳統長度", "predict_sum": 83, },
				{ "processes_id": "079 ", "processes_name": "端面切(車)溝", "predict_sum": 81, },
				{ "processes_id": "203 ", "processes_name": "鑽孔", "predict_sum": 21, },
				{ "processes_id": "204 ", "processes_name": "鉸孔", "predict_sum": 70, },
				{ "processes_id": "021 ", "processes_name": "銑床加工", "predict_sum": 36, },
				{ "processes_id": "022 ", "processes_name": "平磨", "predict_sum": 64, },
				{ "processes_id": "023 ", "processes_name": "傳統修面", "predict_sum": 66, },
				{ "processes_id": "024 ", "processes_name": "CNC精車", "predict_sum": 71, },
				{ "processes_id": "025 ", "processes_name": "平面", "predict_sum": 90, },
				{ "processes_id": "220 ", "processes_name": "車床 切斷", "predict_sum": 22, },
				{ "processes_id": "221 ", "processes_name": "倒角(R)", "predict_sum": 48, },
				{ "processes_id": "219 ", "processes_name": "傳統壓花", "predict_sum": 39, },
			],
			unfinished_today: [
				{
					id: "1",
					order_processes_id: "5123-1101217013",
					production_name: "前沖棒",
					line_name: "",
					processes_name: "CNC車床",
					preset_count: 20,
					machine_id: "",
					work_time: "",
					inbound_time: "",
					out_bound_time: "",
					preset_time: "",
					machine_condition: "",
					img: ""
				},
			],
			unfinished_history: [
				{
					id: "1",
					order_processes_id: "5123-1101217013",
					production_name: "前沖棒",
					line_name: "",
					processes_name: "CNC車床",
					preset_count: 20,
					machine_id: "",
					work_time: "",
					inbound_time: "",
					out_bound_time: "",
					preset_time: "",
					machine_condition: "",
					img: ""
				},
			],
			csvDataHeader:
				[
					{ label: "項目", key: "id" },
					{ label: "製令 #", key: "order_processes_id" },
					{ label: "產品類別", key: "production_name" },
					{ label: "線別", key: "line_name" },
					{ label: "站別", key: "processes_name" },
					{ label: "預計生產數量", key: "preset_count" },
					{ label: "機台號碼", key: "machine_id" },
					{ label: "工時", key: "work_time" },
					{ label: "進站時間", key: "inbound_time" },
					{ label: "出站時間", key: "out_bound_time" },
					{ label: "預計時間", key: "preset_time" },
					{ label: "機台狀況", key: "machine_condition" },
					{ label: "圖面", key: "img" },
				],
			progressbar_data: [
				{
					total: '',
					percentage: '',
					unfinished: '',
					waiting: {
						count: '',
						percentage: '',
					},
					processing: {
						count: '',
						percentage: ''
					},
					defect: {
						count: '',
						percentage: ''
					},
					abnormal: {
						count: ''
					}

				}
			],
			error: [],
			unfinished_data: [],
			image: '',
			// date: new Date().getFullYear() + "-" + this.addz(("0" + (new Date().getMonth() + 1).toString()), 2) + "-" +this.addz(("0" + (new Date().getDate() + 1).toString()), 2),
			date: "2022-02-21",
			date_start: new Date().getFullYear() + "-" + this.addz(("0" + (new Date().getMonth() + 1).toString()), 2) + "-" + this.addz(("0" + (new Date().getDate() + 1).toString()), 2),
			date_end: new Date().getFullYear() + "-" + this.addz(("0" + (new Date().getMonth() + 1).toString()), 2) + "-" + this.addz(("0" + (new Date().getDate() + 1).toString()), 2),
			modal: {
				show: false,
				modal_body: '',
				modal_footer: '',
			},
			img_path: '',
			selectedFloor: this.props.selectedFloor,
			editMode: this.props.editMode,
			dataTable_requires: {
				mapLocation: "",
			}
		}
		this.child_Search = React.createRef();
		this.child_date = React.createRef();
		this.child = React.createRef();
		this.csvHistoryRef = React.createRef();
		this.csvTodayRef = React.createRef();
		this.mapLocationRef = React.createRef();
		this.refreshBarChart = this.refreshBarChart.bind(this);
		this.postProcess = this.postProcess.bind(this);
		this.createCsvDataToday = this.createCsvDataToday.bind(this);
		this.createCsvDataHistory = this.createCsvDataHistory.bind(this);
		this.mapLocationHandler = this.mapLocationHandler.bind(this);
		this.handleMachine = this.handleMachine.bind(this);
		
	}

	imgHandler = (e) => {
		let client_name = e.target.attributes.file_id.value
		this.setState({ image: [] });
		axios
			.get(`${this.state.img_path}${e.target.attributes.file_id.value}`, { responseType: "blob" })
			.then(response => {
				var reader = new window.FileReader();
				reader.readAsDataURL(response.data);
				reader.onload = (e) => {
					var imageDataUrl = reader.result;
					let images = this.state.image;
					images.push(imageDataUrl);
					this.setState({ image: images });
					this.setState({
						modal: {
							modal_body:
								<Card className="bg-dark text-white">
									<Card.Img src={this.state.image[0]} alt="Card image" />
								</Card>,
							modal_footer: "",
							show: true,
						}
					});
					this.child.current.openModal();
				};
			})
			.catch(function (error) {
				console.log(error);
			});
	}

	dateChange = (e) => {
		let pre = this.state.datatables_range.require;
		pre[e.target.id] = e.target.value;
		let thead = this.state.datatables_range.thead
		this.setState({
			datatables_range: {
				require: pre,
				thead: thead
			}
		})
		this.child_Search.current.fetchUsers();
	}

	refreshBarChart() {
		this.setState({ data: [] })
		this.setState({ chart_labels: [] })
		this.setState({ chart_data: [] })

		this.state.processes_id_show.map((value, index) => (
			this.state.processes_data_id.indexOf(value.trim()) !== -1 ? this.state.data.push({ label: this.state.processes_name_show[index], data: this.state.processes_data[this.state.processes_data_id.indexOf(value.trim())]['predict_sum'] }) : null
		))
		this.state.data.map((item) => {
			this.state.chart_labels.push(item.label)
			this.state.chart_data.push(item.data)
			let backgroundColor1 = Math.floor(Math.random() * 150);
			let backgroundColor2 = Math.floor(Math.random() * 150);
			let backgroundColor3 = Math.floor(Math.random() * 150);
			this.state.backgroundColor.push(`rgba(${backgroundColor1}, ${backgroundColor2}, ${backgroundColor3}, 0.2)`)
			this.state.borderColor.push(`rgba(${backgroundColor1}, ${backgroundColor2}, ${backgroundColor3}, 1)`)
		})

		var ctx = document.getElementById('myChart');
		this.state.myChart.destroy();
		var myChart = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: this.state.chart_labels,
				datasets: [{
					label: '各站未來一週預計到站產量',
					data: this.state.chart_data,
					backgroundColor: this.state.backgroundColor,
					borderColor: this.state.borderColor,
					borderWidth: 1
				}]
			},
			options: {
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		});
		this.setState({
			myChart: myChart
		})
	}

	postProcess(response) {
		this.setState({ img_path: response.data.src });
		return response;
	}

	mapLocationHandler(response) {
		this.setState(prevState => ({
			datatables: {
				...prevState.datatables,
				require: {
					machine_id: response,
				}
			}
		}))
		this.child_date.current.fetchUsers();
	}

	addz(num, length) {
		if (num.length >= length) { return num }
		else {
			return this.addz(("0" + num), length)
		}
	}

	createCsvDataHistory = (e) => {
		let params = {};

		params["size"] = -1;
		params["cur_page"] = 1;
		params['start'] = this.state.datatables_range.require.start;
		params['end'] = this.state.datatables_range.require.end;
		axios
			.get(`/RFID/orderUnfinished`, {
				params: params,
			})
			.then((response) => {
				// add serial id
				response.data.data.map((value, index) => (
					value["id"] = index + 1
				))

				this.setState({ unfinished_history: response.data.data });
				let res_data = [];
				response.data.data.map((value, index) => (
					res_data.push({
						"項目": value.id,
						"製令 #": value.order_processes_id,
						"產品類別": value.production_name,
						"線別": value.line_name,
						"站別": value.process_name,
						"預計生產數量": value.preset_count,
						"機台號碼": value.machine_id,
						"工時": value.work_time,
						"進站時間": value.inbound_time,
						"進站時間": value.board_time,
						"出站時間": value.out_bound_time,
						"預計時間": value.preset_time,
						"機台狀況": value.machine_condition,
					})
				))
				let ws = XLSX.utils.json_to_sheet(res_data);
				let wb = { Sheets: { data: ws }, SheetNames: ["data"] };
				let excelBuffer = XLSX.write(wb, { bookType: e.target.attributes.output.value, type: "array" });
				let data = new Blob([excelBuffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8" });
				FileSaver.saveAs(data, 'orderUnfinished.' + e.target.attributes.output.value);

			});
	}

	createCsvDataToday = (e) => {
		let params = {};

		params["size"] = -1;
		params["cur_page"] = 1;
		params['date'] = this.state.date;
		axios
			.get(`/RFID/orderUnfinishedDate`, {
				params: params,
			})
			.then((response) => {
				// add serial id
				response.data.data.map((value, index) => (
					value["id"] = index + 1
				))
				this.setState({ unfinished_today: response.data.data });

				let res_data = [];
				response.data.data.map((value, index) => (
					res_data.push({
						"項目": value.id,
						"製令 #": value.order_processes_id,
						"產品類別": value.production_name,
						"線別": value.line_name,
						"站別": value.process_name,
						"預計生產數量": value.preset_count,
						"機台號碼": value.machine_id,
						"工時": value.work_time,
						"進站時間": value.inbound_time,
						"進站時間": value.board_time,
						"出站時間": value.out_bound_time,
						"預計時間": value.preset_time,
						"機台狀況": value.machine_condition,
					})
				))

				let ws = XLSX.utils.json_to_sheet(res_data);
				let wb = { Sheets: { data: ws }, SheetNames: ["data"] };
				let excelBuffer = XLSX.write(wb, { bookType: e.target.attributes.output.value, type: "array" });
				let data = new Blob([excelBuffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8" });
				FileSaver.saveAs(data, 'orderUnfinished.' + e.target.attributes.output.value);

			});
	}

	handleMachine = (e) => {
		if(e === "machine") {
			this.mapLocationRef.current.updateCanvas()
		}
	}

	componentDidMount() {
		let params = {};
		params["size"] = -1;
		params["cur_page"] = 1;
		params["row_size"] = 5;
		params["date"] = this.state.date;
		axios
			.get('/RFID/overview', { params: params })
			.then(response => {
				this.setState({ progressbar_data: [response.data] });
			})
			.catch(function (error) {
				console.log(error);
			});

		// check data date : 2022/03/01
		axios
			.get('/RFID/predict', { params: { request_date: this.state.date } })
			.then(response => {
				// sql data
				// this.setState({ processes_data: response.data });
				// response.data.map((value, index) => (
				//   this.state.processes_data_id.push(value.processes_id)
				// ))
			})
			.catch(function (error) {
				console.log(error);
			});

		// fake data
		this.state.processes_data.map((value, index) => (
			this.state.processes_data_id.push(value.processes_id.trim())
		))

		// check data date : 2022/02/21
		this.refreshBarChart()
		// this.refreshBarChart()
	}

	render() {
		return (
			<Container fluid>
				<BasicModal
					modal_title="圖面"
					modal_body={this.state.modal.modal_body}
					modal_footer={this.state.modal.modal_footer}
					show={this.state.modal.show}
					ref={this.child}
				></BasicModal>
				<Tabs defaultActiveKey="rfid" id="uncontrolled-tab-example" className="mb-3"  onSelect={this.handleMachine}>
					<Tab eventKey="rfid" title="RFID總覽">
						<Card className="my-2">
							<Card.Body>
								<Row>
									<Col md="6">
										<Row>
											<Col>
												<AccordionShowProcess aria-expanded="true" refreshBarChart={this.refreshBarChart} processes_id_show={this.state.processes_id_show} processes_name_show={this.state.processes_name_show} />
											</Col>
										</Row>
										<Row>
											<Col>
												<canvas id="myChart"></canvas>
											</Col>
										</Row>
									</Col>
									<Col className="error" md="6">
										<CardProgess data={this.state.progressbar_data} error={this.state.error} />
									</Col>
								</Row>
							</Card.Body>
						</Card>
					</Tab>
					<Tab eventKey="machine" title="機台總覽">
						<Row>
							<Col md="12">
								<Card className="my-2">
									<Card.Header style={{ color: "#545051", fontWeight: "bold" }}>廠內平面圖</Card.Header>
									<Card.Body>
										<MapLocation ref={this.mapLocationRef} mapLocationHandler={this.mapLocationHandler} selectedFloor={this.state.selectedFloor} editMode={this.state.editMode} />
									</Card.Body>
								</Card>
							</Col>
						</Row>
						<Row>
							<Col md="12">
								<Card className="my-2">
									<Card.Header style={{ color: "#545051", fontWeight: "bold" }}>今日製令單</Card.Header>
									<Card.Body>
										<Row xs="auto" className="my-2" >
											<Col>
												<Button className="mx-1 my-1" variant="light" onClick={this.createCsvDataToday} style={{ width: 'auto', background: "#839e88", color: "white", }}>CSV</Button>
											</Col>
											<Col md="auto">
												<Button output="xlsx" className="mx-1 my-1" variant="light" onClick={this.createCsvDataToday} style={{ width: 'auto', background: "#507958", color: "white", }}>XLSX</Button>
											</Col>
											<Col md="auto">
												<Button output="xls" className="mx-1 my-1" variant="light" onClick={this.createCsvDataToday} style={{ width: 'auto', background: "#135721", color: "white", }}>XLS</Button>
											</Col>
											<Col md="auto">
												<Row >
													<Col md="auto">
														<h5 className="mx-1 my-3">今日</h5>
													</Col>
													<Col md="auto">
														<Form.Select className="mx-1 my-1" aria-label="Default select example">
															<option value={null}>請選擇站別</option>
															{
																this.state.processes_count.map((value, index) => (
																	<option value={this.state.processes_count_id[index].trim()}>{value}</option>
																))
															}
														</Form.Select>
													</Col>
													<Col md="auto">
														<h5 className="mx-1 my-3">件</h5>
													</Col>
												</Row>
											</Col>
										</Row>
										<Datatable datatables={this.state.datatables} postProcess={this.postProcess} ref={this.child_date} api_location="/RFID/orderUnfinishedDate" />
									</Card.Body>
								</Card>
							</Col>
						</Row>
						<Row>
							<Col md="12">
								<Card className="my-2 history_table">
									<Card.Header style={{ color: "#545051", fontWeight: "bold" }}>歷史製令單</Card.Header>
									<Card.Body>
										<Row xs="auto" className="mb-3" >
											<Col md="auto">
												<Button output="csv" className="mx-1 my-1" variant="light" onClick={this.createCsvDataHistory} style={{ width: 'auto', background: "#839e88", color: "white", }}>CSV</Button>
											</Col>
											<Col md="auto">
												<Button output="xlsx" className="mx-1 my-1" variant="light" onClick={this.createCsvDataHistory} style={{ width: 'auto', background: "#507958", color: "white", }}>XLSX</Button>
											</Col>
											<Col md="auto">
												<Button output="xls" className="mx-1 my-1" variant="light" onClick={this.createCsvDataHistory} style={{ width: 'auto', background: "#135721", color: "white", }}>XLS</Button>
											</Col>
											<Col>
												<InputGroup className="my-1">
													<InputGroup.Text>起</InputGroup.Text>
													<FormControl
														type="date"
														value={this.state.datatables_range.require.start}
														id="start"
														onChange={this.dateChange.bind(this)}
													/>
												</InputGroup>
											</Col>
											<Col>
												<InputGroup className="my-1">
													<InputGroup.Text>迄</InputGroup.Text>
													<FormControl
														type="date"
														value={this.state.datatables_range.require.end}
														id="end"
														onChange={this.dateChange.bind(this)}
													/>
												</InputGroup>
											</Col>
										</Row>
										<Datatable datatables={this.state.datatables_range} postProcess={this.postProcess} ref={this.child_Search} api_location="/RFID/orderUnfinished" />
									</Card.Body>
								</Card>
							</Col>
						</Row>
					</Tab>
				</Tabs>


			</Container>

		)
	}
}

export default RFIDOverview
