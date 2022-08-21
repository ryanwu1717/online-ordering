import React from 'react';
import axios from 'axios';
import './Collapse.css';
import { Card } from 'react-bootstrap'
/* 
    A. module_id透過props獲得(從index.js代入)
    B. baseURL是API的路徑
    C. API所獲得的資料透過persons[]去接收
    D. persons[key]["collapse_condition"]預設為True，表關閉卡片，反之False為開啟卡片
*/
const baseURL = "/overview";

export default class Collapse extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            persons: [],
        };
    }
    componentDidMount() {
        axios
            .get(baseURL, {
                params: {
                    module_id: this.props.module_id
                }
            })
            .then((response) => {
                this.setState({ persons: response.data.all_order_collapse })
            });
    }
    handleClick(event, key) {
        /*根據當前點哪一個，遍歷的去改寫collapse_condition
        先暫存該person狀態，再進行map*/
        let persons = this.state.persons;
        persons.map((element, index) => {
            if (element.module_id === event) {
                // 表打開
                persons[index].collapse_condition = false
            }
            else {
                // 表關閉
                persons[index].collapse_condition = true
            }
        })
        this.setState({ persons: persons })
    }
    render() {
        let list_color = null;
        return (
            <div className='Collapse'>
                <Card>
                    <Card.Body className="d-flex overflow-auto">
                        <div className="flex-nowrap d-flex justify-content-center">
                            <div className="collapse_box">
                                <div className="section_collapse" id="list-tab">
                                    {
                                        this.state.persons.map((value, index) => {
                                            if (index === 0 || list_color !== value.module_color) {
                                                list_color = value.module_color;
                                                return (
                                                    <div key={value.module_id.toString()} className={"collapse_section_item d-flex align-items-center collapse_card" + (value.module_color) + " text-black overflow-auto " + (value.collapse_condition ? "default_collapse justify-content-center " : "expand_collapse justify-content-start ")} onClick={this.handleClick.bind(this, value.module_id)}>
                                                        <div className="d-flex">
                                                            <h1 className="collapse_title mx-3 text-center">{value.module_name}</h1>
                                                        </div>
                                                        <div className={"collapse_content_list ml-3 " + (value.collapse_condition ? "d-none" : "d-flex justify-content-around align-items-center ")}>
                                                            <ul className="d-inline-block py-1 list-unstyled m-0" >
                                                                {this.state.persons[index]["names"].map((value, index) => {
                                                                    return (
                                                                        <li>{value.name}:{value.count}</li>
                                                                    )
                                                                })}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                )
                                            }
                                        })
                                    }
                                </div>
                            </div>
                        </div>
                    </Card.Body>
                </Card>
            </div>
        );
    }
}