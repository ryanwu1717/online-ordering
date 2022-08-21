import React, { useState, useEffect, useCallback, useMemo } from 'react';
// import React, {} from 'react';
import Container from 'react-bootstrap/Container';
import Button from 'react-bootstrap/Button';
import Card from 'react-bootstrap/Card';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import Form from 'react-bootstrap/Form';

import Alert from 'react-bootstrap/Alert';
import { useDropzone } from 'react-dropzone'
import Image from 'react-bootstrap/Image'
import ToggleButton from 'react-bootstrap/ToggleButton'
import ToggleButtonGroup from 'react-bootstrap/ToggleButtonGroup'
import DataTable from 'react-data-table-component';
import "bootstrap/dist/js/bootstrap.bundle.js";
import "bootstrap/dist/css/bootstrap.css";
import axios from 'axios';
const RatioBlock = () => {
    return (
      <Row className='gy-3'>
        <Col>
          <Alert variant="secondary">
            廠內負載率:100%
          </Alert>
        </Col>
        <Col>
          <Alert variant="secondary">
            報價進單率:100%
          </Alert>
        </Col>
        <Col>
          <Alert variant="secondary">
            平均交貨天數:15-30天
          </Alert>
        </Col>
        <Col>
          <Alert variant="secondary">
            機台稼動率:100%
          </Alert>
        </Col>
      </Row>
    );
  }
  export default RatioBlock;