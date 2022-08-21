import { Card, Row,Button,Form ,InputGroup,FormControl} from "react-bootstrap";
import 'bootstrap/dist/css/bootstrap.min.css';
import React from "react";
import Search from '../components/Search';
import Datatable from "../components/Datatable";
import BasicModal from '../components/BasicModal';
import axios from 'axios';
import { FaQrcode } from "react-icons/fa";

import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
 
class StorageSpaceQuery extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            type: "",
            number: "",
            name: "",
            standard: "",
            Search: {
                Select_row1: [
                  { 'label': '廠商代號:', 'id': 'type', 'type': 'input', 'value': '' },
                  { 'label': '品號:', 'id': 'number', 'type': 'input', 'value': '' },
                  { 'label': '品名:', 'id': 'name', 'type': 'input', 'value': '' },
                  { 'label': '儲位:', 'id': 'standard', 'type': 'input', 'value': '' },
                ],
                Select_new: [
                    { 'label': '廠商代號:', 'id': 'type', 'type': 'input', 'value': '' },
                    { 'label': '品名:', 'id': 'name', 'type': 'input', 'value': '' },
                    { 'label': '儲位:', 'id': 'standard', 'type': 'input', 'value': '' },
                ],
            },
            datatables: {
                require: {},
                request_label: {
                    coptd_td001: '單別',
                    coptd_td002: '單號',
                    coptd_td003: '序號',
                    coptc_tc003: '客戶代號',
                },
                request: '/warehousing/origin_material_supplier',
                thead: [
                  {
                    name: '#',
                    cell: row => <Form.Label>{row.id}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: '廠商',
                    cell: row => <Form.Label>{row.type}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: '品號',
                    cell: row => <Form.Label>{row.number}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: '品名',
                    cell: row => <Form.Label>{row.name}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: '規格',
                    cell: row => <Form.Label>{row.specification}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: '目前數量',
                    cell: row => <Form.Label>{row.count}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: '儲位',
                    cell: row => <Form.Label>{row.standard}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: '備註',
                    cell: row => <Form.Label>{row.note}</Form.Label>,
                    width: 'auto',
                    center: true,
                  },
                  {
                    name: 'QR Code',
                    cell: row => <Button variant="light" file_id={row.qr_code} style={{width:'auto', background:"#5F5F5F", color: "white", border: "none" }} onClick={e => this.imgHandler(e)}>QR Code</Button>,
                    width: 'auto',
                    center: true,
                  },
                ]
            },
            modal: {
				show: false,
                modal_body: '',
                modal_footer: '',
                modal_title: "",
			},
        }
        this.child = React.createRef();
        this.handleSearchChange = this.handleSearchChange.bind(this);
        this.getOriginMaterialSupplier = this.getOriginMaterialSupplier.bind(this);
        this.showModal = this.showModal.bind(this);
    }

    handleSearchChange = (e) => {
        this.setState({
          [e.id]: e.value
        });
    }

    imgHandler = (e) => {
        this.setState({ image: [] });
        axios
        .get(e.target.attributes.file_id.value, { responseType: "blob" })
        .then(response => {
            var reader = new window.FileReader();
			reader.readAsDataURL(response.data);
			reader.onload = (e) => {
				var imageDataUrl = reader.result;
				let images = this.state.image;
				images.push(imageDataUrl);
				this.setState({ image: images });
                this.setState({
                    modal: {
                        modal_body: 
                        <Card className="bg-dark text-white">
                            <Card.Img src={this.state.image[0]} alt="Card image" />
                        </Card>,
                        modal_title: "QR Code",
                        modal_footer: "",
                        show: true,
                    }
                });
                this.child.current.openModal();
			};
            
        })
        .catch(function (error) {
            console.log(error);
        });
    }

    showModal() {
        this.setState({
            modal: {
                modal_title: "新增儲位",
                modal_body: 
                <>
                    <InputGroup className="mb-3">
                        <InputGroup.Text id="basic-addon3">廠商代號</InputGroup.Text>
                        <FormControl />
                    </InputGroup>
                    <InputGroup className="mb-3">
                        <InputGroup.Text id="basic-addon3">品名</InputGroup.Text>
                        <FormControl />
                    </InputGroup>
                    <InputGroup className="mb-3">
                        <InputGroup.Text id="basic-addon3">儲位</InputGroup.Text>
                        <FormControl />
                    </InputGroup>
                    <InputGroup className="mb-3">
                        <InputGroup.Text id="basic-addon3">進貨數量</InputGroup.Text>
                        <FormControl type="number" placeholder="請輸入阿拉伯數字" />
                    </InputGroup>
                </>,
                modal_footer: <Button className="mx-2" variant="light" style={{width:'auto', background:"#E97465", color: "white", fontWeight: "bold" }}>新增</Button>,
                show: true,
            }
        });
        this.child.current.openModal();
    }
    getOriginMaterialSupplier () {
        this.setState(prevState => ({
            datatables: {                   
                ...prevState.datatables,    
                require: {
                    type: this.state.type,
                    name: this.state.name,
                    number: this.state.number,
                    standard: this.state.standard
                }     
            }
        }))
        console.log(this.state.datatables)
    }
    postProcess(response) {
        return response;
    }

    componentDidMount() {
      
    }

    render() {
        return (
            <>
                <BasicModal
					modal_title={this.state.modal.modal_title}
					modal_body={this.state.modal.modal_body}
					modal_footer={this.state.modal.modal_footer}
					show={this.state.modal.show}
					ref={this.child}
				></BasicModal>
                <h4 style={{color: "#858796", fontWeight: "bold"}}>儲位查詢</h4>
                <Card className="mb-4">
                    <Card.Header style={{color: "#5e789f", fontWeight: "bold"}}>原物料查詢與新增</Card.Header>
                    <Card.Body>
                        <Row style={{ padding: "0rem 0rem 1rem" }}>
                            <Search resetData={this.handleSearchChange.bind(this)} name={this.state.Search.Select_row1}></Search>
                            <Button className="mx-2" variant="light" onClick={this.getOriginMaterialSupplier} style={{width:'auto', background:"#5e789f", color: "white", fontWeight: "bold" }}>查詢</Button>
                            <Button className="mx-2" variant="light" onClick={this.showModal} style={{width:'auto', background:"#DA5C5D", color: "white", fontWeight: "bold" }}>新增</Button>
                        </Row>
                    </Card.Body>
                </Card>
                <Card className="mb-4">
                    <Card.Header style={{color: "#5e789f", fontWeight: "bold"}}>電極</Card.Header>
                    <Card.Body>
                    <Datatable datatables={this.state.datatables} postProcess={this.postProcess} ref={this.tableRef} api_location="/warehousing/origin_material_supplier" /> 
                    </Card.Body>
                </Card>
            </>
        )
    }
}

export default StorageSpaceQuery
