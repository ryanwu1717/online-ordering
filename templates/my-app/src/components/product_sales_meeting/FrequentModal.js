import React from "react";
import { Modal, Row, Col, Button } from 'react-bootstrap';
import axios from 'axios';

class FrequentModal extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            show: this.props.show,
            frequent_group: [],
            button_style: {
                textOverflow: 'ellipsis',
                overflow: 'hidden',
                whiteSpace: "nowrap",
                width: 'auto',
                maxWidth: '100%',
                fontSize: "18px",
                fontWeight: "bold",
                background: "white",
                color: "#5e789f",
                borderColor: "#5e789f",
                borderWidth: "medium"
            },
        }
        this.openModal = this.openModal.bind(this);
        this.closeModal = this.closeModal.bind(this);
        this.saveMeet = this.saveMeet.bind(this);
        this.getFrequentGroup = this.getFrequentGroup.bind(this);
        this.addParticipant = this.addParticipant.bind(this);
    }
    openModal() {
        this.setState({
            show: true,
        })
    }

    closeModal() {
        this.setState({
            show: false,
        })
    }
    saveMeet() {
        this.props.saveMeet("modal")
    }
    componentDidMount() {
        this.getFrequentGroup()
    }
    addParticipant = (event)=> {
        let frequent_group_id =event.target.attributes.frequent_group_id.value
        this.props.handleClick(frequent_group_id)
    }
    getFrequentGroup() {
        axios.get(`/CRM/frequent_group`)
            .then((response) => {
                let frequent_group = response.data
                this.setState({
                    frequent_group: frequent_group
                })
            });
    }
    componentDidUpdate(prevProps, prevState, snapshot) {
        if (this.state.show !== prevState.show) {
            if (this.state.show) {
                this.getFrequentGroup()
            }
        }

    }
    render() {
        return (
            <Modal
                show={this.state.show}
                size="lg"
                scrollable={true}
                onHide={this.closeModal}
            style={{ height: this.props.height !== "undefined" ? this.props.height : "280px" }}
            >
                <Modal.Header >
                    <Modal.Title>
                        訊息
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row>
                        {
                            this.state.frequent_group.length > 0 ?
                                Object.keys(this.state.frequent_group).map((index) => (
                                    <Col md={"auto"} xs = {"auto"}>
                                        <Button style={this.state.button_style} onClick={this.addParticipant} frequent_group_id={this.state.frequent_group[index].frequent_group_id} variant="outline-success">{this.state.frequent_group[index].frequent_group_name}</Button>
                                    </Col>
                                )) : null
                        }
                    </Row>
                </Modal.Body>
                <Modal.Footer>
                    <Row>
                        <Col md={"auto"}>
                            {/* <Button variant="outline-success" onClick={this.saveMeet} >儲存會議</Button> */}
                        </Col>
                        <Col md={"auto"}>
                            {/* <Button meet_id={this.state.meet_id} variant="outline-success" onClick={this.getFrequentGroup} >前往會議</Button> */}
                        </Col>
                    </Row>
                </Modal.Footer>
            </Modal>
        )
    }
}

export default FrequentModal