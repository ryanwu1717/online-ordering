// Demo.jsx
import React from 'react';
import axios from 'axios';
import 'bootstrap/dist/css/bootstrap.min.css';
import { Card, Row, Col, Button, InputGroup, FormControl } from 'react-bootstrap';
import './TechnicalModificationProcessView.css'
import BasicModal from '../components/BasicModal';

export default class TechnicalModificationProcessView extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            processes: [],
            image: [],
            modal: {
                show: false,
                modal_body: '',
                modal_footer: '',
            },
        };
        this.sendToParentfunction = this.sendToParentfunction.bind(this);
        this.handleClickCard = this.handleClickCard.bind(this);
        
        this.showImg = this.showImg.bind(this);
        this.child = React.createRef();
    }
    process = (e) => {
        alert(e.target.value)
    }
    componentDidMount() {
        this.getProcessData();
    }

    getProcessData = () => {
        console.log(this.props.order_id)
        let order_id = this.props.order_id || 1;
        axios
        .get("/3DConvert/PhaseGallery/order_processes/reprocess",  { params: { order_id: order_id } })
        .then(response => {
            response.data.map((value, index) => {
                value['line_id'] = value['line_id'].trim()
            })
            this.setState({processes: response.data})
        })
        .catch(function (error) {
            console.log(error);
        });

        // axios
        //     .get(`/3DConvert/PhaseGallery/order_processes/reprocess`, { params: { order_id: order_id } })
        //     .then(response => {
        //         let processes_data = {};
        //         response.data.map((item) => {
        //             processes_data[item.processes_id] = {}
        //             processes_data[item.processes_id]['detail'] = {
        //                 processes_name: item.processes_name,
        //                 reprocesses_name: {}
        //             }
        //             item.reprocess.map((reprocess_item) => {
        //                 processes_data[item.processes_id][reprocess_item.order_processes_reprocesses_id] = reprocess_item.images
        //                 processes_data[item.processes_id]['detail']['reprocesses_name'][reprocess_item.order_processes_reprocesses_id] = reprocess_item.order_processes_reprocesses_name
        //             })
        //         })
        //     })
        //     .catch(function (error) {
        //         console.log(error);
        //     });
            let res_data = [
                {  
                    line_id: 'A', 
                    line_name: 'A)車床組', 
                    processes: [
                         { process_index: 0, processes_id: '201', processes_name: '粗車' , note: '11' },
                         { process_index: 1, processes_id: '205', processes_name: '內孔加工' , note: '12' },
                         { process_index: 2, processes_id: '209', processes_name: '車床切、車溝' , note: '13' },
                    ]
                },
                {  
                    line_id: 'B', 
                    line_name: 'B)CNC車床及程式組', 
                    processes: [
                         { process_index: 3, processes_id: '302', processes_name: 'CNC中' , note: '21' },
                    ]
                },
                {  
                    line_id: 'A', 
                    line_name: 'A)車床組', 
                    processes: [
                         { process_index: 4, processes_id: '219', processes_name: '傳統壓花' , note: '31' },
                         { process_index: 5, processes_id: '214', processes_name: '傳統長度' , note: '32' },
                    ]
                },
            ]
            // this.setState({processes: res_data})
    }


    handleClickCard = (e) => {
        let data = this.state.processes[e.target.attributes.line_index.value]
        data['type'] = 'line';
        data['line_index'] = (parseInt(e.target.attributes.line_index.value) + 1);
        console.log(data)
        /*
            {  
                line_id: 'A', 
                line_name: 'A)車床組', 
                line_index: 2,
                type: 'line',
                processes: [
                    { process_index: 4, processes_id: '219', processes_name: '傳統壓花' , note: '31' },
                    { process_index: 5, processes_id: '214', processes_name: '傳統長度' , note: '32' },
                ]
            },
        */
    }

    showImg = (e) => {
        let client_name = e.target.attributes.client_name.value
        this.setState({ image: [] });
        axios
            .get(e.target.attributes.src.value, { responseType: "blob" })
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
                            modal_title: client_name,
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

    sendToParentfunction(e) {
        let data = JSON.parse(JSON.stringify(this.state.processes[e.target.attributes.line_index.value]))
        let process = data['processes'][e.target.attributes.idx.value]
        data['processes'] = [process];
        data['line_index'] = (parseInt(e.target.attributes.line_index.value) + 1);
        data['type'] = 'process';
        /*
            {  
                line_id: 'A', 
                line_name: 'A)車床組', 
                line_index: 2,
                type: 'process',
                processes: [
                    { process_index: 4, processes_id: '219', processes_name: '傳統壓花' , note: '31' },
                ]
            },
        */

        // let data_return = {};
        // data_return = {
        //     order_processes_reprocesses_id: e.target.getAttribute('order_processes_reprocesses_id'),
        //     processes_id: e.target.getAttribute('processes_id'),
        // }
        this.props.sendToParentfunction(data);
    }

    render() {
        return (
            <div>
                <BasicModal
                    modal_title={this.state.modal.modal_title}
                    modal_body={this.state.modal.modal_body}
                    modal_footer={this.state.modal.modal_footer}
                    show={this.state.modal.show}
                    ref={this.child}
                ></BasicModal>
                {this.state.processes.map((item, index) => {
                    return (
                        <Card className="mx-2 my-1 align-top" style={{ borderColor: "#577567", display: "inline-block", width: "350px" }}>
                            <Card.Header className="justify-content-center align-items-center" style={{ borderColor: "#577567", background: "#ebedec", color: "#4E5068", fontWeight: "bold", cursor: "pointer" }} line_index={index} onClick={this.handleClickCard} line_id={item.line_id}>{index + 1}. {item.line_name}</Card.Header>
                            <Card.Body>
                                {
                                    item.processes.map((processes_value, processes_index) => {
                                        return (
                                            <Row xs="auto" className="my-1 mx-1" >
                                               <InputGroup>
                                                   <Button style={{ background: "#5e789f", borderColor: "#5e789f", color: "white", fontWeight: "bold" }} variant="light" name="edit" type="text" key={`${item.line_id}_${processes_value.process_index}`} line_index={index} value={processes_value.processes_name} processes_id={processes_value.processes_id} processes_name={processes_value.processes_name} onClick={this.sendToParentfunction} idx={processes_index} >{processes_value.processes_name}</Button>
                                                   <FormControl
                                                   aria-label="Example text with button addon"
                                                   aria-describedby="basic-addon1"
                                                   value={processes_value.note}
                                                   style={{ backgroundColor: 'white' }}
                                                   disabled
                                                   />
                                               </InputGroup>
                                           </Row>

                                        )
                                    })
                                }
                            </Card.Body>
                        </Card>
                    )
                })}
            </div>
        );
    }
}