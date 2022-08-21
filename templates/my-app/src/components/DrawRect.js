import React from 'react';
import axios from 'axios';
import { Button, Card, Container, Row, Col, Form } from 'react-bootstrap';
import CanvasDraw from "react-canvas-draw";
const geometric = require("geometric");


function rect(props) {
    const { ctx, x, y, width, height } = props;
    ctx.strokeStyle = 'red'
    ctx.strokeRect(x, y, width, height);
}

function text(props) {
    const { ctx, word, x, y } = props;
    ctx.font = "20px Arial";
    ctx.fillStyle = "red";
    ctx.fillText(word, x, y);
}

class DrawRect extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            request_data: this.props.request_data,
            api_location: this.props.api_location,
            order_processes_reprocess_position_code: '',
            canvasWidth: 440,
            canvasHeight: 440,
            prex: 0,
            prey: 0,
            machine_data: [],
            point_list: [],
            mouseDown: false,
            isRect: false,
            drawCheck: false,
            drawBtnName: '畫圖',
            displayCheck: false,
            displayBtnName: '全部顯示',
            brushColor: "#ff0000",
            canvasZIndex: 2,
            drawZIndex: 3,
        }
        this.canvasRef = React.createRef();
        this.upRef = React.createRef();
        this.downRef = React.createRef();
    }

    componentWillUnmount() {
        window.removeEventListener("resize", this.updateCanvas);
    }

    setImg() {

    }
    componentDidUpdate(prevProps) {
        console.log(this.state.request_data, prevProps.request_data)

        if (this.state.request_data !== prevProps.request_data) {
            let ratio;
            this.setState({
                request_data: prevProps.request_data,
                api_location: prevProps.api_location,
            });
            const ctx_down = this.downRef.current.getContext('2d');
            var background = new Image();
            background.src = prevProps.request_data.background_src;
            background.onload = () => {
                ratio = this.upRef.current.offsetWidth / background.width;
                this.setState({
                    canvasWidth: this.upRef.current.offsetWidth,
                    canvasHeight: background.height * ratio
                })
                ctx_down.drawImage(background, 0, 0, this.state.canvasWidth, this.state.canvasHeight);
            }
            let point_list = [];
            let cur_point_list = [];
            let prepoint = [];
            let point = [];

            axios.get(`${prevProps.api_location}`, {
                params: prevProps.request_data
            })
                .then((response) => {
                    let cur_machine_data = [];
                    if (this.props.drawRectArea) {
                        response.data.map((value, index) => {
                            point = []
                            prepoint = []
                            cur_point_list = []
                            point_list = []
                            prepoint.push(value.point_1_x, value.point_1_y)
                            point.push(value.point_2_x, value.point_2_y)
                            cur_point_list.push(prepoint)
                            cur_point_list.push(point)
                            point_list.push(cur_point_list)
                            cur_machine_data.push({
                                order_processes_reprocess_position_id: value.order_processes_reprocess_position_id,
                                order_processes_reprocess_position_code: value.order_processes_reprocess_position_code,
                                point_list: point_list,
                                canvas_width: value.canvas_width,
                                canvas_height: value.canvas_height
                            })
                        })
                    } else {
                        response.data.map((value, index) => {
                            point = []
                            prepoint = []
                            cur_point_list = []
                            point_list = []
                            prepoint.push(parseInt(value.point_list[0][0], 10), parseInt(value.point_list[0][1], 10))
                            point.push(parseInt(value.point_list[1][0], 10), parseInt(value.point_list[1][1], 10))
                            cur_point_list.push(prepoint)
                            cur_point_list.push(point)
                            cur_machine_data.push({
                                position_id: value.position_id,
                                attach_file_position_code: value.attach_file_position_code,
                                attach_file_id: prevProps.request_data.attach_file_id,
                                point_list: cur_point_list,
                                canvas_width: value.canvas_width,
                                canvas_height: value.canvas_height
                            })
                        })
                    }
                    this.setState({
                        machine_data: cur_machine_data
                    })
                })
                .catch((error) => console.log(error))

            window.addEventListener("resize", this.updateCanvas);
        }
    }

    updateCanvas = (e) => {
        this.setState({
            canvasWidth: window.innerWidth * 0.3
        })
        let ratio;
        const ctx_down = this.downRef.current.getContext('2d');
        var background = new Image();
        background.src = this.state.background_src;
        background.onload = () => {
            ratio = this.upRef.current.offsetWidth / background.width;
            this.setState({
                canvasWidth: this.upRef.current.offsetWidth,
                canvasHeight: background.height * ratio
            })
            ctx_down.drawImage(background, 0, 0, this.state.canvasWidth, this.state.canvasHeight);
        }
    }

    handleMouseDown = (e) => {
        if (this.state.drawCheck) {
            this.setState({
                mouseDown: true,
                prex: e.nativeEvent.layerX,
                prey: e.nativeEvent.layerY
            })
        }
    }

    handleMouseMove = (e) => {
        if (this.state.drawCheck && this.state.mouseDown) {
            const ctx = this.upRef.current.getContext('2d');
            ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            let prex = this.state.prex;
            let prey = this.state.prey;
            let px = e.nativeEvent.layerX;
            let py = e.nativeEvent.layerY;
            rect({ ctx, x: prex, y: prey, width: px - prex, height: py - prey })
            this.setState({
                isRect: true
            })
        }
    }

    handleMouseUp = (e) => {
        if (this.state.drawCheck && this.state.isRect) {
            let overlap = false;
            let point_list = [];
            let cur_point_list = [];
            let result_list = [];
            let polygonA = [];
            let polygonB = [];
            let cur_machine_data = this.state.machine_data;
            cur_point_list.push(this.state.prex, this.state.prey)
            point_list.push(cur_point_list)
            cur_point_list = []
            cur_point_list.push(e.nativeEvent.layerX, e.nativeEvent.layerY)
            point_list.push(cur_point_list)
            result_list.push(point_list)
            polygonA.push(
                [this.state.prex, this.state.prey],
                [e.nativeEvent.layerX, this.state.prey],
                [e.nativeEvent.layerX, e.nativeEvent.layerY],
                [this.state.prex, e.nativeEvent.layerY]
            )
            this.state.machine_data.map((mvalue, mindex) => {
                mvalue.point_list.map((pvalue, pindex) => {
                    polygonB = [];
                    polygonB.push(
                        [pvalue[0][0], pvalue[0][1]],
                        [pvalue[1][0], pvalue[0][1]],
                        [pvalue[1][0], pvalue[1][1]],
                        [pvalue[0][0], pvalue[1][1]]
                    )
                    let polygonInPolygon = geometric.polygonInPolygon(polygonA, polygonB)
                    let polygonIntersectsPolygon = geometric.polygonIntersectsPolygon(polygonA, polygonB)
                    if (polygonInPolygon || polygonIntersectsPolygon) {
                        overlap = true;
                    }
                    console.log(overlap, mindex + 1)
                })
            })

            if (!overlap) {
                let request_data = [];

                if (this.state.request_data.order_processes_reprocess_subfile_id !== undefined) {
                    request_data = [{
                        order_processes_reprocess_subfile_id: parseInt(this.state.request_data.order_processes_reprocess_subfile_id, 10),
                        canvas_width: parseInt(this.state.canvasWidth, 10),
                        canvas_height: parseInt(this.state.canvasHeight, 10),
                        point_list: point_list
                    }]
                } else if (!this.state.drawRectArea) {
                    request_data.push({
                        canvas_width: parseInt(this.state.canvasWidth, 10),
                        canvas_height: parseInt(this.state.canvasHeight, 10),
                        attach_file_id: this.state.request_data.attach_file_id,
                        point_list: point_list
                    })
                } else {
                    request_data.push({
                        canvas_width: parseInt(this.state.canvasWidth, 10),
                        canvas_height: parseInt(this.state.canvasHeight, 10),
                        point_list: point_list
                    })
                }




                axios.post(`${this.state.api_location}`,
                    request_data
                )
                    .then((response) => {
                        if (this.state.drawRectArea) {
                            cur_machine_data.push({
                                order_processes_reprocess_position_id: parseInt(response['data'].length !== 0 ? response['data']['data'] : 0),
                                canvas_width: parseInt(this.state.canvasWidth, 10),
                                canvas_height: parseInt(this.state.canvasHeight, 10),
                                point_list: result_list
                            })
                        } else {
                            cur_machine_data.push({
                                position_id: parseInt(response['data'].length !== 0 ? response['data']['position_id'] : 0),
                                attach_file_position_id: parseInt(response['data'].length !== 0 ? response['data']['attach_file_position_id'] : 0),
                                canvas_width: parseInt(this.state.canvasWidth, 10),
                                canvas_height: parseInt(this.state.canvasHeight, 10),
                                point_list: result_list[0],
                                attach_file_id: this.state.request_data.attach_file_id
                            })
                        }

                        this.setState({
                            machine_data: cur_machine_data
                        })
                        if (this.props.drawRectArea) {
                            if (!this.props.same_pic) {
                                this.props.resetRec(this.state.request_data);
                            }
                        }
                    })
                    .catch((error) => console.log(error))
            } else {
                alert('overlaped')
                const ctx = this.upRef.current.getContext('2d');
                ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            }
        }
        this.setState({
            mouseDown: false,
            isRect: false
        })
    }

    drawRect = (e) => {
        const ctx = this.upRef.current.getContext('2d');
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
        if (this.state.drawCheck) {
            this.setState({
                drawCheck: false,
                drawBtnName: '畫圖',
                displayCheck: false,
                displayBtnName: '全部顯示',
                canvasZIndex: 2,
                drawZIndex: 3,
            })
            Object.assign(e.target.style, { width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" });

        } else {
            this.setState({
                drawCheck: true,
                drawBtnName: '畫圖中...',
                displayCheck: false,
                displayBtnName: '全部顯示',
                canvasZIndex: 3,
                drawZIndex: 2,
            })
            Object.assign(e.target.style, { width: 'auto', background: "#5e789f", color: "white", borderColor: "#5e789f" });

            if (this.props.upload_crop !== undefined) {
                this.props.upload_crop(this.state);
            }
        }
    }

    deleteBtn = (e) => {
        const ctx = this.upRef.current.getContext('2d');
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
        let cur_machine_data = this.state.machine_data
        if (this.props.drawRectArea) {
            axios.delete(`${this.state.api_location}`, {
                data: {
                    order_processes_reprocess_position_id: parseInt(this.state.machine_data[e.target.id]['order_processes_reprocess_position_id'], 10)
                }
            })
                .then((response) => {
                    cur_machine_data.splice(e.target.id, 1)
                    this.setState({
                        machine_data: cur_machine_data
                    })
                })
                .catch((error) => console.log(error))
        } else {
            axios.delete(`${this.state.api_location}`, {
                data: [{
                    position_id: parseInt(this.state.machine_data[e.target.id]['position_id'], 10)
                }]
            })
                .then((response) => {
                    cur_machine_data.splice(e.target.id, 1)
                    this.setState({
                        machine_data: cur_machine_data
                    })
                })
                .catch((error) => console.log(error))
        }
    }
    getDrawCanvasData = (e) => {
        let canvas = this.canvasRef.canvasContainer.childNodes[1]
        console.log(canvas)
        return canvas;
    }
    drawBtn = (e) => {
        const ctx = this.upRef.current.getContext('2d');
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
        this.state.machine_data.map((mvalue, mindex) => {
            mvalue.point_list.map((pvalue, pindex) => {
                if (e.target.id == mindex) {
                    rect({ ctx, x: pvalue[0][0], y: pvalue[0][1], width: pvalue[1][0] - pvalue[0][0], height: pvalue[1][1] - pvalue[0][1] })
                    text({ ctx, word: mvalue.order_processes_reprocess_position_code ? mvalue.order_processes_reprocess_position_code : '', x: pvalue[0][0], y: pvalue[0][1] - 2 })
                }
            })
        })
    }

    displayAll = (e) => {
        if (!this.state.displayCheck) {
            const ctx = this.upRef.current.getContext('2d');
            ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            if (this.state.drawRectArea) {
                this.state.machine_data.map((mvalue, mindex) => {
                    mvalue.point_list.map((pvalue, pindex) => {
                        rect({ ctx, x: pvalue[0][0], y: pvalue[0][1], width: pvalue[1][0] - pvalue[0][0], height: pvalue[1][1] - pvalue[0][1] })
                        text({ ctx, word: mvalue.order_processes_reprocess_position_code ? mvalue.order_processes_reprocess_position_code : '', x: pvalue[0][0], y: pvalue[0][1] - 2 })
                    })
                })
            } else {
                this.state.machine_data.map((mvalue, mindex) => {

                    rect({ ctx, x: mvalue.point_list[0][0], y: mvalue.point_list[0][1], width: mvalue.point_list[1][0] - mvalue.point_list[0][0], height: mvalue.point_list[1][1] - mvalue.point_list[0][1] })
                    text({ ctx, word: mvalue.attach_file_position_code ? mvalue.attach_file_position_code : '', x: mvalue.point_list[0][0], y: mvalue.point_list[0][1] - 2 })

                })
            }

            this.setState({
                displayCheck: true,
                displayBtnName: '取消顯示'
            })
        } else {
            const ctx = this.upRef.current.getContext('2d');
            ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            this.setState({
                displayCheck: false,
                displayBtnName: '全部顯示'
            })
        }
    }

    onFormControlChange = (e) => {
        if (this.props.drawRectArea) {
            let cur_machine_data = this.state.machine_data
            cur_machine_data[e.target.getAttribute('index')]['order_processes_reprocess_position_code'] = e.target.value
            this.setState({
                machine_data: cur_machine_data
            })
        } else {
            let cur_machine_data = this.state.machine_data
            cur_machine_data[e.target.getAttribute('index')]['attach_file_position_code'] = e.target.value

            this.setState({
                machine_data: cur_machine_data
            })
        }
    }

    updateBtn = (e) => {
        console.log(this.state.machine_data)
        axios.patch(`${this.state.api_location}`,
            this.state.machine_data
        )
            .then((response) => console.log(response))
            .catch((error) => console.log(error))
    }

    setColor = (e) => {
        this.setState({
            brushColor: e.target.value
        });
    }

    background_src_change() {
        this.setState({
            background_src: this.props.background_src,
        })
        this.updateCanvas()
    }

    render() {
        return (
            <Container fluid>
                <Row>
                    <Col md={5}>
                        <Row className='mt-2'>
                            <Col style={{ position: 'relative' }}>
                                <canvas
                                    ref={this.downRef}
                                    style={{
                                        zIndex: 1
                                    }}
                                    width={(this.state.canvasWidth)}
                                    height={(this.state.canvasHeight)}
                                />
                                <canvas
                                    ref={this.upRef}
                                    style={{
                                        backgroundColor: 'transparent',
                                        position: 'absolute',
                                        left: 12,
                                        top: 0,
                                        zIndex: this.state.canvasZIndex,
                                        border: "1px solid #a39e9e",
                                    }}
                                    width={(this.state.canvasWidth)}
                                    height={(this.state.canvasHeight)}
                                    onMouseDown={this.handleMouseDown.bind(this)}
                                    onMouseUp={this.handleMouseUp.bind(this)}
                                    onMouseMove={this.handleMouseMove.bind(this)}
                                />
                                {
                                    (!this.props.drawRectArea) ?
                                        <CanvasDraw
                                            ref={canvasDraw => (this.canvasRef = canvasDraw)}
                                            imgSrc={this.state.request_data.img_src}
                                            brushColor={this.state.brushColor}
                                            brushRadius={2}
                                            lazyRadius={2}
                                            hideGrid={true}
                                            enablePanAndZoom={true}
                                            canvasHeight={(this.state.canvasHeight)}
                                            canvasWidth={(this.state.canvasWidth)}
                                            id="canvas"
                                            style={{
                                                backgroundColor: 'transparent',
                                                position: 'absolute',
                                                left: 12,
                                                top: 0,
                                                zIndex: this.state.drawZIndex,
                                                display: "block"
                                            }}
                                        /> : ''
                                }

                            </Col>
                        </Row>
                        <Row className='mt-2'>
                            <Col md="12">
                                <Card style={{ padding: "0rem" }}>
                                    <Card.Header>畫框</Card.Header>
                                    <Card.Body>
                                        <Row>
                                            <Col>
                                                <Button variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.drawRect.bind(this)}>{this.state.drawBtnName}</Button>
                                                <Button variant="light" className="mx-4" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.displayAll.bind(this)}>{this.state.displayBtnName}</Button>
                                                <Button variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.updateBtn.bind(this)}>保存</Button>
                                            </Col>
                                        </Row>
                                    </Card.Body>
                                </Card>
                            </Col>
                        </Row>
                        {
                            (!this.props.drawRectArea) ?
                                <Row className='mt-2'>
                                    <Col md="12">
                                        <Card style={{ padding: "0rem" }}>
                                            <Card.Header>畫筆</Card.Header>
                                            <Card.Body>
                                                <Row>
                                                    <Col md="auto">
                                                        <Form.Control
                                                            type="color"
                                                            id="exampleColorInput"
                                                            defaultValue={this.state.brushColor}
                                                            title="Choose your color"
                                                            onChange={this.setColor.bind(this)}
                                                        />
                                                    </Col>
                                                    <Col>
                                                        <Button className="mx-2" variant="light" onClick={() => { this.canvasRef.undo(); }} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>上一步</Button>
                                                        <Button className="mx-2" variant="light" onClick={() => { this.canvasRef.eraseAll(); }} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>清空</Button>
                                                        <Button className="mx-2" variant="light" onClick={this.props.handleCanvasChange} style={{ width: 'auto', background: "#5e789f", color: "white", borderColor: "#5e789f", borderWidth: "medium", fontWeight: "bold" }}>儲存劃記</Button>

                                                    </Col>
                                                </Row>
                                            </Card.Body>
                                        </Card>
                                    </Col>
                                </Row>
                                : ''
                        }
                    </Col>
                    <Col style={{ display: 'absolute', right: 'auto', left: 'auto' }} md='7'>
                        {
                            (!this.props.drawRectArea) ?
                                <Row className="my-1" >
                                    {this.state.machine_data.map((mvalue, mindex) => {
                                        return (
                                            <Col key={mindex} md="4" className="mt-2">
                                                <Card style={{ width: '15rem' }}>
                                                    <Card.Header>
                                                        <button type="button" className="btn-close" aria-label="Close" id={mindex} onClick={this.deleteBtn.bind(this)}></button>
                                                        <Card.Text className='text-center'>
                                                            <Button key={mindex} id={mindex} variant='outline-secondary' onClick={this.drawBtn.bind(this)}>{mindex + 1}</Button>
                                                        </Card.Text>
                                                    </Card.Header>
                                                    <Card.Body className='text-center'>
                                                        <Form>
                                                            <Form.Control placeholder="名稱" index={mindex} value={mvalue.attach_file_position_code} onChange={this.onFormControlChange.bind(this)} />
                                                        </Form>
                                                    </Card.Body>
                                                </Card>
                                            </Col>
                                        )
                                    })}
                                </Row> : ''
                        }
                    </Col>
                </Row>
                {
                    (this.props.drawRectArea) ?
                        <Row className="my-1" xs='auto' sm={2} md={3} lg={4} xl='auto'>
                            {this.state.machine_data.map((mvalue, mindex) => {
                                return (
                                    <Col key={mindex} className="mt-2">
                                        <Card style={{ width: '15rem' }}>
                                            <Card.Header>
                                                <button type="button" className="btn-close" aria-label="Close" id={mindex} onClick={this.deleteBtn.bind(this)}></button>
                                                <Card.Text className='text-center'>
                                                    <Button key={mindex} id={mindex} variant='outline-secondary' onClick={this.drawBtn.bind(this)}>{mindex + 1}</Button>
                                                </Card.Text>
                                            </Card.Header>
                                            <Card.Body className='text-center'>
                                                <Form>
                                                    <Form.Control placeholder="名稱" index={mindex} disabled={this.state.formControlDisable} value={mvalue.order_processes_reprocess_position_code} onChange={this.onFormControlChange.bind(this)} />
                                                </Form>
                                            </Card.Body>
                                        </Card>
                                    </Col>
                                )
                            })}
                        </Row> : ''
                }
            </Container >
        );
    }
}

export default DrawRect;
