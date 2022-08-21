import React from "react";
import { ButtonGroup, Button, Col, Row } from "react-bootstrap";
import "./TabsButtoms.css";

class ToggleButtonGroupControlled extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      buttomData: this.props.buttoms,
      click: false,
    };
    this.changeColor = this.changeColor.bind(this);
  }

  handleChange = (event) => {
    this.props.parentCallback(event.target.value);
    this.changeColor(event.target.value);
  };
  changeColor = (value) => {
    this.setState({ click: !this.state.click });

    let button = this.state.buttomData;
    let newButtons = [...this.state.buttomData];
    for (let i = 0; i < button.length; i++) {
      let newButton = { ...newButtons[i] };
      if (button[i].eventKey == value) {
        newButton.backgroundColor = "blueButton";
      } else {
        newButton.backgroundColor = "whiteButton";
      }
      newButtons[i] = newButton;
    }
    this.setState({ buttomData: newButtons });
  };

  render() {
    return (
      <ButtonGroup aria-label="Basic example">
        {this.state.buttomData.map((items, idx) => (
          <Button
            value={items.eventKey}
            onClick={this.handleChange}
            className={items.backgroundColor}
          >
            {items.title}
          </Button>
        ))}
      </ButtonGroup>
    );
  }
}

export default ToggleButtonGroupControlled;
