import * as React from "react";
import {
  useState,
  useEffect,
  useRef,
  useImperativeHandle,
  forwardRef,
} from "react";
import Datatable from "../Datatable.js";
import ContentEditable from "react-contenteditable";
import axios from "axios";

const CustomTable = React.forwardRef((props, ref) => {
  const datatables_range = {
    require: {},
    thead: [
      {
        sortField: "",
        name: "#",
        cell: (row) => row.video_type_id,
        width: "auto",
        center: true,
      },
      {
        sortField: "",
        name: "影片類別",
        cell: (row) => (
          <ContentEditable
            html={row.name} // innerHTML of the editable div
            disabled={false} // use true to disable edition
            onChange={handleChange(row.video_type_id)} // handle innerHTML change
          />
        ),
        width: "auto",
        center: true,
      },
    ],
  };
  const [delete_arr, set_delete_arr] = useState([]);
  const [patch_arr, set_patch_arr] = useState([]);
  useImperativeHandle(ref, () => ({
    datatableCallBack() {
      datatableCallBack();
    },
    delete_arr: delete_arr,
    patch_arr: patch_arr,
  }));
  useEffect(() => { console.log(patch_arr) }, [patch_arr])
  function datatableCallBack() {
    myRef.current.fetchUsers();
  }
  function postProcess(response) {
    let data = JSON.parse(JSON.stringify(response.data));
    let newResponses = JSON.parse(JSON.stringify(response));
    newResponses.data = new Object();
    newResponses.data.data = [];
    for (var i = 0; i < data.length; i++) {
      let newResponse = data[i];
      newResponses.data.data.push({
        video_type_id: newResponse.id,
        name: newResponse.name,
      });
    }
    return JSON.parse(JSON.stringify(newResponses));
  }
  function rowClickedHandler(row, e) {
    if (delete_arr.indexOf(row.video_type_id) === -1) {
      Object.assign(e.target.parentElement.style, { background: "#ffe8e8" });
      let delete_arr_temp = [...delete_arr];
      delete_arr_temp.push(row.video_type_id);
      set_delete_arr(delete_arr_temp);
    } else {
      Object.assign(e.target.parentElement.style, { background: "#ffffff" });
      let delete_arr_temp = [...delete_arr];
      delete_arr_temp.splice(delete_arr.indexOf(row.video_type_id), 1);
      set_delete_arr(delete_arr_temp);
    }
  }
  const handleChange = (video_type_id) => (event) => {
    patch_arr.push({ video_type_id: video_type_id, video_type_name: event.target.value })
    axios
      .patch("/develop/video/video_type", patch_arr)
      .then((response) => datatableCallBack())
      .catch((error) => console.log(error));
  };
  const myRef = useRef();
  return (
    <div className="single_line m-0 p-0">
      <Datatable
        rowClickedHandler={rowClickedHandler}
        datatables={datatables_range}
        postProcess={postProcess}
        ref={myRef}
        api_location="/develop/videos/video_type"
      />
    </div>
  );
});

export default CustomTable;
