import React from "react";
import { SortableContainer, sortableElement } from 'react-sortable-hoc';
import { Card, Button, Col, Row, FormControl, InputGroup, ButtonGroup } from 'react-bootstrap';
import DetailItem from './DetailItem';
import axios from 'axios';
import SortableProcess from "./SortableProcess";
export default class SortableCard extends React.Component {
	render() {
		return (
			<SortableList axis="xy" handleDelete={this.props.handleDelete} handleEdit={this.props.handleEdit} onSortProcessEnd={this.props.onSortProcessEnd} line_name={this.props.line_name} process_name={this.props.process_name} process_id={this.props.process_id} line_id={this.props.line_id} cardbody={this.props.cardbody} onChange={this.props.onChange} onSortEnd={this.props.onSortEnd} />
		)
	}
}

class Item extends React.Component {

	state = {
		cardbody: this.props.cardbody,
		order_processes_reprocess: [],
	}

	handleChange = (e) => {
		this.props.onChange(e.target.value)
	}



	delProcess = (e) => {
		let options = [...this.state.cardbody];
		options.splice(e.target.attributes.detail_idx.value, 1);
		this.setState({ cardbody: options });
		console.log(e.target.attributes.detail_idx.value)
	}

	addProcess = () => {
		this.setState({
			cardbody: [...this.state.cardbody, ""]
		})
	}


	componentDidMount() {
	}
	render() {
		let process_id = this.props.process_id;
		let process_id_split = "";
		process_id.map((value, index) => (
			process_id_split += `${value},`
		))
		console.log(process_id_split)
		return (
			<Card className="h-100" {...this.props} style={{ borderColor: "#577567", display: "inline-block", width: "350px" }}>
				<Card.Header className="justify-content-center align-items-center" style={{ background: "#E6F6CA", color: "#4E5068", borderColor: "#577567", fontWeight: "bold" }}>
					<Row>
						<Col md="7" style={{ padding: '8px 0rem 0rem 1rem' }}>{this.props.line_name}</Col>
						<Col md="5" style={{ display: 'flex', justifyContent: 'right' }}>
							<ButtonGroup aria-label="Basic example">
								<Button style={{ width: 'auto', background: "#E6F6CA", color: "#5e789f", border: 'none', fontWeight: "bold" }} variant="light" process_id={process_id_split} line_id={this.props.line_id} idx={this.props.idx} line_name={this.props.line_name} onClick={this.props.handleEdit}>編輯</Button>
								<Button style={{ width: 'auto', background: "#E6F6CA", color: "#5e789f", border: 'none', fontWeight: "bold" }} variant="light" process_id={process_id_split} line_id={this.props.line_id} idx={this.props.idx} line_name={this.props.line_name} onClick={this.props.handleDelete}>刪除</Button>
							</ButtonGroup>
						</Col>
					</Row>
				</Card.Header>
				<Card.Body>
					<Row className="my-2">
						<Col xs="12">
							<SortableProcess
								axis="xy"
								line={this.props.line_id}
								line_idx={this.props.idx}
								process_id={this.props.process_id}
								process_name={this.props.process_name}
								onSortEnd={this.props.onSortProcessEnd}
								cardbody={this.props.cardbody}
								onChange={this.props.onChange}
							/>
						</Col>
					</Row>
				</Card.Body>
			</Card>
		)
	}
}

const SortableItem = sortableElement(Item);
const SortableList = SortableContainer(({ line_name, cardbody, line_id, process_name, process_id, onChange, onSortProcessEnd, handleDelete, handleEdit }) => {

	return (
		<div>
			{line_name.map((value, index) => (
				<SortableItem key={`item-${index}`} onChange={onChange} handleDelete={handleDelete} handleEdit={handleEdit} onSortProcessEnd={onSortProcessEnd} className="mx-1 my-1 align-top" process_id={process_id[index]} index={index} line_name={value} process_name={process_name[index]} cardbody={cardbody[index]} line_id={line_id[index]} idx={index} />
			))}
		</div>
	);
});
