// import logo from './logo.svg';
import 'bootstrap/dist/css/bootstrap.min.css';
import { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import axios from 'axios';



function App(data) {
  const [progress,setProgress] = useState([]);
  const [module,setModule] = useState([]);
  const [checkArr,setCheckArr] = useState([]);
  const id = data.id
    
  useEffect(() => {
    console.log(id)
    axios
        .get("/setting/url")
        .then(response => {
            setProgress(response.data);
        });
  },[]);
  useEffect(() => {
    console.log(checkArr)
   
  },[checkArr]);


  const updateCheck= (event) => {
    var updatedList = [...checkArr];
    console.log(event.target)

    if (event.target.checked) {
      updatedList = [...checkArr, event.target.value];
    } else {
      updatedList.splice(checkArr.indexOf(event.target.value), 1);
    }
    setCheckArr(updatedList);
  }


  return (
    <> 
      <div className="row">
        <div className="col-12 col-sm-3 mb-4">
          <div className="card shadow mb-4 h-100">
            <div className="card-header">協助報價部門</div>
            <div className="card-body">
              <ul>
                <li>請選擇需要協助的部門（可複選）</li>
              </ul>
                  {progress.map((progress,index) => 
                    <div className="row" key={index}> 
                      <div className="row form-group form-check form-inline col-auto">
                        <label >
                          <input className="form-check-input"  type="checkbox" data-module={progress.module_id} defaultValue={progress.show} defaultChecked={progress.show} onClick={updateCheck}/>
                        {progress.name}</label>
                      </div>
                  </div>
                  )}
               
            </div>
          </div>
        </div>
        
      </div>
    </>
  
  );
}

export default App;
