import React from "react";
import { Button, Row, Col, FormControl} from 'react-bootstrap';

export default class DetailItem extends React.Component {
    render() {
        return (
            <Row className="my-2">
                <Col xs="8">
                    <FormControl list={`data_${this.props.processid}`} name="edit" autoComplete="off" type="text" className='processDetail' detail_idx={this.props.detail_idx} value={this.props.value} onChange={this.props.onChange} processid={this.props.processid} />
                    <datalist id={`data_${this.props.processid}`}>
                        {this.props.datalist.map((value, index) => (
                            <option value={value.label} />
                        ))}
                    </datalist>
                </Col>
                <Col xs="4">
                    <Button name="edit" onClick={this.props.onClick} style={{background:"#999999", color: "white"}} detail_idx={this.props.detail_idx} variant="light">刪除</Button>
                </Col>
            </Row>
        )
    }
}
