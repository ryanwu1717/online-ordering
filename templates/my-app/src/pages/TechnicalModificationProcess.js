// Demo.jsx
import React from 'react';
import axios from 'axios';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Button, Form, Row, Card, Col } from 'react-bootstrap';
import { arrayMoveImmutable } from 'array-move';
import TechnicalModificationProcessView from '../components/TechnicalModificationProcessView';
import BasicModalWithTable from '../components/BasicModalWithTable';
import SortableCard from '../components/SortableCard';
import BasicModal from '../components/BasicModal';
import Search from '../components/Search';
import ProcessGroup from '../components/ProcessGroup'
export default class TechnicalModificationProcess extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			order_id: 1, coptd_td001: '2210', coptd_td002: '2210', coptd_td003: '2210',
			processName: [], processId: [], show: false, post: [], processRow: [],
			page: 1, oldProcessName: [], oldProcessId: [], Process_API_location: 123,
			cardBody: ["", ""], API_location_processes: this.props.API_location_processes,
			editable: this.props.editable, children: [],
			coptd_td: { "coptd_td001": "", "coptd_td002": "", "coptd_td003": "", },
			Search: {
				Select_row1: [
					{ 'label': '單別:', 'id': 'coptd_td001', 'type': 'input', 'value': '' },
					{ 'label': '單號:', 'id': 'coptd_td002', 'type': 'input', 'value': '' },
					{ 'label': '序號:', 'id': 'coptd_td003', 'type': 'input', 'value': '' }
				],
			},
			datatables: {
				thead: [
					{
						name: '製程代號',
						cell: row => <Form.Label>{row.process_id}</Form.Label>,

					},
					{
						name: '製程名稱',
						cell: row => <Form.Label>{row.process_name}</Form.Label>,
					},
					{
						name: '選擇',
						cell: row => <input type="checkbox" name="processChecked" id={row.process_id.replace(" ", "")} onChange={e => this.categoryCheckboxFilterHandler(e)} style={{ width: "20px", height: "20px" }} process_id={row.process_id.replace(" ", "")} process_name={row.process_name} />,
					},
				]
			},
			all_processes: [],
			modal: {
				show: false,
				modal_body: '',
				modal_footer: '',
				modal_title: '',
			},
			modal_body: '',
			line_processes: {},
			line_row: [],
			line_name: [],
			line_body: [],
			process_name: [],
			process_id: [],
			group_process_id: [],
			group_process_name: [],
		};
		this.sendToParentfunction = this.sendToParentfunction.bind(this);
		this.handleClickProcesses = this.handleClickProcesses.bind(this);
		this.handleProcessesCheck = this.handleProcessesCheck.bind(this);

		this.handle_edit = this.handle_edit.bind(this);
		this.handle_delete = this.handle_delete.bind(this);
		this.onChange = this.onChange.bind(this);
		this.modalRef = React.createRef();
		this.child = React.createRef();
	}

	on_sort_processEnd = (e) => {
		let line_idx = e.nodes[e.oldIndex].node.attributes.line_idx.value;
		let line_body = this.state.line_body;
		let line_body_array_move = arrayMoveImmutable(line_body[line_idx], e.oldIndex, e.newIndex);
		line_body[line_idx] = line_body_array_move;
		this.setState({ line_body: line_body })

		let process_id = this.state.process_id;
		let process_id_array_move = arrayMoveImmutable(process_id[line_idx], e.oldIndex, e.newIndex);
		process_id[line_idx] = process_id_array_move;
		this.setState({ process_id: process_id })

		let process_name = this.state.process_name;
		let process_name_array_move = arrayMoveImmutable(process_name[line_idx], e.oldIndex, e.newIndex);
		process_name[line_idx] = process_name_array_move;
		this.setState({ process_name: process_name })
	};

	handleClose = () => { this.setState({ show: false }) }
	handleShow = () => {
		this.setState({ show: true })
		this.state.post.map((item) => {
			this.state.processRow.push({ process_id: item.process_id, process_name: item.process_name });
		})

		this.setState({ processId: this.state.processId })
		this.setState({ processName: this.state.processName })
	}

	categoryCheckboxFilterHandler = (e) => {
		if (e.target.checked === true) {
			this.state.processName.push(e.target.attributes.process_name.value);
			this.state.processId.push(e.target.attributes.process_id.value);

		} else {
			let process_name_check_list = [];
			let process_id_check_list = [];
			this.state.processName.map((item) => {
				if (item !== e.target.attributes.process_name.value) {
					process_name_check_list.push(item);
					process_id_check_list.push(e.target.attributes.process_id.value);
				}
				this.state.processRow.push({ process_id: item.process_id, process_name: item.process_name });
			})
			this.setState({ processName: process_name_check_list })
			this.setState({ processId: process_id_check_list })
		}
	};

	handleProcessesCheck = (e) => {
		if (e.target.checked === true) {
			let line_body = this.state.line_body;
			line_body[parseInt(e.target.attributes.line_row_idx.value)].push("");
			this.setState({ line_body: line_body });

			let process_name = this.state.process_name;
			process_name[parseInt(e.target.attributes.line_row_idx.value)].push(e.target.attributes.process_name.value);
			this.setState({ process_name: process_name });

			let process_id = this.state.process_id;
			process_id[parseInt(e.target.attributes.line_row_idx.value)].push(e.target.attributes.process_id.value);
			this.setState({ process_id: process_id });
		} else {
			let process_id = this.state.process_id;
			let process_id_per = process_id[parseInt(e.target.attributes.line_row_idx.value)];
			let process_idx = process_id_per.indexOf(e.target.attributes.process_id.value);
			process_id_per.splice(process_idx, 1);
			process_id[parseInt(e.target.attributes.line_row_idx.value)] = process_id_per;
			this.setState({ process_id: process_id });

			let line_body = this.state.line_body;
			let line_body_process = line_body[parseInt(e.target.attributes.line_row_idx.value)];
			line_body_process.splice(process_idx, 1);
			line_body[parseInt(e.target.attributes.line_row_idx.value)] = line_body_process;
			this.setState({ line_body: line_body });

			let process_name = this.state.process_name;
			let process_name_per = process_name[parseInt(e.target.attributes.line_row_idx.value)];
			process_name_per.splice(process_idx, 1);
			process_name[parseInt(e.target.attributes.line_row_idx.value)] = process_name_per;
			this.setState({ process_name: process_name });
		}
		let group_process_id = [];
		let group_process_name = [];
		this.state.process_id.map((row_value, row_index) => (
			row_value.map((value, index) => {
				group_process_id.push(value)
				group_process_name.push(group_process_name[row_index][index])
			})
		))
		this.setState({ group_process_id: group_process_id, group_process_name: group_process_name })
	};

	cancel = () => {
		this.setState({ show: false });
	}

	handleSearchChange = (event) => {

		let select_row = this.state.Search.Select_row1;
		select_row.map((value, index) => (
			value.id === event.id ? value.value = event.value : null
		))
		let search = {
			Select_row1: select_row
		}
		this.setState({
			Search: search,
		})
	}
	handleClickProcesses = (e) => {
		console.log(e.target.attributes)
		let line_row = this.state.line_row;
		line_row.push(e.target.attributes.line.value);
		this.setState({ line_row: line_row })

		let line_name = this.state.line_name;
		line_name.push(e.target.attributes.line_name.value);
		this.setState({ line_name: line_name })

		let line_body = this.state.line_body;
		line_body.push([]);
		this.setState({ line_body: line_body })

		let process_name = this.state.process_name;
		process_name.push([]);
		this.setState({ process_name: process_name })

		let process_id = this.state.process_id;
		process_id.push([]);
		this.setState({ process_id: process_id })

		this.setState({
			modal: {
				modal_body:
					<h5>
						<Row>
							{Object.entries(this.state.line_processes[e.target.attributes.line.value]).map(([key, item]) => {
								return (
									<Col md='3' className='my-1'>
										<Form.Group controlId={key}>
											<Form.Check type="checkbox" line_row_idx={this.state.line_row.length - 1} onClick={e => this.handleProcessesCheck(e)} label={item} name={e.target.attributes.line.value} process_id={key} process_name={item} />
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
			}
		});
		this.modalRef.current.openModal();
	}

	componentDidMount() {
		// axios
		// 	.get(this.state.API_location_processes)
		// 	.then(response => {
		// 		this.setState({ post: response.data });
		// 	})
		// 	.catch(function (error) {
		// 		console.log(error);
		// 	});


		/*  line_processes data
			{
				A: {
					001: '剝皮',
					006: '鑽孔',
				}
			}
		*/
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
							line_processes_temp[value['線別代號'].trim()][process_value['製程代號'][0].trim()] = process_value['製程名稱'][0].trim()
						))
						: null
				))
				this.setState({ line_processes: line_processes_temp });
			})
			.catch(function (error) {
				console.log(error);
			});
		axios
			.get("/3DConvert/PhaseGallery/order_processes/reprocess", { params: { order_id: this.state.order_id } })
			.then(response => {
				let line_row = []
				let line_name = []
				let line_body = []
				let process_name = []
				let process_id = []
				response.data.map((value, index) => {
					line_row.push(value.line_id.trim())
					line_name.push(value.line_name.trim())
					line_body.push([]);
					process_name.push([]);
					process_id.push([]);
					value.processes.map((processes_value, processes_index) => {
						line_body[line_body.length - 1].push(processes_value.note)
						process_name[process_name.length - 1].push(processes_value.processes_name)
						process_id[process_id.length - 1].push(processes_value.processes_id)
					})
				})
				this.setState({
					line_row: line_row,
					line_name: line_name,
					line_body: line_body,
					process_name: process_name,
					process_id: process_id
				})

			})
			.catch(function (error) {
				console.log(error);
			});
	}

	returnViewComponentSwitch() {
		this.child.current.getProcessData();
	}

	resetViewComponent(data) {
		this.child.current.resetProcessData(data);
	}

	onSortEnd = ({ oldIndex, newIndex }) => {
		this.setState(({ line_name, process_name, process_id, line_body, line_row }) => ({
			line_name: arrayMoveImmutable(line_name, oldIndex, newIndex),
			process_name: arrayMoveImmutable(process_name, oldIndex, newIndex),
			process_id: arrayMoveImmutable(process_id, oldIndex, newIndex),
			line_body: arrayMoveImmutable(line_body, oldIndex, newIndex),
			line_row: arrayMoveImmutable(line_row, oldIndex, newIndex),
		}));
		let group_process_id = [];
		let group_process_name = [];
		this.state.process_id.map((row_value, row_index) => (
			row_value.map((value, index) => {
				group_process_id.push(value)
				group_process_name.push(group_process_name[row_index][index])
			})
		))
		this.setState({ group_process_id: group_process_id, group_process_name: group_process_name })
	};
	onChange = (e) => {
		let options = this.state.line_body;
		options[parseInt(e.target.attributes.line_idx.value)][parseInt(e.target.attributes.idx.value)] = e.target.value;
		this.setState({ line_body: options });
		console.log(this.state.line_body)
	}
	saveProcess = () => {
		var data = {};
		data['fk'] = {};
		data['fk']['coptd_td001'] = this.state.coptd_td001;
		data['fk']['coptd_td002'] = this.state.coptd_td002;
		data['fk']['coptd_td003'] = this.state.coptd_td003;
		var process_arr = [];
		console.log(this.state.process_name)
		let idx = -1;
		this.state.process_id.map((process_value, process_index) => (
			process_value.map((value, index) => {
				idx += 1
				return (
					process_arr.push({
						fk: { 'CMSMW.MW001': value },
						process_index: idx,
						processes_name: this.state.process_name[process_index][index],
						note: this.state.line_body[process_index][index]
					})
				)

			})
		))
		data['data'] = process_arr;

		axios
			.post('/3DConvert/PhaseGallery/order_processes', data)
			.then((response) => console.log(response))
			.catch((error) => console.log(error))

	}

	sendToParentfunction(dataToParentFunction) {
		this.props.sendToParentfunction(dataToParentFunction);
	}

	handle_edit = (e) => {
		this.setState({
			modal: {
				modal_body:
					<h5>
						<Row>
							{Object.entries(this.state.line_processes[e.target.attributes.line_id.value]).map(([key, item]) => {
								return (
									<Col md='3' className='my-1'>
										<Form.Group controlId={key}>
											<Form.Check
												type="checkbox"
												line_row_idx={e.target.attributes.idx.value}
												onClick={e => this.handleProcessesCheck(e)}
												label={item}
												name={e.target.attributes.line_name.value}
												process_id={key}
												process_name={item}
												defaultChecked={e.target.attributes.process_id.value.split(',').indexOf(key) !== -1 ? true : false}
											/>
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
			}
		});
		this.modalRef.current.openModal();
	}

	handle_delete = (e) => {
		let idx = parseInt(e.target.attributes.idx.value);

		let line_name = this.state.line_name;
		line_name.splice(idx, 1);
		this.setState({ line_name: line_name });

		let line_row = this.state.line_row;
		line_row.splice(idx, 1);
		this.setState({ line_row: line_row });

		let process_name = this.state.process_name;
		process_name.splice(idx, 1);
		this.setState({ process_name: process_name });

		let process_id = this.state.process_id;
		process_id.splice(idx, 1);
		this.setState({ process_id: process_id });

		let line_body = this.state.line_body;
		line_body.splice(idx, 1);
		this.setState({ line_body: line_body });

		let group_process_id = [];
		let group_process_name = [];
		this.state.process_id.map((row_value, row_index) => (
			row_value.map((value, index) => {
				group_process_id.push(value)
				group_process_name.push(group_process_name[row_index][index])
			})
		))
		this.setState({ group_process_id: group_process_id, group_process_name: group_process_name })
	}
	render() {
		let process_id = [];
		let process_name = [];
		this.state.process_id.map((row_value, row_index) => (
			row_value.map((value, index) => {
				process_id.push(value)
				process_name.push(this.state.process_name[row_index][index])
			})
		))
		return (
			<>
				<BasicModal
					modal_title={this.state.modal.modal_title}
					modal_body={this.state.modal.modal_body}
					modal_footer={this.state.modal.modal_footer}
					show={this.state.modal.show}
					ref={this.modalRef}
				></BasicModal>
				{this.state.editable ?
					<Row>
						<Col md="11">
							<Card className="my-2">
								<Card.Header style={{ color: "#545051", fontWeight: "bold" }}>點擊新增線別</Card.Header>
								<Card.Body>
									<Row>
										{this.state.all_processes.map((value, index) => (
											value['製程'] !== null ?
												<Button className="mx-1 my-1" variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.handleClickProcesses} line={value['線別代號'].trim()} line_name={value['線別名稱']} >{value['線別名稱']}</Button>
												: null
										))}
									</Row>
								</Card.Body>
							</Card>
						</Col>
						<Col md="auto">
							<Row>
								<Button className="my-2 mx-1" variant="primary" style={{ background: "#5e789f", color: "white", border: "none" }} onClick={this.saveProcess} hidden={!this.state.editable}>儲存</Button>
							</Row>
						</Col>
					</Row> : null
				}
				{this.state.editable ? <SortableCard axis="xy" on_sort_processEnd={this.on_sort_processEnd.bind(this)} line_name={this.state.line_name} process_id={this.state.process_id} process_name={this.state.process_name} line_id={this.state.line_row} cardbody={this.state.line_body} onChange={this.onChange} handle_edit={this.handle_edit} handle_delete={this.handle_delete} onSortEnd={this.onSortEnd} /> : <TechnicalModificationProcessView ref={this.child} order_id={this.props.order_id} sendToParentfunction={this.sendToParentfunction} />}
				{this.state.editable ? <ProcessGroup process_id={process_id} process_name={process_name} /> : null}
			</>
		);
	}
}