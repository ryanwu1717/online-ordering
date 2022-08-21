import React from 'react';
import axios from 'axios';
import Axios from 'axios';
import ModalWithDataTable from './ModalWithDataTable'
import { Button, Card, Container, Row, Col, Form, InputGroup, FormControl, Toast } from 'react-bootstrap';
import { IoAddSharp } from 'react-icons/io5';
const geometric = require("geometric");

function line(props) {
    const { ctx, x, y, x1, y1 } = props;
    ctx.strokeStyle = 'red'
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.lineTo(x1, y1);
    ctx.lineWidth = 2;
    ctx.stroke();
}

class MapLocation extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            canvasWidth: 1000,
            canvasHeight: 800,
            canvasRatio: 1,
            preCanvasWidth: 0,
            mouseStatus: false,
            drawStatus: false,
            count: 0,
            sx: 0,
            sy: 0,
            prex: 0,
            prey: 0,
            pList: [],
            preList: [],
            machine_data: [],
            canvas_data: [],
            nowx: [],
            nowy: [],
            floor_columns: [],
            floor_data: [],
            upload: {
                allowType: ['image/jpg', 'image/jpeg', 'image/png', 'video/mp4'],
                API_location: "/RFID/floor_image",
            },
            preview: null,
            status: "將檔案拖放到這裡或點擊此處",
            source: Axios.CancelToken.source(),
            selected_floor_id: 0,
            address_columns: [],
            address_data: [],
            saveAntenna: [],
            status: [],
            modalTitle: '',
            selectedMindex: 0,
            displayBtnName: '全部顯示',
            displayCheck: false,
            canvasHidden: true,
            addressModalShow: false,
            floorModalShow: false,
            editMode: props.editMode,
            // editMode: true,
            datatableLoading: false,
            offsetLeft: null,
            offsetTop: null,
            addressEditCheck: '已保存',
            addressEditCheckColor: 'text-success',
            floorEditCheck: '已保存',
            floorEditCheckColor: 'text-success'
        };
        this.downRef = React.createRef()
        this.midRef = React.createRef()
        this.upRef = React.createRef()
        this.selectRef = React.createRef()
        this.fileInputRef = React.createRef();
        this.updateCanvas = this.updateCanvas.bind(this);
        this.onChangeAddressSelect = this.onChangeAddressSelect.bind(this)
        this.onChangeAntennaSelect = this.onChangeAntennaSelect.bind(this)
        this.onChangeStatus = this.onChangeStatus.bind(this)
        this.onImageChange = this.onImageChange.bind(this);
    }

    componentWillUnmount() {
        window.removeEventListener("resize", this.updateCanvas);
    }

    componentDidMount() {
        axios.get('/RFID/floor')
            .then(response => {
                let min_floor_id = 0
                response.data.map((value, index) => {
                    if (index === 0)
                        min_floor_id = value.floor_id
                    else {
                        if (value.floor_id < min_floor_id)
                            min_floor_id = value.floor_id
                    }
                })
                this.setState({
                    floor_data: response.data,
                })
                this.onChangeFloor({
                    target: {
                        value: min_floor_id
                    }
                })
            })

        axios.get('/RFID/address/detail')
            .then(response => {
                this.setState({
                    address_data: response.data
                })
            })

        axios.get('/RFID/address/detail')
            .then(response => {
                this.setState({
                    status_data: response.data
                })
            })

        this.setState({
            preCanvasWidth: this.downRef.current.offsetWidth,
            address_columns: [
                {
                    name: 'IP',
                    center: true,
                    cell: (row, index) =>
                        <Form className='row'>
                            <Col className='mt-2 col-2'>
                                <button type="button" className="btn-close" style={{ border: "1px solid #a39e9e" }} aria-label="Close" index={index} onClick={this.deleteAddress.bind(this)}></button>
                            </Col>
                            <Col className='col-8'>
                                <FormControl defaultValue={row.address} index={index} onChange={this.onChangeIP.bind(this)} />
                            </Col>
                        </Form>
                },
                {
                    name: '天線',
                    center: true,
                    cell: (row, index) =>
                        <div className='mt-2'>
                            {row.antennas.map((antenna, aindex) =>
                                <Form className='row' key={`${antenna.antenna_code}${aindex}${index}`}>
                                    {(antenna.antenna_id) ? (
                                        <>
                                            <Col className='mt-2 col-2'>
                                                <button type="button" className="btn-close" style={{ border: "1px solid #a39e9e" }} aria-label="Close" index={index} aindex={aindex} onClick={this.deleteAntenna.bind(this)}></button>
                                            </Col>
                                            <Col className='col-8'>
                                                <FormControl className='mb-2' defaultValue={antenna.antenna_code ? antenna.antenna_code : ''} rowindex={index} aindex={aindex} onChange={this.onChangeAntennaCode.bind(this)} />
                                            </Col>
                                        </>
                                    ) : ''}
                                </Form>
                            )}
                            <Col className='text-center mb-2'>
                                <button type="button" className="p-1" style={{ border: "1px solid #a39e9e", borderRadius: "5px", cursor: "pointer", color: "#696969", backgroundColor: 'white' }} ><IoAddSharp style={{ fontSize: "1.5em" }} index={index} onClick={this.addAntenna.bind(this)} /></button>
                            </Col>
                        </div>
                }
            ],
            floor_columns: [
                {
                    name: '樓層名稱',
                    center: true,
                    cell: (row, index) =>
                        <Form className='row'>
                            <Col className='mt-2 col-2'>
                                <button type="button" className="btn-close" style={{ border: "1px solid #a39e9e" }} aria-label="Close" index={index} onClick={this.deleteFloor.bind(this)}></button>
                            </Col>
                            <Col className='col-8'>
                                <FormControl defaultValue={row.floor_name} index={index} onChange={this.onChangeFloorName.bind(this)} />
                            </Col>
                        </Form>
                },
                {
                    name: '樓層圖片',
                    center: true,
                    cell: (row, index) =>
                        <Col>
                            <Row className='my-2'>
                                <img
                                    src={`${axios.defaults.baseURL}/RFID/floor_image/${row.floor_id}`}
                                    // src={`/RFID/floor_image/${row.floor_id}`}
                                    style={{ cursor: "pointer" }}
                                />
                            </Row>
                            <Row className='mb-2'>
                                <Button className="mx-2" variant="light" onClick={this.clickUpdateImg.bind(this)} style={{ width: 'auto', background: "#7B84A0", color: "white", }}>上傳檔案</Button>
                                <input style={{ display: 'none' }} type="file" ref={this.fileInputRef} floor_id={row.floor_id} index={index} onChange={(e) => this.onImageChange(e)} />
                            </Row>
                        </Col>
                }
            ]
        })
        window.addEventListener("resize", this.updateCanvas);
    }

    updateCanvas = (e) => {
        this.setState({
            offsetTop: this.downRef.current.offsetTop,
            offsetLeft: this.downRef.current.offsetLeft,
            width: this.downRef.current.offsetWidth,
            height: this.downRef.current.offsetHeight,
        });
        let floor_id = this.state.selected_floor_id
        let ratio;
        const ctx_down = this.downRef.current.getContext('2d');
        var background = new Image();
        background.src = `${axios.defaults.baseURL}/RFID/floor_image/${floor_id}`;
        // background.src = `/RFID/floor_image/${floor_id}`;
        let canvasRatio = this.state.preCanvasWidth / this.downRef.current.offsetWidth
        this.setState({
            canvasRatio: canvasRatio
        })
        background.onload = () => {
            ratio = this.downRef.current.offsetWidth / background.width;
            this.setState({
                canvasWidth: this.downRef.current.offsetWidth,
                canvasHeight: background.height * ratio,
            })
            ctx_down.drawImage(background, 0, 0, this.state.canvasWidth, this.state.canvasHeight);
        }
    }

    handleMouseDown(e) {
        if (this.state.drawStatus) {
            let cur_pList = this.state.pList;
            let cur_preList = this.state.preList;
            cur_pList.push({
                px: e.nativeEvent.offsetX * this.state.canvasRatio,
                py: e.nativeEvent.offsetY * this.state.canvasRatio
            })
            if (this.state.count > 3) {
                this.setState({
                    count: 1,
                    pList: cur_pList,
                    preList: cur_preList
                })
            } else {
                this.setState({
                    count: this.state.count + 1
                })
            }
            if (this.state.prex != 0 || this.state.prey != 0) {
                if (this.state.count === 1) {
                    this.setState({
                        prex: e.nativeEvent.offsetX * this.state.canvasRatio,
                        prey: e.nativeEvent.offsetY * this.state.canvasRatio,
                        sx: this.state.prex,
                        sy: this.state.prey
                    })
                    cur_preList.push({
                        px: this.state.prex,
                        py: this.state.prey
                    })
                } else if (this.state.count === 3) {
                    this.setState({
                        prex: 0,
                        prey: 0
                    })
                    cur_preList.push({
                        px: this.state.prex,
                        py: this.state.prey
                    })
                } else {
                    this.setState({
                        prex: e.nativeEvent.offsetX * this.state.canvasRatio,
                        prey: e.nativeEvent.offsetY * this.state.canvasRatio
                    })
                    cur_preList.push({
                        px: this.state.prex,
                        py: this.state.prey
                    })
                }
            } else {
                this.setState({
                    prex: e.nativeEvent.offsetX * this.state.canvasRatio,
                    prey: e.nativeEvent.offsetY * this.state.canvasRatio
                })
                cur_preList.push({
                    px: this.state.prex,
                    py: this.state.prey
                })
            }
        } else {
            let px = [];
            let py = [];
            let draw_x = 0, draw_y = 0;
            let canvas_width = 0, canvas_height = 0;
            let nowx = e.nativeEvent.offsetX * this.state.canvasRatio;
            let nowy = e.nativeEvent.offsetY * this.state.canvasRatio;
            this.state.machine_data.map((mvalue, mindex) => {
                canvas_width = mvalue.canvas_width;
                canvas_height = mvalue.canvas_height;
                mvalue.point.map((pvalue, pindex) => {
                    draw_x = this.state.canvasWidth / canvas_width
                    draw_y = this.state.canvasHeight / canvas_height
                    px.push(pvalue.px * draw_x)
                    py.push(pvalue.py * draw_y)
                })
                let r1 = this.getCross(px[0 + mindex * 4], py[0 + mindex * 4], px[1 + mindex * 4], py[1 + mindex * 4], nowx, nowy);
                let r2 = this.getCross(px[2 + mindex * 4], py[2 + mindex * 4], px[3 + mindex * 4], py[3 + mindex * 4], nowx, nowy);
                let r3 = this.getCross(px[1 + mindex * 4], py[1 + mindex * 4], px[2 + mindex * 4], py[2 + mindex * 4], nowx, nowy);
                let r4 = this.getCross(px[3 + mindex * 4], py[3 + mindex * 4], px[0 + mindex * 4], py[0 + mindex * 4], nowx, nowy);
                let r5 = r1 * r2
                let r6 = r3 * r4
                if (r5 >= 0 && r6 >= 0) {
                    console.log('success', this.state.machine_data[mindex]['machine_id'])
                    // this.props.mapLocationHandler(this.state.machine_data[mindex]['machine_id']);
                }
            })
        }
    }

    getCross(p1x, p1y, p2x, p2y, px, py) {
        return (p2x - p1x) * (py - p1y) - (px - p1x) * (p2y - p1y);
    }

    handleMouseUp(e) {
        if (this.state.drawStatus) {
            let overlap = false;
            if (this.state.count === 4) {
                let nowList = [];
                let cur_nowList = [];
                this.state.pList.map((pvalue, pindex) => {
                    cur_nowList = []
                    cur_nowList.push(pvalue.px, pvalue.py)
                    nowList.push(cur_nowList)
                })
                this.state.machine_data.map((mvalue, mindex) => {
                    let checkList = [];
                    let cur_checkList = [];
                    mvalue.point.map((pvalue, pindex) => {
                        cur_checkList = []
                        cur_checkList.push(pvalue.px, pvalue.py)
                        checkList.push(cur_checkList)
                    })
                    let polygonA = checkList
                    let polygonB = nowList
                    let polygonInPolygon = geometric.polygonInPolygon(polygonA, polygonB)
                    let polygonIntersectsPolygon = geometric.polygonIntersectsPolygon(polygonA, polygonB)
                    if (polygonInPolygon || polygonIntersectsPolygon) {
                        overlap = true;
                    }
                    console.log(overlap, mindex + 1)
                })
                let cur_machine_data = this.state.machine_data
                if (!overlap) {
                    axios.post('/RFID/position', {
                        floor_id: parseInt(this.state.selected_floor_id),
                        point_list: this.state.pList,
                        canvas_width: parseInt(this.state.canvasWidth),
                        canvas_height: parseInt(this.state.canvasHeight),
                    })
                        .then((response) => {
                            cur_machine_data.push({
                                machine_id: parseInt(response.data.data),
                                point: this.state.pList,
                                prepoint: this.state.preList,
                                canvas_width: parseInt(this.state.canvasWidth),
                                canvas_height: parseInt(this.state.canvasHeight)
                            })
                            const upctx = this.upRef.current.getContext('2d');
                            const ctx = this.midRef.current.getContext('2d');
                            upctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
                            let x = 0, y = 0, x1 = 0, y1 = 0;
                            this.state.pList.map((pvalue, pindex) => {
                                this.state.preList.map((prevalue, preindex) => {
                                    if (pindex == 0) {
                                        x = pvalue.px;
                                        y = pvalue.py;
                                    }
                                    if (pindex == 3) {
                                        x1 = pvalue.px;
                                        y1 = pvalue.py;
                                    }
                                    if (prevalue.px != 0 || prevalue.py != 0) {
                                        if (pindex == preindex) {
                                            line({ ctx, x: prevalue.px, y: prevalue.py, x1: pvalue.px, y1: pvalue.py });
                                        }
                                    }
                                })
                            })
                            line({ ctx, x: x, y: y, x1: x1, y1: y1 });
                            this.setState({
                                pList: [],
                                preList: [],
                                machine_data: cur_machine_data
                            })
                        })
                        .catch((error) => {
                            console.log(error)
                        })
                } else {
                    alert('overlaped')
                    const ctx = this.upRef.current.getContext('2d');
                    ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
                    this.setState({
                        pList: [],
                        preList: []
                    })
                }
            }
        }
    }

    handleMouseMove(e) {
        if (this.state.drawStatus) {
            if (this.state.prex != 0 || this.state.prey != 0) {
                const ctx = this.upRef.current.getContext('2d');
                ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
                this.state.pList.map((pvalue, pindex) => {
                    this.state.preList.map((prevalue, preindex) => {
                        if (pindex == preindex && prevalue.px != 0 && prevalue.py != 0) {
                            line({ ctx, x: prevalue.px, y: prevalue.py, x1: pvalue.px, y1: pvalue.py });
                        }
                    })
                })
                line({ ctx, x: this.state.prex, y: this.state.prey, x1: e.nativeEvent.offsetX * this.state.canvasRatio, y1: e.nativeEvent.offsetY * this.state.canvasRatio });
                if (this.state.count === 3) {
                    line({ ctx, x: this.state.sx, y: this.state.sy, x1: e.nativeEvent.offsetX * this.state.canvasRatio, y1: e.nativeEvent.offsetY * this.state.canvasRatio });
                }
            }
        }
    }

    deleteBtn = (e) => {
        let cur_machine_data = this.state.machine_data;
        axios.delete('/RFID/position', {
            data: {
                machine_id: this.state.machine_data[e.target.id]['machine_id']
            }
        })
            .then(response => {
                cur_machine_data.splice(e.target.id, 1);
                this.setState({
                    machine_data: cur_machine_data,
                    displayCheck: false,
                    displayBtnName: '全部顯示'
                })
                const ctx = this.upRef.current.getContext('2d');
                const midctx = this.midRef.current.getContext('2d');
                ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
                midctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
                this.displayAll()
            })
            .catch(error => console.log(error))
    }

    drawBtn(e) {
        const upctx = this.upRef.current.getContext('2d');
        const ctx = this.midRef.current.getContext('2d');
        upctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
        let x = 0, y = 0, x1 = 0, y1 = 0;
        let draw_x = 0, draw_y = 0;
        let canvas_width = 0, canvas_height = 0;
        this.state.machine_data.map((mvalue, mindex) => {
            if (e.target.id == mindex) {
                canvas_width = mvalue.canvas_width;
                canvas_height = mvalue.canvas_height;
                mvalue.point.map((pvalue, pindex) => {
                    draw_x = this.state.canvasWidth / canvas_width
                    draw_y = this.state.canvasHeight / canvas_height
                    mvalue.prepoint.map((prevalue, preindex) => {
                        if (pindex == 0) {
                            x = pvalue.px;
                            y = pvalue.py;
                        }
                        if (pindex == 3) {
                            x1 = pvalue.px;
                            y1 = pvalue.py;
                        }
                        if (prevalue.px != 0 || prevalue.py != 0) {
                            if (pindex == preindex) {
                                line({ ctx, x: prevalue.px * draw_x, y: prevalue.py * draw_y, x1: pvalue.px * draw_x, y1: pvalue.py * draw_y });
                            }
                        }
                    })
                })
                line({ ctx, x: x * draw_x, y: y * draw_y, x1: x1 * draw_x, y1: y1 * draw_y });
            }
        })
    }

    displayAll = (e) => {
        if (!this.state.displayCheck) {
            const upctx = this.upRef.current.getContext('2d');
            const ctx = this.midRef.current.getContext('2d');
            upctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight);
            this.setState({
                displayCheck: true,
                displayBtnName: '取消顯示'
            })
            let x = 0, y = 0, x1 = 0, y1 = 0;
            let draw_x = 0, draw_y = 0;
            let canvas_width = 0, canvas_height = 0;
            this.state.machine_data.map((mvalue, mindex) => {
                canvas_width = mvalue.canvas_width;
                canvas_height = mvalue.canvas_height;
                mvalue.point.map((pvalue, pindex) => {
                    draw_x = this.state.canvasWidth / canvas_width
                    draw_y = this.state.canvasHeight / canvas_height
                    mvalue.prepoint.map((prevalue, preindex) => {
                        if (pindex == 0) {
                            x = pvalue.px;
                            y = pvalue.py;
                        }
                        if (pindex == 3) {
                            x1 = pvalue.px;
                            y1 = pvalue.py;
                        }
                        if (prevalue.px != 0 || prevalue.py != 0) {
                            if (pindex == preindex) {
                                line({ ctx, x: prevalue.px * draw_x, y: prevalue.py * draw_y, x1: pvalue.px * draw_x, y1: pvalue.py * draw_y });
                            }
                        }
                    })
                })
                line({ ctx, x: x * draw_x, y: y * draw_y, x1: x1 * draw_x, y1: y1 * draw_y });
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

    onChangeFormControl = (e) => {
        let index = e.target.getAttribute('index');
        let cur_machine_data = this.state.machine_data
        cur_machine_data[index].machine_code = e.target.value
        this.setState({
            machine_data: cur_machine_data
        })
    }

    btnDraw = (e) => {
        const ctx = this.upRef.current.getContext('2d');
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
        if (!this.state.drawStatus) {
            this.setState({
                drawStatus: true,
            })
            Object.assign(e.target.style, { width: 'auto', background: "#5e789f", color: "white", borderColor: "#5e789f" })
        } else {
            this.setState({
                drawStatus: false,
            })
            Object.assign(e.target.style, { width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" })

        }
    }

    onChangeFloor = (e) => {
        let floor_id = e.target.value;
        this.setState({
            canvasHidden: false,
            FormControlDisable: true,
            selected_floor_id: floor_id,
            displayCheck: false,
            displayBtnName: '全部顯示',
            preCanvasWidth: this.downRef.current.offsetWidth
        })
        const ctx = this.upRef.current.getContext('2d');
        ctx.clearRect(0, 0, this.state.canvasWidth, this.state.canvasHeight)
        let cur_machine_data;
        let point, prepoint;
        axios.get('/RFID/position', {
            params: {
                floor_id: floor_id
            }
        })
            .then((response) => {
                cur_machine_data = [];
                response.data.map((value, index) => {
                    point = [];
                    prepoint = [];
                    let px1 = parseInt(value.point_1_x, 10);
                    let py1 = parseInt(value.point_1_y, 10);
                    let px2 = parseInt(value.point_2_x, 10);
                    let py2 = parseInt(value.point_2_y, 10);
                    let px3 = parseInt(value.point_3_x, 10);
                    let py3 = parseInt(value.point_3_y, 10);
                    let px4 = parseInt(value.point_4_x, 10);
                    let py4 = parseInt(value.point_4_y, 10);
                    point.push({ px: px1, py: py1 })
                    point.push({ px: px2, py: py2 })
                    point.push({ px: px3, py: py3 })
                    point.push({ px: px4, py: py4 })
                    prepoint.push({ px: 0, py: 0 })
                    prepoint.push({ px: px1, py: py1 })
                    prepoint.push({ px: px2, py: py2 })
                    prepoint.push({ px: px3, py: py3 })
                    cur_machine_data.push({
                        machine_id: value.machine_id,
                        address_id: value.address_id,
                        antenna_id: value.antenna_id,
                        machine_code: value.machine_code,
                        canvas_width: value.canvas_width,
                        canvas_height: value.canvas_height,
                        point: point,
                        prepoint: prepoint
                    })
                })
                this.updateCanvas();
                this.setState({
                    machine_data: cur_machine_data
                })
            })
            .catch((error) => console.log(error))
    }

    onChangeAddressSelect = (e, mindex) => {
        let cur_machine_data = [...this.state.machine_data];
        cur_machine_data[mindex]['address_id'] = parseInt(e.target.value)
        this.setState({
            machine_data: cur_machine_data,
            selectedMindex: mindex
        })
    }

    onChangeAntennaSelect = (e, mindex) => {
        let cur_machine_data = [...this.state.machine_data]
        cur_machine_data[mindex]['antenna_id'] = parseInt(e.target.value)
        let cur_saveAntenna = [...this.state.saveAntenna]
        cur_saveAntenna.push({
            machine_id: parseInt(e.target.getAttribute('index'), 10),
            antenna_id: parseInt(e.target.value, 10)
        })
        this.setState({
            saveAntenna: cur_saveAntenna,
            machine_data: cur_machine_data
        })
    }

    onChangeStatus = (e, mindex) => {
        let cur_machine_data = [...this.state.machine_data];
        cur_machine_data[mindex]['status'] = e.target.value
        this.setState({
            machine_data: cur_machine_data,
        })
    }

    addressModalCancel = (e) => {
        this.setState({ addressModalShow: false })
    }

    floorModalCancel = (e) => {
        this.setState({ floorModalShow: false })
    }

    editAddressAntenna = (e) => {
        this.setState({ modalTitle: '新增編輯天線' })
        if (this.state.addressModalShow) {
            this.setState({
                addressModalShow: false
            })
        } else {
            this.setState({
                addressModalShow: true,
            })
        }
    }

    editFloor = (e) => {
        this.setState({ modalTitle: '新增編輯樓層' })
        if (this.state.floorModalShow) {
            this.setState({
                floorModalShow: false
            })
        } else {
            this.setState({
                floorModalShow: true,
            })
        }
    }

    addAddress = (e) => {
        axios.post(`/RFID/address`)
            .then((response) => {
                let cur_address_data = [...this.state.address_data]
                cur_address_data.push({
                    address_id: response.data.address_id,
                    address: '',
                    antennas: []
                })
                this.setState({
                    address_data: cur_address_data,
                    addressEditCheck: '編輯中...',
                    addressEditCheckColor: 'text-muted'
                })
            })
    }

    addAntenna = (e) => {
        let cur_address_data = JSON.parse(JSON.stringify(this.state.address_data))
        axios.post(`/RFID/antenna`, { address_id: parseInt(cur_address_data[e.target.getAttribute('index')]['address_id'], 10) })
            .then((response) => {
                cur_address_data[e.target.getAttribute('index')]['antennas'].push({
                    antenna_id: parseInt(response.data.antenna_id, 10),
                    antenna_code: null
                })
                this.setState({
                    address_data: cur_address_data,
                    addressEditCheck: '編輯中...',
                    addressEditCheckColor: 'text-muted'
                })
            })
    }

    addFloor = (e) => {
        axios.post(`/RFID/floor/new`)
            .then((response) => {
                let cur_floor_data = [...this.state.floor_data]
                cur_floor_data.push({
                    floor_id: response.data.floor_id,
                    floor_name: '',
                    file_client_name: ''
                })
                this.setState({
                    floor_data: cur_floor_data,
                    floorEditCheck: '編輯中...',
                    floorEditCheckColor: 'text-muted'
                })
            })
    }

    saveBtn = (e) => {
        axios.patch('/RFID/position',
            this.state.machine_data
        )
            .then((response) => console.log(response))
        axios.post('/RFID/antenna_machine',
            this.state.saveAntenna
        )
            .then((response) => console.log(response))
        this.setState({
            FormControlDisable: true
        })
    }

    updateAddressAntenna = (e) => {
        axios.patch('/RFID/address', this.state.address_data)
            .then((response) => {
                console.log(response)
            })
        let result = []
        console.log(this.state.address_data)
        let cur_address_data = JSON.parse(JSON.stringify(this.state.address_data))
        cur_address_data.map((value, index) => {
            value.antennas.map((avalue, aindex) => {
                result.push(avalue)
            })
        })
        console.log(result)
        axios.patch('/RFID/antenna', result)
            .then((response) => {
                console.log(response)
            })
        this.setState({
            addressEditCheck: '已保存',
            addressEditCheckColor: 'text-success'
        })
    }

    updateFloor = (e) => {
        axios.patch('/RFID/floor', this.state.floor_data)
            .then((response) => {
                console.log(response)
            })
        this.setState({
            floorEditCheck: '已保存',
            floorEditCheckColor: 'text-success'
        })
    }

    deleteAddress = (e) => {
        let cur_address_data = [...this.state.address_data]
        axios.delete(`/RFID/address`, {
            data: {
                address_id: parseInt(cur_address_data[e.target.getAttribute('index')]['address_id'], 10)
            }
        })
            .then((response) => {
                cur_address_data.splice(e.target.getAttribute('index'), 1)
                this.setState({ address_data: cur_address_data })
            })
    }

    deleteAntenna = (e) => {
        let cur_address_data = JSON.parse(JSON.stringify(this.state.address_data))
        axios.delete(`/RFID/antenna`, {
            data: {
                antenna_id: parseInt(cur_address_data[e.target.getAttribute('index')]['antennas'][e.target.getAttribute('aindex')]['antenna_id'], 10)
            }
        })
            .then((response) => {
                cur_address_data[e.target.getAttribute('index')]['antennas'].splice(e.target.getAttribute('aindex'), 1)
                this.setState({ address_data: cur_address_data })
            })
    }

    onChangeIP = (e) => {
        let cur_address_data = this.state.address_data
        cur_address_data[e.target.getAttribute('index')]['address'] = e.target.value
        this.setState({
            address_data: cur_address_data,
            addressEditCheck: '編輯中...',
            addressEditCheckColor: 'text-muted'
        })
    }

    onChangeAntennaCode = (e) => {
        let cur_address_data = this.state.address_data
        cur_address_data[e.target.getAttribute('rowindex')]['antennas'][e.target.getAttribute('aindex')]['antenna_code'] = parseInt(e.target.value)
        this.setState({
            address_data: cur_address_data,
            addressEditCheck: '編輯中...',
            addressEditCheckColor: 'text-muted'
        })
    }

    deleteFloor = (e) => {
        let cur_floor_data = [...this.state.floor_data]
        axios.delete(`/RFID/floor`, {
            data: {
                floor_id: parseInt(cur_floor_data[e.target.getAttribute('index')]['floor_id'], 10)
            }
        })
            .then((response) => {
                cur_floor_data.splice(e.target.getAttribute('index'), 1)
                this.setState({ floor_data: cur_floor_data })
            })
    }

    clickUpdateImg = (e) => {
        this.fileInputRef.current.click();
    }

    onChangeFloorName = (e) => {
        let cur_floor_data = this.state.floor_data
        cur_floor_data[e.target.getAttribute('index')]['floor_name'] = e.target.value
        this.setState({
            floor_data: cur_floor_data,
            floorEditCheck: '編輯中...',
            floorEditCheckColor: 'text-muted'
        })
    }

    onImageChange = (e) => {
        this.setState({ datatableLoading: true })
        let index = e.target.getAttribute('index')
        let floor_id = e.target.getAttribute('floor_id')
        let file = e.target.files[0];
        let type = e.target.files[0].type;
        this.UploadImg(file, type, floor_id, index);
        e.target.value = ""
    }

    UploadImg = (file, type, floor_id, index) => {
        let request_data = {
            floor_id: floor_id
        }
        // 到時候要是可調的，允許之型態
        const supportedFilesTypes = this.state.upload.allowType;
        if (supportedFilesTypes.indexOf(type) > -1) {
            // Begin Reading File
            var image = new Image();
            image.src = file;
            const reader = new FileReader();
            reader.onload = e => this.setState({
                preview: e.target.result,
            });
            reader.readAsDataURL(file);
            // Create Form Data
            var payload = new FormData();
            payload.append('inputFile', file);
            Object.entries(request_data).map(([key, item]) => {
                payload.append(key, item);
            })

            axios.post(this.state.upload.API_location, payload, {
                cancelToken: this.state.source.token,
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (e) => {
                    const done = e.position || e.loaded;
                    const total = e.totalSize || e.total;
                    const perc = (Math.floor(done / total * 1000) / 10);
                    if (perc >= 100) {
                        this.setState({ status: "上傳完畢" })
                        // Delayed reset
                        setTimeout(() => {
                            this.setState({ status: "" });

                        }, 750); // To match the transition 500 / 250
                    } else {
                        this.setState({ status: `${perc}%` });
                    }
                }
            }).then(response => {
                let cur_floor_data = [...this.state.floor_data]
                cur_floor_data[index]['file_client_name'] = response.data.file_client_name
                this.setState({
                    floor_data: cur_floor_data,
                    datatableLoading: false
                })
            })
        }
        else {
            this.setState({ preview: null, status: "此檔案無法上傳，請再次點擊或拖拉至此" });
        }
    }

    render() {
        return (
            <Container fluid>
                <Row className="mt-2" md='auto'>
                    <Col xs="auto">
                        <Form.Select onChange={this.onChangeFloor.bind(this)} ref={this.selectRef}>
                            <option value='0' disabled>請選擇樓層</option>
                            {this.state.floor_data.map((value, index) => {
                                return <option key={value.floor_name} value={value.floor_id}>{value.floor_name}</option>
                            })}
                        </Form.Select>
                    </Col>
                    <Col xs='auto' hidden={!this.state.editMode}>
                        <Button variant='light' style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.btnDraw.bind(this)}>文字框</Button>
                    </Col>
                    <Col xs="auto" hidden={!this.state.editMode}>
                        <Button variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.displayAll.bind(this)}>{this.state.displayBtnName}</Button>
                    </Col>
                    <Col xs="auto" hidden={!this.state.editMode}>
                        <Button variant='light' style={{ width: 'auto', background: "#5e789f", color: "white", borderWidth: "medium", borderColor: "#5e789f" }} onClick={this.saveBtn.bind(this)}>保存</Button>
                    </Col>
                    <Col xs="auto" hidden={!this.state.editMode}>
                        <Button variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.editAddressAntenna.bind(this)}>新增編輯天線</Button>
                    </Col>
                    <Col xs="auto" hidden={!this.state.editMode}>
                        <Button variant="light" style={{ width: 'auto', background: "white", color: "#5e789f", borderColor: "#5e789f", fontWeight: "bold", borderWidth: "medium" }} onClick={this.editFloor.bind(this)}>新增編輯樓層</Button>
                    </Col>
                </Row>
                <Row className="my-1" xs={1} xxl={2} style={{ overflowY: 'scroll' }} hidden={!this.state.editMode}>
                    {this.state.machine_data.map((mvalue, mindex) => {
                        return (
                            <Col key={mvalue.machine_id} className="mt-2">
                                <Card className="col-12">
                                    <Card.Body className='text-center'>
                                        <Form className='row'>
                                            <Col className='col-1'>
                                                <Button id={mindex} variant='outline-secondary' onClick={this.drawBtn.bind(this)}>{mindex + 1}</Button>
                                            </Col>
                                            <Col className='col-3'>
                                                <Form.Control placeholder="區塊名稱" index={mindex} value={mvalue.machine_code} onChange={this.onChangeFormControl.bind(this)} />
                                            </Col>
                                            <Col className='col-3'>
                                                <Form.Select aria-label="Default select example" onChange={e => this.onChangeAddressSelect(e, mindex)} value={mvalue.address_id}>
                                                    <option value={0}>請選擇IP</option>
                                                    {this.state.address_data.map((value, index) => {
                                                        return <option key={value.address} value={value.address_id}>{value.address}</option>
                                                    })}
                                                </Form.Select>
                                            </Col>
                                            <Col className='col-2'>
                                                <Form.Select aria-label="Default select example" index={mvalue.machine_id} onChange={e => this.onChangeAntennaSelect(e, mindex)} value={mvalue.antenna_id}>
                                                    <option value={0}>請選擇天線</option>
                                                    {this.state.address_data.map((value, index) =>
                                                        value.address_id === mvalue.address_id ? value.antennas.map((avalue, aindex) => {
                                                            return <option key={avalue.antenna_code} value={avalue.antenna_id}>{avalue.antenna_code}</option>
                                                        }) : <></>
                                                    )}
                                                </Form.Select>
                                            </Col>
                                            <Col className='col-2'>
                                                <Form.Select aria-label="Default select example" onChange={e => this.onChangeStatus(e, mindex)} value={mvalue.status}>
                                                    <option value={0}>請選擇狀態</option>
                                                    {this.state.status_data.map((value, index) => {
                                                        return <option key={`${value.status}${index}`} value={value.stauts}>{value.status}</option>
                                                    })}
                                                </Form.Select>
                                            </Col>
                                            <Col className='col-1 mt-2'>
                                                <button type="button" className="btn-close pr-3" aria-label="Close" id={mindex} onClick={this.deleteBtn.bind(this)}></button>
                                            </Col>
                                        </Form>
                                    </Card.Body>
                                </Card>
                            </Col>
                        )
                    })}
                </Row>
                <Row className="my-2">
                    <Col style={{
                        position: 'relative'
                    }}>
                        <div>
                            <canvas
                                ref={this.downRef}
                                style={{
                                    border: '1px solid',
                                    objectFit: 'contain',
                                    width: '100%',
                                    height: '100%',
                                    left: 0,
                                    top: 0,
                                }}
                                width={this.state.canvasWidth}
                                height={this.state.canvasHeight}
                            />
                            <canvas
                                ref={this.midRef}
                                style={{
                                    backgroundColor: 'transparent',
                                    position: 'absolute',
                                    left: this.state.offsetLeft || 0,
                                    top: this.state.offsetTop || 0,
                                    objectFit: 'contain',
                                    width: this.state.width || 0,
                                    height: '100%',
                                }}
                                width={this.state.canvasWidth}
                                height={this.state.canvasHeight}
                            />
                            <canvas
                                ref={this.upRef}
                                style={{
                                    backgroundColor: 'transparent',
                                    position: 'absolute',
                                    left: this.state.offsetLeft || 0,
                                    top: this.state.offsetTop || 0,
                                    zindex: 1,
                                    objectFit: 'contain',
                                    width: this.state.width || 0,
                                    height: '100%',
                                }}
                                width={this.state.canvasWidth}
                                height={this.state.canvasHeight}
                                onMouseDown={this.handleMouseDown.bind(this)}
                                onMouseUp={this.handleMouseUp.bind(this)}
                                onMouseMove={this.handleMouseMove.bind(this)}
                            />
                        </div>
                    </Col>
                </Row>
                <ModalWithDataTable
                    show={this.state.addressModalShow}
                    modalTitle={this.state.modalTitle}
                    addModule={this.addAddress.bind(this)}
                    updateElement={this.updateAddressAntenna.bind(this)}
                    modalCancel={this.addressModalCancel.bind(this)}
                    columns={this.state.address_columns}
                    data={this.state.address_data}
                    editCheck={this.state.addressEditCheck}
                    editCheckColor={this.state.addressEditCheckColor}
                ></ModalWithDataTable>
                <ModalWithDataTable
                    show={this.state.floorModalShow}
                    datatableLoading={this.state.datatableLoading}
                    modalTitle={this.state.modalTitle}
                    addModule={this.addFloor.bind(this)}
                    updateElement={this.updateFloor.bind(this)}
                    modalCancel={this.floorModalCancel.bind(this)}
                    columns={this.state.floor_columns}
                    data={this.state.floor_data}
                    editCheck={this.state.floorEditCheck}
                    editCheckColor={this.state.floorEditCheckColor}
                ></ModalWithDataTable>
            </Container >
        );
    }
}

export default MapLocation;