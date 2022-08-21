import React from "react";
import Card from "react-bootstrap/Card";
import axios from 'axios';

class CardPicture extends React.Component {
    // constructor(props) {
    //     super(props);
    //     this.state = {
    //         img_url: ''
    //     };
    // }

    // componentDidMount() {
    //     fetch("/develop/video/industryPicture/1")
    //         .then((res) => res.blob())
    //         .then(
    //             imageBlob => {
    //                 // Then create a local URL for that image and print it
    //                 this.setState({ img_url: URL.createObjectURL(imageBlob) })
    //             },
    //             (error) => {
    //                 console.log(error)
    //             }
    //         );
    // }
    // render() {
    //     return (
    //         <Card className = "shadow">
    //             <Card.Header>
    //                 <span>{this.props.title}</span>
    //             </Card.Header>
    //             <Card.Body>
    //                 <div class="mh-100 d-flex">
    //                     <Card.Img src={this.state.img_url} class="img-fluid img-thumbnail rounded float-left" alt="..."></Card.Img>
    //                 </div>
    //             </Card.Body>
    //         </Card>

    //     );
    // }

    constructor(props) {
        super(props);
        this.state = {
            img_url: ''
        };
    }
    
      componentDidMount() {
        axios.get(`/develop/video/industryPicture/1`,{responseType:'blob'})
        .then((res) => this.setState({ img_url: window.URL.createObjectURL(new Blob([res.data])) })
        );
      }
    
      render() {
        return (
            <Card className = "shadow">
                <Card.Header>
                    <span>{this.props.title}</span>
                </Card.Header>
                <Card.Body>
                    <div className="mh-100 d-flex">
                        <Card.Img src={this.state.img_url} className="img-fluid img-thumbnail rounded float-left" alt="..."></Card.Img>
                    </div>
                </Card.Body>
            </Card>
        );
    }
}
  
  export default CardPicture;