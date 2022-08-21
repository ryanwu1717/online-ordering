
class BookmarksBar extends React.Component {
    render() {
        return (
            <div class="card shadow mb-4">
                <div class="card-body">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">數位典藏-首件錄影紀錄</a></li>
                            <li class="breadcrumb-item"><a href="#">CNC車床</a></li>
                            <li class="breadcrumb-item active" aria-current="page">00000000</li>
                        </ol>
                    </nav>
                </div>
            </div>
        );
    }
}
class VideoBlock extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            data: []
        };
    }

    componentDidMount() {
        fetch("/develop/video/video_download?video_id=1&language_id[0]=1")
            .then((res) => res.json())
            .then(
                (data) => {
                    this.setState({
                        data: data
                    });
                },
                (error) => {
                    console.log(error)
                }
            );
    }
    render() {
        const data = this.state;
        return (
            <div class="card shadow">
                <div class="card-header">
                    影片
                </div>
                <div class="card-body">
                    <div class="mh-100 d-flex">
                        <div class="embed-responsive embed-responsive-16by9 align-content-center flex-wrap">
                            <video controls key={data.data} >
                                <source src={data.data} type="video/mp4" />
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
class CardPicture extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            img_url: ''
        };
    }

    componentDidMount() {
        fetch("/develop/video/industryPicture/1")
            .then((res) => res.blob())
            .then(
                imageBlob => {
                    // Then create a local URL for that image and print it
                    this.setState({ img_url: URL.createObjectURL(imageBlob) })
                },
                (error) => {
                    console.log(error)
                }
            );
    }
    render() {
        return (
            <div class="card shadow">
                <div class="card-header">
                    <span>{this.props.title}</span>
                </div>
                <div class="card-body">
                    <div class="mh-100 d-flex">
                        <img src={this.state.img_url} class="img-fluid img-thumbnail rounded float-left" alt="..."></img>
                    </div>
                </div>
            </div>

        );
    }
}
class RecordBlock extends React.Component {
    render() {
        return (
            <div class="card shadow">
                <div class="card-header">
                    紀錄
                </div>
                <div class="card-body">
                    <div class="form-group form-inline">
                        <li><a class="btn btn-link">0:30</a></li>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                        <button type="button" class="btn btn-primary mx-2"><i
                            class="fas fa-play-circle"></i></button>
                        <button type="button" class="btn btn-warning mx-2"><i
                            class="fas fa-microphone"></i></button>
                        <button type="button" class="btn btn-danger mx-2"><i
                            class="fas fa-trash-alt"></i></button>
                    </div>
                    <div class="form-group form-inline">
                        <li><a class="btn btn-link">1:00</a></li>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                        <button type="button" class="btn btn-primary mx-2"><i
                            class="fas fa-play-circle"></i></button>
                        <button type="button" class="btn btn-warning mx-2"><i
                            class="fas fa-microphone"></i></button>
                        <button type="button" class="btn btn-danger mx-2"><i
                            class="fas fa-trash-alt"></i></button>
                    </div>
                    <div class="form-group form-inline">
                        <li><a class="btn btn-link">1:30</a></li>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                        <button type="button" class="btn btn-primary mx-2"><i
                            class="fas fa-play-circle"></i></button>
                        <button type="button" class="btn btn-warning mx-2"><i
                            class="fas fa-microphone"></i></button>
                        <button type="button" class="btn btn-danger mx-2"><i
                            class="fas fa-trash-alt"></i></button>
                    </div>
                    <div class="form-group form-inline">
                        <li><a class="btn btn-link">2:00</a></li>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                        <button type="button" class="btn btn-primary mx-2"><i
                            class="fas fa-play-circle"></i></button>
                        <button type="button" class="btn btn-warning mx-2"><i
                            class="fas fa-microphone"></i></button>
                        <button type="button" class="btn btn-danger mx-2"><i
                            class="fas fa-trash-alt"></i></button>
                    </div>
                    <div class="form-group form-inline">
                        <li><a class="btn btn-link">2:30</a></li>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="1"></textarea>
                        <button type="button" class="btn btn-primary mx-2"><i
                            class="fas fa-play-circle"></i></button>
                        <button type="button" class="btn btn-warning mx-2"><i
                            class="fas fa-microphone"></i></button>
                        <button type="button" class="btn btn-danger mx-2"><i
                            class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
            </div>
        );
    }
}
class ProductionManagementRemindBlock extends React.Component {
    render() {
        return (
            <div class="card shadow">
                <div class="card-header text-primary">技術生管研發提醒</div>
                <div class="card-body">
                    句子中的生產通知示例 施工應與可行性報告基本一致，但生產通知中提出的任何變更均受制於管理委員會的權利，使施工中的其他合理變化如 管理委員會，由特別多數認為必要和可取。
                </div>
            </div>
        )
    }
}
class LanguageBlock extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            digitalCollectionContentData: []
        };
    }

    componentDidMount() {
        fetch('/develop/video/translations?video_id=1&language_id[0]=1', {
            method: "GET",
            headers: new Headers({
                'Content-Type': 'application/json',
            })
        })
            .then(res => res.json())
            .then(data => this.setState({ digitalCollectionContentData: data }))
            .catch(e => {
                /*發生錯誤時要做的事情*/
            })
    }

    render() {
        return (
            <div>
                <nav>
                    <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                        {this.state.digitalCollectionContentData.map((data, i) => (
                            <a class={(i === 0) ? "nav-item nav-link active" : "nav-item nav-link"} id={"nav-lang" + data.id + "-tab"} data-toggle="tab" href={"#nav-lang" + data.id} role="tab" aria-controls={"nav-lang" + data.id} aria-selected="true">{data.language_name}</a>
                        ))}
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    {this.state.digitalCollectionContentData.map((data, i) => (
                        <div name={"lang" + data.id} class={(i === 0) ? "tab-pane fade show" : "tab-pane fade"} id={"nav-lang" + data.id} role="tabpanel" aria-labelledby={"nav-lang" + data.id + "-tab"}>
                            <div name="card_success" class="card shadow h-100 py-2">
                                <div class="card-body row row-cols-1">
                                    {data.note_content}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        )
    }

}
class SearchBlock extends React.Component {
    render() {
        return (
            <div>
                <div class="row">
                    <div class="col-8">
                        <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="Search"></input>
                    </div>
                    <button class="btn btn-primary mb-2">搜尋</button>
                </div>
            </div>

        )
    }
}
class StreamingVideo extends React.Component {
    constructor(props) {
        super(props)
        this.state = ({ StreamingVideoBlock: "Off" })
        this.changeBlockStatus = this.changeBlockStatus.bind(this)
    }
    changeBlockStatus() {
        if (this.state.StreamingVideoBlock == "On") {
            this.setState({ StreamingVideoBlock: "Off" })
            document.getElementById("mySidebar").style.width = "0px";
        }
        else {
            this.setState({ StreamingVideoBlock: "On" })
            document.getElementById("mySidebar").style.width = "800px";
        }
    }
    render() {
        const sidebar = {
            width: '0px',
            position: 'fixed',
            top: '51px',
            bottom: '0',
            left: '0',
            zIndex: '1000',
            display: 'block',
            overflowX: 'hidden',
            overflowY: 'auto',
            backgroundColor: '#f5f5f5',
            borderRight: '1px solid #eee'
        };
        return (
            <div id="mySidebar" style={sidebar}>
                <div class="card shadow">
                    <div class="card-header">
                        影片
                        <a href="javascript:void(0)" class="closebtn float-right" onClick={this.changeBlockStatus}>×</a>
                    </div>
                    <div class="card-body">
                        <div class="row align-content-center">
                            <button class="btn btn-primary mb-2 mx-2" >CNC</button>
                            <button class="btn btn-primary mb-2 mx-2" >铣床</button>
                        </div>
                        <div class="mh-100 d-flex">
                            <div class="embed-responsive embed-responsive-16by9 align-content-center flex-wrap">
                                <video controls key="/resource/sample.mp4" >
                                    <source src="/resource/sample.mp4" type="video/mp4" />
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="fixed-bottom">
                        <button class="btn btn-primary mb-2"
                            onClick={this.changeBlockStatus} >即時串流</button>
                    </div>
                </div>
            </div>
        )
    }
}
class TabBlock extends React.Component {
    render() {
        return (
            <div class="row row-cols-1 row-cols-lg-1 mx-1">
                <nav>
                    <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">全部</a>
                        <a class="nav-item nav-link" id="nav-en-tab" data-toggle="tab" href="#nav-en" role="tab" aria-controls="nav-profile" aria-selected="false">CNC</a>
                        <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">铣床</a>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div name="zh" class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                        <div class="row">
                            <div class="col my-2">
                                <button type="button" class="btn btn-primary mb-2 float-right">排序設定</button>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-xl-4 my-2">
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                        </div>
                    </div>
                    <div name="en" class="tab-pane fade show" id="nav-en" role="tabpanel" aria-labelledby="nav-en-tab">
                        <div class="row">
                            <div class="col my-2">
                                <button type="button" class="btn btn-primary mb-2 float-right">排序設定</button>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-xl-4 my-2">
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                        </div>
                    </div>
                    <div name="first" class="tab-pane fade show" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                        <div class="row">
                            <div class="col my-2">
                                <button type="button" class="btn btn-primary mb-2 float-right">排序設定</button>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-xl-4 my-2">
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                            <div class="col">
                                <img src="/img/Logo.png" class="img-fluid img-thumbnail rounded float-left" alt="..." />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        )
    }
}
class NewBlock extends React.Component {
    render() {
        return (
            <div>
                <div class="row">
                    <div class="col-10">
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                最新消息
                            </div>
                            <div class="card-body">

                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button
                            type="button"
                            class="btn btn-primary mb-2"
                            onClick={(e) => {
                                e.preventDefault();
                                window.location.href = 'http://google.com';
                            }}
                        > Add</button>
                    </div>
                </div>
            </div>

        )
    }
}
const element =
    <div>
        <div>
            <StreamingVideo />
        </div>
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">數位典藏（首件）</div>
                <div class="card-body">
                    <div class="row my-1">
                        <div class="col-6">
                            <SearchBlock />
                        </div>
                        <div class="col-6">
                            <NewBlock />
                        </div>
                    </div>
                    <div class="row my-1">
                        <div class="col-12">
                            <TabBlock />
                        </div>
                    </div>
                    <div class="row my-1">
                        <div class="col-6">
                            <CardPicture
                                title="客戶圖"
                            />
                        </div>
                        <div class="col-6">
                            <CardPicture
                                title="製程階層圖"
                            />
                        </div>
                    </div>
                    <hr />
                    <div class="row my-1">
                        <div class="col-12">
                            <ProductionManagementRemindBlock />
                        </div>
                    </div>
                    <hr />
                    <div class="row my-1">
                        <div class="col-6">
                            <VideoBlock />
                        </div>
                        <div class="col-6">
                            <RecordBlock />
                        </div>
                    </div>
                    <hr />
                    <div class="row my-1">
                        <div class="col-auto">
                            <LanguageBlock />
                        </div>
                    </div>
                    <hr />
                </div>
            </div>
        </div>
    </div>
ReactDOM.render(element, document.getElementById('root'));
