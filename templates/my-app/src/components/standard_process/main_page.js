import React, { useState, useEffect, useCallback, useMemo } from 'react';
import ReactDOM from 'react-dom';
// import ChangeModal from './change_modal.js';
import { Container, Card, Button, Col, Row, FormControl, } from 'react-bootstrap';
import SortableComponent from './sort_card';
import ArticleNumber from './article_number';
import ArticleNumberInput from './article_number_input'
import App from './sort_card.js'
import axios from 'axios';
class MainPage extends React.Component {
  constructor(props) {
    super(props);
    this.state = {

    }
    this.SortChild = React.createRef();
    this.AddItem = this.AddItem.bind(this);

  }
  componentDidMount() {

  }
  AddItem() {
    this.SortChild.current.AddItem();
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
                <Row className='mb-2'>
                  <Col md="auto">
                    <Button variant="primary" onClick={this.AddItem}>新增製程</Button>{' '}
                  </Col>
                </Row>
                <Row className='mb-2'>
                  <SortableComponent ref={this.SortChild} />
                </Row>
              </Card.Body>
            </Card>
          </Col>
        </Row>
        <Row>
          <Col md="12">
            <Card className='shadow'>
              <Card.Header>
                製程參考
              </Card.Header>
              <Card.Body>
                <ArticleNumberInput/>
                <ArticleNumber />
              </Card.Body>
            </Card>

          </Col>
        </Row>
        <Row>
        </Row>
      </Container>
    );
  }
}

export default MainPage;