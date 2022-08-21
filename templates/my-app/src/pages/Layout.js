import React, { Component } from 'react';
import {Navbar, Nav, NavItem, Row, Col} from 'react-bootstrap'
import {Outlet, BrowserRouter as Router, Route, Link} from 'react-router-dom'
import {LinkContainer} from 'react-router-bootstrap';
import Notices from '../components/Notices';
import UserInfo from '../components/UserInfo';
import PhoneInfo from '../components/PhoneInfo';

class Layout extends React.Component {
  constructor(props) {
    super(props);
    this.state = { active: 1 };
    this.handleSelect = this.handleSelect.bind(this);
  }

  handleSelect(selectedKey) {
    this.setState({active: selectedKey});
  }

  render() {
    const sidebar = {
      position: 'fixed',
      top: '51px',
      bottom: '0',
      left: '0',
      zIndex: '1000',
      display: 'block',
      padding: '20px',
      overflowX: 'hidden',
      overflowY: 'auto',
      backgroundColor: '#f5f5f5',
      borderRight: '1px solid #eee'
    };
    return (
        <div>
          <Navbar fluid={true}>
            <Navbar.Header>
              <Navbar.Brand>
                <a href="/">React-Bootstrap</a>
              </Navbar.Brand>
            </Navbar.Header>
          </Navbar>
          {/* <Grid fluid={true}> */}
            <Row className="show-grid">
              <Col xs={2} style={sidebar}>
                <Nav stacked activeKey={this.state.active} onSelect={this.handleSelect}>
                  <LinkContainer to="/userinfo">
                    <NavItem eventKey={1}>使用者清單</NavItem>
                  </LinkContainer>
                  <LinkContainer to="/phoneinfo">
                    <NavItem eventKey={2}>電話清單</NavItem>
                  </LinkContainer>
                  <LinkContainer to="/remakeinfo">
                    <NavItem eventKey={3} disabled>備註清單</NavItem>
                  </LinkContainer>
                </Nav>
              </Col>
              <Col xs={10} xsOffset={2}>
                <Route exact path="/" component={Notices}/>
                <Route path="/userinfo" component={UserInfo}/>
                <Route path="/phoneinfo" component={PhoneInfo}/>
              </Col>
            </Row>
          {/* </Grid> */}
          <Outlet />
        </div>
    );
  }
}

export default Layout;