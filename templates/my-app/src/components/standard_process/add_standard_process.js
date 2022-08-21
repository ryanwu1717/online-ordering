import React, { useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom';
// import ChangeModal from './change_modal.js';
import Container from 'react-bootstrap/Container';
import Card from 'react-bootstrap/Card';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import axios from 'axios';
class AddStandardProcess extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      
    }
    
  }
  componentDidMount() {

  }

  
  render() {
    return (
      <Container fluid className="d-grid gap-2 my-3">
        <Row>
          <Col md="12">
            <Card className='shadow'>
              <Card.Header>
                標準製程
              </Card.Header>
              <Card.Body>
                
              </Card.Body>
            </Card>
          </Col>
        </Row>
        <Row>
          <Col md="12">
          </Col>
        </Row>
        <Row>
        </Row>
      </Container>
    );
  }
}

export default AddStandardProcess;