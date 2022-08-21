import React from "react";
import {SortableContainer, sortableElement } from 'react-sortable-hoc';
import { Card, Button, Col, Row, FormControl,InputGroup  } from 'react-bootstrap';
import DetailItem from './DetailItem';
import axios from 'axios';
export default class SortableProcess extends React.Component {
  render() {
        return ( 
            <SortableListProcess axis="xy" line={this.props.line} line_idx={this.props.line_idx} process_id={this.props.process_id} process_name={this.props.process_name} cardbody={this.props.cardbody} onSortEnd={this.props.onSortEnd} onChange={this.props.onChange} />
        )
    }
}

class ItemProcess extends React.Component {

    state = {
      cardbody : this.props.cardbody,
      order_processes_reprocess: [],
    }

    handleChange = (e) => {
      this.props.onChange(e.target.value)
    }

    delProcess = (e) => {
        let options = [...this.state.cardbody];
        options.splice(e.target.attributes.detail_idx.value, 1);
        this.setState({ cardbody: options });
        console.log(e.target.attributes.detail_idx.value)
    }
  
    addProcess = () => {
      this.setState({
        cardbody: [...this.state.cardbody, ""]
      })
    }
    componentDidMount() {
    }
    render() {
        return(
            
            <InputGroup className="my-2" {...this.props} >
              <InputGroup.Text id="basic-addon1">{this.props.process_name}</InputGroup.Text>
              <FormControl
                onChange={this.handleChange.bind(this)} 
                process_id={this.props.process_id}
                idx={this.props.idx}
                line={this.props.line}
                line_idx={this.props.line_idx}
                value={this.props.cardbody}
              />
            </InputGroup>
                      
        )
    }
  }

const SortableItemProcess = sortableElement(ItemProcess);
const SortableListProcess = SortableContainer(({cardbody, line, line_idx, process_id,process_name, onChange}) => {
   
  return (
      <div>
        {process_id.map((value, index) => (
            <SortableItemProcess className="mx-1 my-1 align-top" cardbody={cardbody[index]} process_name={process_name[index]} line_idx={line_idx} key={`item-process-${index}`} process_id={process_id[index]} line={line} idx={index} index={index} onChange={onChange} />
        ))}
      </div>
    );
  });
