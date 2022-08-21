import React from 'react';
import { SortableContainer, SortableElement } from 'react-sortable-hoc';
import { CloseButton, FloatingLabel, Button, Container, Card, Row, Col, Form } from 'react-bootstrap';

export default class SortableComponent extends React.Component {
	render() {
		return (
			<SortableList axis="y" handleDelete={this.props.handleDelete} process_name={this.props.process_name} process_id={this.props.process_id} onSortEnd={this.props.onSortEnd} />
		)
	}
}

class Item extends React.Component {
	state = {
		process_id: this.props.process_id,
	}
	componentDidMount() {
	}
	render() {
		return (
			
					<Card className='h-100' {...this.props} style={{ borderColor: "#577567", display: "inline-block" }}>
						<Card.Body>
							<Row>
								<Col xs="2">
									<h5>{this.props.idx + 1}</h5>
								</Col>
								<Col xs="2">
									<h5>{this.props.process_id}</h5>
								</Col>
								<Col xs="4">
									<h5>{this.props.process_name}</h5>
								</Col>
								<Col style={{ display: 'flex', justifyContent: 'right' }} xs="4">
									<CloseButton />
								</Col>
							</Row>
						</Card.Body>
					</Card>
		)
	}
}

const SortableItem = SortableElement(Item);
const SortableList = SortableContainer(({ process_name, process_id, handleDelete }) => {
	// console.log(process_id)
	return (
		<Row>
			{process_id.map((value, index) => (
				<SortableItem key={`process-${index}`} className="mx-1 my-1" handleDelete={handleDelete} process_id={value} process_name={process_name[index]} idx={index} />
			))}
		</Row>
	);
});
