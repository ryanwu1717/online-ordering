import React from "react";
import { SortableContainer, sortableElement } from 'react-sortable-hoc';
import { Row, CloseButton, Col } from 'react-bootstrap';

import axios from 'axios';
export default class SortableInput extends React.Component {
    render() {
        return (
            <SortableList axis="xy" handleDelete={this.props.handleDelete} search_process_id={this.props.search_process_id} search_process_name={this.props.search_process_name} onSortEnd={this.props.onSortEnd} />
        )
    }
}

class Item extends React.Component {

    state = {
        cardbody: this.props.cardbody,
        order_processes_reprocess: [],
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
            <span {...this.props} style={{ display: "inline-block", backgroundColor: '#8f7bb3', width: 'auto', fontSize: 16, }} className="mx-1 my-1 badge rounded p-3 text-center ">
                <Row>
                    <Col className="my-1">{this.props.search_process_id} {this.props.search_process_name}</Col>
                    <Col><CloseButton variant="white" className="mr-2" onClick={this.props.handleDelete} idx={this.props.idx} /></Col>
                </Row>
            </span>
        )
    }
}

const SortableItem = sortableElement(Item);

const SortableList = SortableContainer(({ search_process_name, search_process_id, handleDelete }) => {

    return (
        <Row>
            {search_process_id.map((value, index) => (
                <SortableItem key={`item-${index}`} className="mx-1 my-1 align-top" handleDelete={handleDelete} search_process_id={value} index={index} search_process_name={search_process_name[index]} idx={index} />
            ))}
        </Row>
    );
});