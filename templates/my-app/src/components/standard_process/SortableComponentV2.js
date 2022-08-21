import React from "react";
import { SortableContainer, sortableElement } from 'react-sortable-hoc';
import { Card, CloseButton, Col, Row, FormControl, InputGroup, ButtonGroup } from 'react-bootstrap';

import axios from 'axios';
export default class SortableCard extends React.Component {
    render() {
        return (
            <SortableList axis="y" handleDelete={this.props.handleDelete} process_name={this.props.process_name} process_id={this.props.process_id} onSortEnd={this.props.onSortEnd} />
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

    }

    addProcess = () => {
        this.setState({
            cardbody: [...this.state.cardbody, ""]
        })
    }


    componentDidMount() {
    }
    render() {

        return (
            <Card className="h-100" {...this.props} style={{ height: "70px", borderColor: "#949cac", display: "inline-block", width: "98%" }}>

                <Card.Body>
                    <Row className="my-2">
                        <Col xs="2">
                            <h5>{this.props.idx + 1}</h5>
                        </Col>
                        <Col xs="3">
                            <h5 style={{ whiteSpace: 'nowrap' }}>{this.props.process_id}</h5>
                        </Col>
                        <Col xs="4">
                            <h5 style={{ whiteSpace: 'nowrap' }}>{this.props.process_name}</h5>
                        </Col>
                        <Col style={{ display: 'flex', justifyContent: 'right' }} xs="3">
                            <CloseButton idx={this.props.idx} onClick={this.props.handleDelete} />
                        </Col>
                    </Row>
                </Card.Body>
            </Card>
        )
    }
}

const SortableItem = sortableElement(Item);
const SortableList = SortableContainer(({ process_name, process_id, handleDelete }) => {

    return (
        <div style={{ height: '400px', overflowY: 'scroll' }}>
            {process_name.map((value, index) => (
                <SortableItem key={`item-${index}`} handleDelete={handleDelete} className="mx-1 my-1 align-top" process_id={process_id[index]} index={index} process_name={process_name[index]} idx={index} />
            ))}
        </div>
    );
});