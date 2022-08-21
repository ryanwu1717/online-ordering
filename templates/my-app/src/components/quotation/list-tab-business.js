import React from 'react';
import axios from 'axios';
import './list-tab-business.css';
/* 
    allowType:image/jpg、image/png、image/jpeg、video/mp4，允許傳入之型態，Ex:"['image/pmg']"
    request_data:key:value，API的key:value用object型態傳入，Ex:"["key":"value"]"
    API location，API位置，Ex:"/3DConvert/PhaseGallery/coptd_image"
*/

const baseURL = `/file/state/${file_id}`;

export default class ListTabBusiness extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
          
        };
    }
    componentDidMount() {
        axios
            .get(baseURL, {
                params: {
                    module_name: '製圖'
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

        return (
            <div className='Collapse'>

            </div>
        );
    }
}