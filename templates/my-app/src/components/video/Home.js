import Search from "./Search.js";
import News from "./News.js";
import UploadVideo from "./uploadVideo.js";
import CardPicture from "./CardPicture.js";
import ProductionManagementRemind from "./ProductionManagementRemind.js";
import Video from "./Video.js";
import Record from "./Record.js";
import Language from "./Language.js";
import Tab from "./Tab.js";

import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.min.js";
import Card from "react-bootstrap/Card";
import Row from "react-bootstrap/Row";
import Col from "react-bootstrap/Col";
import React from "react";

class Home extends React.Component {
  constructor() {
    super();
    this.state = {
      callVideo: false,
      tabRequest: "",
    };
    this.tabRef = React.createRef();
    this.scrollRef = React.createRef();
    this.searchRef = React.createRef();
    this.tabCallBack = this.tabCallBack.bind(this);
    this.searchCallBack = this.searchCallBack.bind(this);
  }
  tabCallBack(file_id) {
    this.setState({ callVideo: true });
    this.tabRef.current.getVideo(file_id);
    this.videoScroll();
  }
  videoScroll() {
    this.scrollRef.current.scrollIntoView({ behavior: "smooth" });
  }
  searchCallBack(search) {
    this.searchRef.current.getDatatbleRequire(search);
  }
  render() {
    return (
      <>
        <Row className="w-100 m-0 p-0">
          <Col md={12}>
            <Card className="shadow mb-5 w-100">
              <Card.Title md="12" as="h3" className="badge text-center m-0 p-0">
                <Row className="m-0">
                  <Col md={"auto"} className="title">
                    <span>數位典藏</span>
                  </Col>
                </Row>
              </Card.Title>
              <Card.Body md="12">
                <Row className="d-flex justify-content-start my-1">
                  <Col md={6}>
                    <Search passToParent={this.searchCallBack} />
                  </Col>
                  {/* <Col md={5}><News /></Col> */}
                </Row>
                <Row className="d-flex justify-content-start my-3">
                  <Col>
                    <Tab ref={this.searchRef} tabCallBack={this.tabCallBack} />
                  </Col>
                </Row>
                {/* <Row my={1}>
                    <Col md = {6}>
                      <CardPicture
                          title="客戶圖"
                      />
                    </Col>
                    <Col md = {6}>
                      <CardPicture
                          title="製程階層圖"
                      />
                    </Col>
                  </Row>

                    <hr />
                  <Row my={1}>
                    <Col md = {12}>
                      <ProductionManagementRemind />
                    </Col>
                  </Row> */}
                <hr />
                {this.state.callVideo ? (
                  <Row my={1} ref={this.scrollRef}>
                    <Video ref={this.tabRef} />
                    {/* <Col md = {6}>
                  <Record />
                </Col> */}
                  </Row>
                ) : (
                  <div></div>
                )}

                {/* <hr />
                  <Row my={1}>
                    <Col>
                      <Language />
                    </Col>
                  </Row>  */}
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </>
    );
  }
}

export default Home;
