import React, { useState, useRef, useEffect } from "react";
import {Form, FloatingLabel } from 'react-bootstrap';

function SearchTextArea(props) {
    
    const [searchData, setSearchData] = useState(props.searchData);
    useEffect(() => {
        setSearchData(props.searchData)
    },[props.searchData]);
    
    return (

        <>
            {searchData.map((value, index) => (
                <FloatingLabel className="my-2" label={value.label}>
                    <Form.Control
                        as="textarea"
                        style={{ height: value.height }}
                        autoComplete="off"
                        value={value.value}
                        id={value.id}
                        row_idx={value.row_idx}
                        idx={value.idx}
                        disabled={value.disabled}
                        onChange={(e)=>props.onChange(e)}
                    />
                </FloatingLabel>
            ))}
        </>
    );
}
  
export default SearchTextArea;