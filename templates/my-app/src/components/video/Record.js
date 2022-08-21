import Card from "react-bootstrap/Card";
import Button from 'react-bootstrap/Button';
import { faPlayCircle } from "@fortawesome/free-solid-svg-icons";
import { faMicrophone } from "@fortawesome/free-solid-svg-icons";
import { faTrashAlt } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";


const Record = () => {
    return (
        <Card className = "shadow">
            <Card.Header>
                紀錄
            </Card.Header>
            <Card.Body>
                <div className = "form-group form-inline">
                    <li><Button variant = "link">0:30</Button></li>
                    <textarea className = "form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                    <Button variant = "primary" className = "mx-2">
                        <FontAwesomeIcon icon={faPlayCircle} />
                    </Button>
                    <Button variant = "warning" className = "mx-2">
                        <FontAwesomeIcon icon={faMicrophone} color="white" />
                    </Button>
                    <Button variant = "danger" className = "mx-2">
                        <FontAwesomeIcon icon={faTrashAlt} />
                    </Button>
                </div>
                <div className = "form-group form-inline">
                    <li><Button variant = "link">1:00</Button></li>
                    <textarea className = "form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                    <Button variant = "primary" className = "mx-2">
                        <FontAwesomeIcon icon={faPlayCircle} />
                    </Button>
                    <Button variant = "warning" className = "mx-2">
                        <FontAwesomeIcon icon={faMicrophone} color="white" />
                    </Button>
                    <Button variant = "danger" className = "mx-2">
                        <FontAwesomeIcon icon={faTrashAlt} />
                    </Button>
                </div>
                <div className = "form-group form-inline">
                    <li><Button variant = "link">1:30</Button></li>
                    <textarea className = "form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                    <Button variant = "primary" className = "mx-2">
                        <FontAwesomeIcon icon={faPlayCircle} />
                    </Button>
                    <Button variant = "warning" className = "mx-2">
                        <FontAwesomeIcon icon={faMicrophone} color="white" />
                    </Button>
                    <Button variant = "danger" className = "mx-2">
                        <FontAwesomeIcon icon={faTrashAlt} />
                    </Button>
                </div>
                <div className = "form-group form-inline">
                    <li><Button variant = "link">2:00</Button></li>
                    <textarea className = "form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                    <Button variant = "primary" className = "mx-2">
                        <FontAwesomeIcon icon={faPlayCircle} />
                    </Button>
                    <Button variant = "warning" className = "mx-2">
                        <FontAwesomeIcon icon={faMicrophone} color="white" />
                    </Button>
                    <Button variant = "danger" className = "mx-2">
                        <FontAwesomeIcon icon={faTrashAlt} />
                    </Button>
                </div>
                <div className = "form-group form-inline">
                    <li><Button variant = "link">2:30</Button></li>
                    <textarea className = "form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                    <Button variant = "primary" className = "mx-2">
                        <FontAwesomeIcon icon={faPlayCircle} />
                    </Button>
                    <Button variant = "warning" className = "mx-2">
                        <FontAwesomeIcon icon={faMicrophone} color="white" />
                    </Button>
                    <Button variant = "danger" className = "mx-2">
                        <FontAwesomeIcon icon={faTrashAlt} />
                    </Button>
                </div>
            </Card.Body>
        </Card>
    );
};
  
export default Record;