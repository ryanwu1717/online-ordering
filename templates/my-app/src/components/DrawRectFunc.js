import React from 'react';
import axios from 'axios';
import { Button, Card, Container, Row, Col, Form, InputGroup, FormControl } from 'react-bootstrap';
import CanvasDraw from "react-canvas-draw";
const geometric = require("geometric");


function rect(props) {
    const { ctx, x, y, width, height } = props;
    ctx.strokeStyle = 'red'
    ctx.strokeRect(x, y, width, height);
}

function text(props) {
    let { ctx, word, x, y } = props;
    if (word.attach_file_position_code !== undefined || word.order_processes_position_code !== undefined) {
        if (word.attach_file_position_code) {
            word = word.attach_file_position_code;
        } else if (word.order_processes_position_code) {
            word = word.order_processes_position_code;
        } else {
            word = ''
        }
    } else {
        word = '';
    }
    ctx.font = "17px Arial";
    ctx.fillStyle = "red";
    ctx.fillText(word, x, y);
}

function minxy(props) {
    const { point_list } = props;
    let x = 0
    let y = 0
    if (parseInt(point_list[0][0]) < parseInt(point_list[1][0])) {
        x = parseInt(point_list[0][0])
    } else {
        x = parseInt(point_list[1][0])
    }
    if (parseInt(point_list[0][1]) < parseInt(point_list[1][1])) {
        y = parseInt(point_list[0][1])
    } else {
        y = parseInt(point_list[1][1])
    }
    return { x: x, y: y }
}

