import React, { useState, useEffect } from "react";
import axios from "axios";
import { Card, ListGroup } from "react-bootstrap";
import DataTable from "react-data-table-component";
import { useSSRSafeId } from "@react-aria/ssr";

function DatatableCardFunctional (props) {
	const [row_size,setRowSize] = useState(5);
    const [columns, setColumns] = useState(getColumns());
    const [data, setData] = useState(props.datatables.totalReactPackages);
    const [count, setCount] = useState(0);
    const [loading,setLoading] = useState(false);
    // require: {
    //     coptd_td001: null,
    //     coptd_td002: null,
    //     coptd_td003: null,
    //     coptc_tc003: null,
    //     date_begin: todayDate,
    //     date_end: todayDate,
    //     row_size: null,
    // },
    // request_label: {
    //     coptd_td001: '單別',
    //     coptd_td002: '單號',
    //     coptd_td003: '序號',
    //     coptc_tc003: '客戶代號',
    // },
    // request: '/3DConvert/PhaseGallery/order',
    useEffect(()=>{
        setColumns(getColumns());
    },[data]);
    function getColumns() {
		let thead = [];
		for (let i = 0; i < row_size; i++) {
			thead.push({
				cell: (row) => {
                    return (
					<DATATABLE_CARD
						customizeCard={()=>{
							return {
								src:i,
								req_id:i,
								label:{
									title: 'Title',
									year: 'Year',
								},
								datas:data	
							}
						}}
						src={row}
						id={i}
						label={{
							title: 'Title',
							year: 'Year',
                        }}
						imgBridgeTransport={()=>{}}
					></DATATABLE_CARD>
				    )
                }
			});
		}
		return thead;
	}
    return (
        <DataTable
            columns={columns}
            // ={this.state.columns}
            data={data}
            // // ={this.state.totalReactPackages}
            progressPending={loading}
            // // ={this.state.loading}
            pagination
            paginationServer
            // paginationTotalRows
            // // ={this.state.totalRows}
            // paginationDefaultPage
            // // ={this.state.data.cur_page}
            // onChangeRowsPerPage
            // // ={this.handlePerRowsChange}
            // onChangePage
            // // ={this.handlePageChange}
            // onSelectedRowsChange
            // // ={({ selectedRows }) => console.log(selectedRows)}
        />
    );
}
export default DatatableCardFunctional;



class DATATABLE_CARD extends React.Component {
	constructor(props) {
		super(props);
		let reponese_data = this.props.src;
		reponese_data["req_id"] = this.props.id;

		let card_data = this.props.customizeCard(reponese_data);
		card_data["label"] = this.props.label;
		this.state = card_data;
		this.pictureClick = this.pictureClick.bind(this);
	}

	componentDidMount() {
		this.state.image_temp !== undefined
			? this.getImage(this.state.image_temp)
			: this.setState({ image_temp: "" });
	}

	getImage(file_id) {
		axios.get(`${file_id}`, { responseType: "blob" }).then((response) => {
			var reader = new window.FileReader();
			reader.readAsDataURL(response.data);
			reader.onload = (e) => {
				var imageDataUrl = reader.result;
				let images = this.state.image;
				images.push(imageDataUrl);
				this.setState({ image: images });
			};
		});
	}

	pictureClick = (e) => {
		this.props.imgBridgeTransport(this.state, e.target);
	}

	render() {
		const row = this.state.datas;
		// const src = this.state.image[0];
		const src = '';
		const label = this.state.label;
		return (
			<Card style={{ width: "15rem" }} className="mt-3">
				<Card.Img
					variant="top"
					style={{ width: "15rem", height: "30vh" }}
					src={src}
					onClick={this.pictureClick}
				/>
				{Object.keys(row).map((key, value) => (
					<ListGroup variant="flush">
						<ListGroup.Item key={value}>
							{label[key]}：{this.state.datas[key]}
						</ListGroup.Item>
					</ListGroup>
				))}
			</Card>
		);
	}
}