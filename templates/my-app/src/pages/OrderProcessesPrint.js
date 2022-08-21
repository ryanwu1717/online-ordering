import React, { useState, useEffect } from "react";
import { Modal, ModalTitle, ModalBody, ModalFooter } from 'react-bootstrap';
import axios from 'axios';
import Datatable from "react-data-table-component";
import ModalHeader from "react-bootstrap/esm/ModalHeader";

function OrderProcessesPrint(props) {
    const [modalShow,setModalShow] = useState(false);
    const [modalBody,setModalBody] = useState([]);
    const [modalResult,setModalResult] = useState([]);
    const modalInfo = ['製令單別','製令單號','客戶圖號','預計生產完成日'];
    const [data,setData] = useState([]);
    const columns=[
      {
        name: '單別',
        cell: row => row.製令單別,
        width: 'auto',
        center: true,
      },
      {
        name: '單號',
        cell: row => row.製令單號,
        width: 'auto',
        center: true,
      },
      {
        name: '客戶圖號',
        cell: row => row.客戶圖號,
        width: 'auto',
        center: true,
      },
      {
        name: '預計生產完成日',
        cell: row => row.預計生產完成日,
        width: 'auto',
        center: true,
      },
    ]
    useEffect(()=>{
      axios
       .get(`/RFID/printer/orderProcesses`)
       .then(response=>{
         setData(response.data)
       })
    },[])
    const rowClickedHandler = (row) => {
        setModalShow(true);
        setModalBody(row);
    }
    const handleModalOnHide = ()=>{
      setModalShow(false)
      setModalBody([]);
      setModalResult([]);
    }
    const handlePrint = ()=>{
      axios
        .post(`/RFID/printer/orderProcesses`,modalBody)
        .then(response=>{
          console.log(response.data)
          setModalResult(response.data);
        })
    }
    return (
      <>
        <Modal show={modalShow} backdrop={'static'} onHide={handleModalOnHide}>
          <ModalHeader closeButton onHide={handleModalOnHide}>
            <ModalTitle>製令單資訊</ModalTitle>
          </ModalHeader>
          <ModalBody>
            {
              modalResult.length===0?(
                modalInfo.map((value)=>{
                  return (
                    <p>{value}：{modalBody[value]}</p>
                  )
                })
              ):(
                Object.keys(modalResult).map((key)=>{
                  return modalResult[key]==='success'?<p>已完成列印</p>:'';
                })
              )
            }
          </ModalBody>
          <ModalFooter>
            {
              modalResult.length!==0?(<></>):(
                <button type="button" className="btn btn-primary" onClick={handlePrint}>列印</button>
              )
            }
            <button type="button" className="btn btn-secondary" onClick={handleModalOnHide}>關閉</button>
          </ModalFooter>
        </Modal>
        <Datatable columns={columns} data={data} onRowClicked={rowClickedHandler} />
      </>
    );
}
  
export default OrderProcessesPrint;