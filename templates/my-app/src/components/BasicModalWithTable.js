import React from "react";
import { Button, Modal} from 'react-bootstrap';
import DataTable from 'react-data-table-component';

class BasicModalWithTable extends React.Component {
    
    render() {
        return (
            <Modal 
            show={this.props.show}
            size="lg"
            scrollable={true}
          >
            <Modal.Header>
              <Modal.Title>{this.props.modal_title}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
            <DataTable
              columns={this.props.datatables.thead}
              data={this.props.processRow}
              highlightOnHover
              pagination
            />
            </Modal.Body>
            <Modal.Footer>
            <Button variant="secondary" onClick={this.props.cancel}>
                取消
              </Button>
              <Button variant="primary" style={{background:"#5e789f", color: "white", border: "none"}} onClick={this.props.handleClose}>
                新增
              </Button>
            </Modal.Footer>
          </Modal>
        )
    }
}

export default BasicModalWithTable