class DrawRectFunc extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            canvasWidth: 670,
            canvasHeight: 670,
            prex: 0,
            prey: 0,
            machine_data: [],
            mouseDown: false,
            isRect: false,
            drawCheck: false,
            displayCheck: false,
            displayBtnName: '全部顯示',
            drawed: false,
            drawedName: '展示筆跡',
            brushColor: "#ff0000",
            canvasZIndex: 2,
            drawZIndex: 3,
            modal: {
                modal_body: "保存成功",
                show: false,
                size: 'sm'
            }
        }
        this.canvasRef = React.createRef();
        this.upRef = React.createRef();
        this.midRef = React.createRef();
        this.downRef = React.createRef();
        this.drawBtnRef = React.createRef();
        this.subfileCodeArea = React.createRef();
        this.order_processes_subfile_draw = React.createRef();
        this.order_processes_subfile_tech = React.createRef();
        this.callbackReturn = this.callbackReturn.bind(this);
    }

    componentWillUnmount() {
        window.removeEventListener("resize", this.updateCanvas);
    }

    componentDidUpdate(prevProps) {
        if ((JSON.stringify(this.state.machine_data) !== JSON.stringify(this.props.request_data)) || (prevProps.background_src !== this.props.background_src)) {
            this.updateCanvas()
            this.setState({
                machine_data: this.props.request_data
            })
            if (this.props.note !== undefined) {
                this.order_processes_subfile_draw.current.value = this.props.note.order_processes_subfile_draw;
                this.order_processes_subfile_tech.current.value = this.props.note.order_processes_subfile_tech;
            } else if (this.order_processes_subfile_draw.current !== null || this.order_processes_subfile_tech.current !== null) {

                this.order_processes_subfile_draw.current.value = '';
                this.order_processes_subfile_tech.current.value = '';
            }
            window.addEventListener("resize", this.updateCanvas);
        }
    }

    updateCanvas = (e) => {
        let ratio;
        const ctx_down = this.downRef.current.getContext('2d');
        var background = new Image();
        background.src = this.props.background_src;
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
                prey: e.nativeEvent.layerY,
            })
        }
    }

    handleMouseMove = (e) => {
        if (this.state.drawCheck && this.state.mouseDown) {
            let prex = this.state.prex
            let prey = this.state.prey
            let px = e.nativeEvent.layerX
            let py = e.nativeEvent.layerY
            const ctx = this.upRef.current.getContext('2d');
            ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
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
            let polygonA = [];
            let polygonB = [];
            let cur_machine_data = {};
            cur_point_list.push(this.state.prex, this.state.prey)
            point_list.push(cur_point_list)
            cur_point_list = []
            cur_point_list.push(e.nativeEvent.layerX, e.nativeEvent.layerY)
            point_list.push(cur_point_list)
            polygonA.push(
                [this.state.prex, this.state.prey],
                [e.nativeEvent.layerX, this.state.prey],
                [e.nativeEvent.layerX, e.nativeEvent.layerY],
                [this.state.prex, e.nativeEvent.layerY]
            )
            console.log(this.state.machine_data)
            this.state.machine_data.map((mvalue, mindex) => {
                polygonB = [];
                polygonB.push(
                    [mvalue.point_list[0][0], mvalue.point_list[0][1]],
                    [mvalue.point_list[1][0], mvalue.point_list[0][1]],
                    [mvalue.point_list[1][0], mvalue.point_list[1][1]],
                    [mvalue.point_list[0][0], mvalue.point_list[1][1]]
                )
                let polygonInPolygon = geometric.polygonInPolygon(polygonA, polygonB)
                let polygonIntersectsPolygon = geometric.polygonIntersectsPolygon(polygonA, polygonB)
                if (polygonInPolygon || polygonIntersectsPolygon) {
                    overlap = true;
                }
                console.log(overlap, mindex + 1)
            })
            if (!overlap) {
                cur_machine_data = {
                    canvas_width: parseInt(this.state.canvasWidth, 10),
                    canvas_height: parseInt(this.state.canvasHeight, 10),
                    point_list: point_list
                }
                this.props.handlePostRect(cur_machine_data)
                const upctx = this.upRef.current.getContext('2d');
                const ctx = this.midRef.current.getContext('2d');
                upctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
                rect({ ctx, x: point_list[0][0], y: point_list[0][1], width: point_list[1][0] - point_list[0][0], height: point_list[1][1] - point_list[0][1] })
                let min = minxy({ point_list: point_list })
                text({ ctx, word: '', x: min.x, y: min.y - 2 })
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
        if (this.state.drawCheck) {
            this.setState({
                drawCheck: false,
                canvasZIndex: 2,
                drawZIndex: 3,
            })
            Object.assign(e.target.style, { width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" });
        } else {
            this.setState({
                drawCheck: true,
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
        const midctx = this.midRef.current.getContext('2d');
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
        midctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
        this.props.handleDeleteRect(e.target.id)
        this.setState({
            displayCheck: false,
            displayBtnName: '全部顯示'
        })
    }

    getDrawCanvasData = (e) => {
        let canvas = this.canvasRef.canvasContainer.childNodes[1]
        console.log(canvas)
        return canvas;
    }

    drawBtn = (e) => {
        let min
        const ctx = this.upRef.current.getContext('2d');
        const midctx = this.midRef.current.getContext('2d');
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
        midctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
        this.state.machine_data.map((mvalue, mindex) => {
            if (e.target.id == mindex) {
                rect({ ctx, x: mvalue.point_list[0][0], y: mvalue.point_list[0][1], width: mvalue.point_list[1][0] - mvalue.point_list[0][0], height: mvalue.point_list[1][1] - mvalue.point_list[0][1] })
                min = minxy({ point_list: mvalue.point_list })
                text({ ctx, word: mvalue, x: min.x, y: min.y - 2 })
            }
        })
    }

    displayAll = (e) => {
        if (!this.state.displayCheck) {
            let min
            const upctx = this.upRef.current.getContext('2d');
            const ctx = this.midRef.current.getContext('2d');
            upctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            this.state.machine_data.map((mvalue, mindex) => {
                rect({ ctx, x: mvalue.point_list[0][0], y: mvalue.point_list[0][1], width: mvalue.point_list[1][0] - mvalue.point_list[0][0], height: mvalue.point_list[1][1] - mvalue.point_list[0][1] })
                min = minxy({ point_list: mvalue.point_list })
                text({ ctx, word: mvalue, x: min.x, y: min.y - 2 })
            })
            this.setState({
                displayCheck: true,
                displayBtnName: '取消顯示'
            })
        } else {
            const upctx = this.upRef.current.getContext('2d');
            const ctx = this.midRef.current.getContext('2d');
            upctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            this.setState({
                displayCheck: false,
                displayBtnName: '全部顯示'
            })
        }
    }
    onFormControlChange = (e) => {
        let cur_machine_data = this.state.machine_data
        cur_machine_data[e.target.getAttribute('index')]['attach_file_position_code'] = e.target.value
        cur_machine_data[e.target.getAttribute('index')]['order_processes_position_code'] = e.target.value
        cur_machine_data[e.target.getAttribute('index')]['order_processes_position_id'] = e.target.getAttribute('id')
        this.setState({
            machine_data: cur_machine_data
        })
    }
    ontextAreaChange = (e) => {
        let change_arr = [];
        let obj = {};
        obj[this.order_processes_subfile_draw.current.getAttribute('name')] = this.order_processes_subfile_draw.current.value;
        obj[this.order_processes_subfile_tech.current.getAttribute('name')] = this.order_processes_subfile_tech.current.value;
        change_arr.push(obj)
        this.props.change_opr_subfile_code(change_arr);
    }

    updateBtn = (e) => {
        this.props.handleUpdateRect(this.state.machine_data)
        this.props.handleCanvasChange()
    }

    showDrawed = (e) => {
        if (this.state.drawed) {
            this.setState({
                drawed: false,
                drawedName: '展示筆跡'
            })
            this.props.handleShowOrigin()
        } else {
            this.setState({
                drawed: true,
                drawedName: '看原圖'
            })

            this.props.handleShowPaint()
        }
    }

    setColor = (e) => {
        this.setState({
            brushColor: e.target.value
        });
    }

    colorClick = (e) => {
        this.setState({
            drawCheck: false,
            canvasZIndex: 2,
            drawZIndex: 3,
        })
        Object.assign(this.drawBtnRef.current.style, { width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" });
    }

    background_src_change() {
        this.setState({
            background_src: this.props.background_src,
        })
        this.updateCanvas()
    }

    callbackReturn(modal) {
        let modal_temp = modal;
        this.setState({
            modal: modal_temp,
        })

        setTimeout(function () {
            modal_temp['show'] = false;
            this.setState({
                modal: modal_temp,
            })

        }.bind(this), 1000)
    }

    render() {
        return (
            <Container fluid>
                {(!this.props.drawRectArea) ?
                    <Row className='mt-2'>
                        <Col xs="auto" >
                            <Button ref={this.drawBtnRef} variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.drawRect.bind(this)}>文字框</Button>
                        </Col>
                        <Col xs="auto" >
                            <Button variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.displayAll.bind(this)}>{this.state.displayBtnName}</Button>
                        </Col>
                        <Col xs="1" >
                            <Form.Control
                                type="color"
                                id="exampleColorInput"
                                defaultValue={this.state.brushColor}
                                title="Choose your color"
                                onChange={this.setColor.bind(this)}
                                onClick={this.colorClick.bind(this)}
                            />
                        </Col>
                        <Col xs="auto" >
                            <Button variant="light" onClick={() => { this.canvasRef.undo(); }} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>上一步</Button>
                        </Col>
                        <Col xs="auto" >
                            <Button variant="light" onClick={() => { this.canvasRef.eraseAll(); }} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>清空</Button>
                        </Col>
                        <Col xs="auto" >
                            <Button variant="light" onClick={this.showDrawed.bind(this)} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>{this.state.drawedName}</Button>
                        </Col>
                        <Col xs="auto" >
                            <Button variant="light" onClick={this.updateBtn.bind(this)} style={{ width: 'auto', background: "#5e789f", color: "white", borderColor: "#5e789f", borderWidth: "medium", fontWeight: "bold" }}>保存</Button>
                        </Col>
                    </Row>
                    : ''
                }
                <Row className='mt-3'>
                    <Col style={{ position: 'relative' }} xs='7' md='8' lg='9'>
                        <canvas
                            ref={this.downRef}
                            style={{
                                zIndex: 1,
                                objectFit: 'contain',
                                width: '98%',
                                height: '98%'
                            }}
                            width={(this.state.canvasWidth)}
                            height={(this.state.canvasHeight)}
                        />
                        <canvas
                            ref={this.midRef}
                            style={{
                                backgroundColor: 'transparent',
                                position: 'absolute',
                                left: 12,
                                top: 0,
                                zIndex: 1,
                                objectFit: 'contain',
                                width: '98%',
                                height: '98%'
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
                                objectFit: 'contain',
                                width: '98%',
                                height: '98%'
                            }}
                            width={(this.state.canvasWidth)}
                            height={(this.state.canvasHeight)}
                            onMouseDown={this.handleMouseDown.bind(this)}
                            onMouseUp={this.handleMouseUp.bind(this)}
                            onMouseMove={this.handleMouseMove.bind(this)}
                        />
                        <CanvasDraw
                            ref={canvasDraw => (this.canvasRef = canvasDraw)}
                            imgSrc={this.state.img_src}
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
                                display: "block",
                                width: '98%',
                                height: '98%'
                            }}
                        />
                    </Col>
                    {(!this.props.drawRectArea) ?
                        <Col xs='5' md='4' lg='3' style={{ height: this.state.canvasHeight * 0.96, overflowY: 'scroll' }}>
                            <Row xs='auto' lg={1} xl={2} xxl='auto'>
                                {this.state.machine_data.map((mvalue, mindex) => {
                                    return (
                                        <Col key={mindex} className="mb-2">
                                            <Card>
                                                <Card.Body className='text-center'>
                                                    <Form className='row'>
                                                        <Col className='col-2'>
                                                            <Button key={mindex} id={mindex} variant='outline-secondary' onClick={this.drawBtn.bind(this)}>{mindex + 1}</Button>
                                                        </Col>
                                                        <Col className='col-8'>
                                                            <Form.Control className="form-control" placeholder="名稱" index={mindex} value={mvalue.attach_file_position_code} onChange={this.onFormControlChange.bind(this)} />
                                                        </Col>
                                                        <Col className='mt-2 col-2'>
                                                            <button type="button" className="btn-close pr-3" aria-label="Close" id={mindex} onClick={this.deleteBtn.bind(this)}></button>
                                                        </Col>
                                                    </Form>
                                                </Card.Body>
                                            </Card>
                                        </Col>
                                    )
                                })}
                            </Row>
                        </Col> : ''
                    }
                </Row>
                {
                    (this.props.drawRectArea) ?
                        <>
                            <Row className='mt-2 mb-3'>
                                <Col xs="auto" >
                                    <Button ref={this.drawBtnRef} variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.drawRect.bind(this)}>文字框</Button>
                                </Col>
                                <Col xs="auto" >
                                    <Button variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.displayAll.bind(this)}>{this.state.displayBtnName}</Button>
                                </Col>
                                <Col xs="auto">
                                    <Form.Control
                                        type="color"
                                        id="exampleColorInput"
                                        defaultValue={this.state.brushColor}
                                        title="Choose your color"
                                        onChange={this.setColor.bind(this)}
                                        onClick={this.colorClick.bind(this)}
                                        width="40px"
                                    />
                                </Col>
                                <Col xs="auto" >
                                    <Button variant="light" onClick={() => { this.canvasRef.undo(); }} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>上一步</Button>
                                </Col>
                                <Col xs="auto" >
                                    <Button variant="light" onClick={() => { this.canvasRef.eraseAll(); }} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>清空</Button>
                                </Col>
                                <Col xs="auto" >
                                    <Button variant="light" onClick={this.showDrawed.bind(this)} style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }}>{this.state.drawedName}</Button>
                                </Col>
                                <Col xs="auto" >
                                    <Button variant="light" onClick={this.updateBtn.bind(this)} style={{ width: 'auto', background: "#5e789f", color: "white", borderColor: "#5e789f", borderWidth: "medium", fontWeight: "bold" }}>保存</Button>
                                </Col>
                                <Col xs="auto" >
                                    {this.state.modal.show ? <Col style={{ color: "#B22222", fontWeight: "bold" }}>{this.state.modal.modal_body}</Col> : ''}
                                </Col>

                            </Row>
                            <Row className="mb-2">
                                <Col xs="auto" sm={6}>
                                    <Card>
                                        <Card.Body>
                                            <Form>
                                                <Form.Group className="mb-1" controlId="exampleForm.ControlTextarea1">
                                                    <Form.Label>製圖註記：</Form.Label>
                                                    <Form.Control as="textarea" rows={3} ref={this.order_processes_subfile_draw} name="order_processes_subfile_draw" onChange={this.ontextAreaChange.bind(this)} disabled={this.props.area_left_editable === true ? false : 'disabled'} />
                                                </Form.Group>
                                            </Form>
                                        </Card.Body>
                                    </Card>
                                </Col>
                                <Col xs="auto" sm={6}>
                                    <Card>
                                        <Card.Body>
                                            <Form>
                                                <Form.Group className="mb-1" controlId="exampleForm.ControlTextarea1">
                                                    <Form.Label>技術註記：</Form.Label>
                                                    <Form.Control as="textarea" rows={3} ref={this.order_processes_subfile_tech} name="order_processes_subfile_tech" onChange={this.ontextAreaChange.bind(this)} disabled={this.props.area_left_editable === true ? 'disabled' : false} />
                                                </Form.Group>
                                            </Form>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            </Row>
                            <Row xs='auto' lg={1} xxl='auto'>
                                {this.state.machine_data.map((mvalue, mindex) => {
                                    return (
                                        <Col key={`${mvalue.order_processes_position_id}${mindex}`} className="mb-2" style={{ overflowY: 'scroll', width: 350 }}>
                                            <Card>
                                                <Card.Body className='text-center'>
                                                    <Form className='row'>
                                                        <Col className='col-2'>
                                                            <Button key={`${mvalue.order_processes_position_id}${mindex}`} id={mindex} variant='outline-secondary' onClick={this.drawBtn.bind(this)}>{mindex + 1}</Button>
                                                        </Col>
                                                        <Col className='col-8'>
                                                            <Form.Control className="form-control" placeholder="名稱" index={mindex} id={mvalue.order_processes_position_id} value={mvalue.order_processes_position_code} onChange={this.onFormControlChange.bind(this)} />
                                                        </Col>
                                                        <Col className='mt-2 col-2'>
                                                            <button type="button" className="btn-close pr-3" aria-label="Close" id={mindex} onClick={this.deleteBtn.bind(this)}></button>
                                                        </Col>
                                                    </Form>
                                                </Card.Body>
                                            </Card>
                                        </Col>
                                    )
                                })}
                            </Row>
                        </>
                        : ''
                }
            </Container >
        );
    }
}

export default DrawRectFunc;
