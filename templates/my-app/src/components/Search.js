import React from "react";
import { Col, Form, FloatingLabel } from 'react-bootstrap';

class Search extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            field: this.props.name,
            return: {}
        }
        this.resetData = this.resetData.bind(this);
    }

    resetData = (event) => {   
        this.props.resetData({
            id: event.target.id,
            value: event.target.value
        });
    }

    render() {
        const row = this.state.field;
        return (
            <>
                {row.map((data, index) => (
                    <Col key={data.id}>
                        <FloatingLabel label={data.label} className="mb-2">
                            <Form.Control
                                name={data.name}
                                isInvalid={data.isinvalid}
                                autoComplete="off"
                                id={data.id}
                                type={data.type}
                                value={this['props']['name'][index]['value']}
                                defaultValue={data.value}
                                disabled={data.disabled}
                                onChange={this.resetData}
                            // required={data.required}
                            />
                        </FloatingLabel>
                    </Col>
                ))}
            </>
        )
    }
}
export default Search;
