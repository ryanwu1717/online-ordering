import React, { useState, useEffect, useRef } from "react";
import { Button, Modal } from 'react-bootstrap';
import DataTable from 'react-data-table-component';

function ModalWithDataTable(props) {
  const [columns, setColumns] = useState([]);
  const [data, setData] = useState([]);

  useEffect(() => {
    setColumns(props.columns);
    setData(props.data);
  }, [props.columns, props.data])

  return (
    <Modal
      show={props.show}
      size="lg"
      scrollable={true}
    >
      <Modal.Header>
        <Modal.Title>{props.modalTitle}</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <Button className="float-right" variant="light" style={{ background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={props.addModule}>
          新增
        </Button>
        {props.datatableLoading
          ? <>讀取中...</>
          : <DataTable
            columns={columns}
            data={data}
            highlightOnHover
            pagination
          />
        }
      </Modal.Body>
      <Modal.Footer>
        <div className={props.editCheckColor}>{props.editCheck}</div>
        <Button variant="secondary" onClick={props.modalCancel}>
          關閉
        </Button>
        <Button variant="primary" style={{ background: "#5e789f", color: "white", border: "none" }} onClick={props.updateElement}>
          保存
        </Button>
      </Modal.Footer>
    </Modal>
  )
}

export default ModalWithDataTable