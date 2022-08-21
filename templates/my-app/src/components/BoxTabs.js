import React from 'react';
import { Tabs, Tab } from 'react-bootstrap';
import TabContent from './TabContent';

class BoxTabs extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            key: 'record',
            tabs: this.props.tabsControl,
            datatables: this.props.datatables,
        }
        this.setKey = this.setKey.bind(this);
    }

    setKey(props) {
        this.setState({
            key: props
        })
        this.props.handlerClick(props)
    }

    render() {
        let row = this.state.tabs;
        let row_datatables = this.state.datatables;
        let post_process = this.props.postProcess;
        console.log(this.state.key)
        return (
            <Tabs id="controlled-tab-example" activeKey={this.state.key} onSelect={this.setKey} className="mb-3">
                {this.state.tabs.map((data, index) =>
                    <Tab key={data.eventKey} eventKey={data.eventKey} title={data.title}>
                        <TabContent tableRef={this.props.tableRef[index]} rowClickedHandler={this.props.rowClickedHandler}
                            datatables={row_datatables[index] }
                            postProcess={post_process} api_location={data.api_location} />
                    </Tab>
                )}
            </Tabs>
        )
    }
}
export default BoxTabs