import React, { useState, useEffect } from "react";
import axios from 'axios';
import Magnifier from '../components/quotation/Magnifier';

function Picutres(props) {
    const [picturesData, setPicturesData] = useState(props.pictures_data);
    const [loading, setLoading] = useState(false);
    const [pickTitle, setPickTitle] = useState('Press Here');
    const handleRotate = (e,angle,index)=>{
        let pictures_data_temp = [...picturesData];
        pictures_data_temp[index].rotating = true;
        setPicturesData(pictures_data_temp)
        axios
            .patch("/file/message/rotate",{rotate:angle,file_name:picturesData[index].file_name})
            .then(response=>{
                let pictures_data_temp = [...picturesData];
                pictures_data_temp[index].hash = new Date().getTime();
                pictures_data_temp[index].rotating = false;
                setPicturesData(pictures_data_temp)
            })
    }
    useEffect(() => {
        let pictures_data_temp = [...props.pictures_data];
        Object.keys(pictures_data_temp).forEach((key)=>{
            pictures_data_temp[key].rotate = 0;
            pictures_data_temp[key].rotating = false;
            pictures_data_temp[key].hash = new Date().getTime();
        });
        setPicturesData(props.pictures_data)
    },[props.pictures_data]);
/* /file/message/rotate */
    useEffect(() => {
        setLoading(props.loading)
    },[props.loading]);
    useEffect(()=>{
        setPickTitle(props.pickTitle)
    },[props.pickTitle])
    return (
      <>
        <div className="row d-flex flex-nowrap" style={{overflowX:'scroll'}} >
        {loading?<>讀取中...</>:
            picturesData.map((value, index) => (
                <div className="col-6">
                    <div className="row d-flex justify-content-center">
                        <div className="col-auto" style={{zIndex:'999'}}>
                            <button type="button" className="btn btn-secondary" onClick={(e)=>handleRotate(e,90,index)} hidden={value.rotating} >+90</button>
                        </div>
                        <div className="col-auto" style={{zIndex:'999'}}>
                            <button type="button" className="btn btn-secondary"onClick={(e)=>handleRotate(e,10,index)} hidden={value.rotating}>+10</button>
                        </div>
                        <div className="col-3" style={{zIndex:'999'}} hidden>
                            <input type="number" className="form-control" value={value.rotate} />
                        </div>
                        <div className="col-auto" style={{zIndex:'999'}}>
                            <button type="button" className="btn btn-secondary"onClick={(e)=>handleRotate(e,-10,index)} hidden={value.rotating}>-10</button>
                        </div>
                        <div className="col-auto" style={{zIndex:'999'}}>
                            <button type="button" className="btn btn-secondary"onClick={(e)=>handleRotate(e,-90,index)} hidden={value.rotating}>-90</button>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-10" style={{transform:`rotate(`+value.rotate+`deg)`}}>
                            <div className="d-flex justify-content-center">
                                <Magnifier src={axios.defaults.baseURL+value.src+'?'+value.hash} alt={value.alt} className="img-fluid"></Magnifier>
                                {/* <img src={axios.defaults.baseURL+value.src} alt={value.alt} className="img-fluid"/> */}
                            </div>
                        </div>
                        {Object.keys(value).map((key)=>
                            key==="order_name"?<>{value[key].hasOwnProperty('text')?(
                                <div className="col-12 text-center">
                                    <p><strong>圖{(index+1)}圖號：</strong>{value[key]['text']}</p>
                                </div>
                            ):''}</>:<></>
                        )}
                    </div>
                    <div className="row">
                        <div className="col-12">
                            <div className="d-flex justify-content-center">
                                <button style={{zIndex:'999'}} type="button" className="btn btn-primary" onClick={(e)=>{props.handleOnClick(e,value.file_name);}}>{pickTitle}</button>
                            </div>
                        </div>
                    </div>
                </div>
                ))
            }
        </div>
      </>
    );
}
  
export default Picutres;