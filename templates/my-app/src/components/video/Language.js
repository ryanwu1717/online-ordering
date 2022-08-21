import React from "react";
import Nav from 'react-bootstrap/Nav';
import Row from 'react-bootstrap/Row';
import Col from 'react-bootstrap/Col';
import axios from 'axios'
import Card from 'react-bootstrap/Card';

class Language extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            digitalCollectionContentData: []
        };
    }

    componentDidMount() {
        fetch('/develop/video/translations?video_id=1&language_id[0]=1', {
            method: "GET",
            headers: new Headers({
                'Content-Type': 'application/json',
            })
        })
            .then(res => res.json())
            .then(data => this.setState({ digitalCollectionContentData: data }))
            .catch(e => {
                /*發生錯誤時要做的事情*/
            })
    }

    render() {
        return (
            <Row>
                <Col>
                    <Card className = "shadow">
                        <Card.Header>
                            <span>原文</span>
                        </Card.Header>
                        <Card.Body>
                            <div className="mh-100 d-flex">
                                {/* <Card.Img src={this.state.img_url} className="img-fluid img-thumbnail rounded float-left" alt="..."></Card.Img> */}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col>
                    <Card className = "shadow">
                        <Card.Header>
                            <span>英文</span>
                        </Card.Header>
                        <Card.Body>
                            <div className="mh-100 d-flex">
                                {/* <Card.Img src={this.state.img_url} className="img-fluid img-thumbnail rounded float-left" alt="..."></Card.Img> */}
                            </div>
                        </Card.Body>
                    </Card>
                </Col><Col>
                    <Card className = "shadow">
                        <Card.Header>
                            <span>中文</span>
                        </Card.Header>
                        <Card.Body>
                            <div className="mh-100 d-flex">
                                {/* <Card.Img src={this.state.img_url} className="img-fluid img-thumbnail rounded float-left" alt="..."></Card.Img> */}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            // <div>
            //     <Nav variant = "fill tabs" id="nav-tab" role="tablist" defaultActiveKey="/home">
            //         <Nav.Item>
            //             <Nav.Link href="/home" data-toggle="tab">Active</Nav.Link>
            //         </Nav.Item>
            //         <div class="tab-content" id="nav-tabContent">
            //             {this.state.digitalCollectionContentData.map((data, i) => (
            //                 <div name={"lang" + data.id} class={(i === 0) ? "tab-pane fade show" : "tab-pane fade"} id={"nav-lang" + data.id} role="tabpanel" aria-labelledby={"nav-lang" + data.id + "-tab"}>
            //                     <div name="card_success" class="card shadow h-100 py-2">
            //                         <div class="card-body row row-cols-1">
            //                             {data.note_content}
            //                         </div>
            //                     </div>
            //                 </div>
            //             ))}
            //         </div>
            //     </Nav>
            // </div>

            /*oringinal*/ 
            // <div>
            //     <nav>
            //         <Nav variant = "fill tabs" id="nav-tab" role="tablist">
            //             {this.state.digitalCollectionContentData.map((data, i) => (
            //                 <a class={(i === 0) ? "nav-item nav-link active" : "nav-item nav-link"} id={"nav-lang" + data.id + "-tab"} data-toggle="tab" href={"#nav-lang" + data.id} role="tab" aria-controls={"nav-lang" + data.id} aria-selected="true">{data.language_name}</a>
            //             ))}
            //         </Nav>
            //     </nav>
            //     <div class="tab-content" id="nav-tabContent">
            //         {this.state.digitalCollectionContentData.map((data, i) => (
            //             <div name={"lang" + data.id} class={(i === 0) ? "tab-pane fade show" : "tab-pane fade"} id={"nav-lang" + data.id} role="tabpanel" aria-labelledby={"nav-lang" + data.id + "-tab"}>
            //                 <div name="card_success" class="card shadow h-100 py-2">
            //                     <div class="card-body row row-cols-1">
            //                         {data.note_content}
            //                     </div>
            //                 </div>
            //             </div>
            //         ))}
            //     </div>
            // </div>
        )
    }
}
  
export default Language;