import React, { useState, useRef, useEffect } from 'react';
import { Table, Col, Row, Button, Card, FormControl, InputGroup, TabContainer } from "react-bootstrap";
import Datatable from '../components/Datatable';
import './TabContent.css'
const TabContent = (props) => {

    const addz = (num, length) => {
        if (num.length >= length) { return num }
        else {
            return addz(("0" + num), length)
        }
    }


    useEffect(() => {
    }, []);

    return (
        <div className='tabContent'>
            <Datatable ref={props.tableRef} datatables={props.datatables} postProcess={props.postProcess} api_location={props.api_location} rowClickedHandler={props.rowClickedHandler} />
        </div>
    );
}

export default TabContent;
