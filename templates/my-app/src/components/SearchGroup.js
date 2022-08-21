import React, { useState, useRef, useEffect } from "react";
import {InputGroup, FormControl } from 'react-bootstrap';

function SearchGroup(props) {
    
    const [searchData, setSearchData] = useState(props.searchData);
    useEffect(() => {
        setSearchData(props.searchData)
    },[props.searchData]);
    return (
        <>
            {searchData.map((value, index) => (
                <InputGroup className="my-2">
                    <InputGroup.Text>{value.name}</InputGroup.Text>
                    <FormControl
                    aria-describedby="inputGroup-sizing-default"
                    value= {value.value}
                    type={value.type}
                    id={value.id}
                    row_idx={value.row_idx}
                    idx={value.idx}
                    onChange={(e)=>props.onChange(e)}
                    disabled={value.disabled}
                    />
                </InputGroup>
            ))}
        </>
    );
}
  
export default SearchGroup;