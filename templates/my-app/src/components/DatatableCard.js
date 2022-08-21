import React from "react";
import axios from "axios";
import { Card, ListGroup } from "react-bootstrap";
import DataTable from "react-data-table-component";

class DatatableCard extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			data: this.props.datatables.require,
			request: this.props.datatables.request,
			request_label: this.props.datatables.request_label,
			loading: false,
			totalRows: 0,
			cur_page: 1, //目前資料頁數
			size: 12, //一次資料總數
			row_size: 4, //一行資料總數
			columns: [],
			totalReactPackages: [],
		};
		this.customizeCard = this.customizeCard.bind(this);
		this.imgBridgeTransport = this.imgBridgeTransport.bind(this);
		this.dataTableCard = React.createRef();
	}

	componentDidMount() {
		this.fetchUsers();
	}

	sync_pic(data) {
		let totalReactPackages_temp = [...this.state.totalReactPackages];
		let params = this.state.data;
		params["size"] = this.state.size;
		params["cur_page"] = this.state.cur_page;
		params["row_size"] = this.state.row_size;
		totalReactPackages_temp[data.temp_place.row][data.temp_place.col]['src'] = data.return_image + "?" + new Date();
		totalReactPackages_temp[data.temp_place.row][data.temp_place.col]['image'] = [data.return_image + "?" + new Date()];
		totalReactPackages_temp[data.temp_place.row][data.temp_place.col]['src_file_id'] = data.return_image_file_id;
		totalReactPackages_temp[data.temp_place.row][data.temp_place.col]['file_exists'] = true;
		let columns = this.getColumns();
		this.setState({
			totalReactPackages: totalReactPackages_temp,
			columns: columns,
		})
		this.fetchUsers();
		console.log(totalReactPackages_temp)
	}

	customizeCard(data) {
		let parent_row = this.props.customizeCardGrandParent(data);
		return parent_row;
	}

	imgBridgeTransport(e, cardImg) {
		this.props.imgTransport(e, cardImg);
	}

	getColumns() {
		let thead = [];
		for (let i = 0; i < this.state.row_size; i++) {
			thead.push({
				cell: (row, row_id) =>
				(
					<DATATABLE_CARD
						customizeCard={this.customizeCard}
						src={row}
						id={i}
						row_id={row_id}
						label={this.state.request_label}
						imgBridgeTransport={this.imgBridgeTransport}
						ref={this.dataTableCard}
					></DATATABLE_CARD>
				),
			});
		}
		return thead;
	}

	fetchUsers() {
		let columns = this.getColumns();
		let params = this.state.data;
		params["size"] = this.state.size;
		params["cur_page"] = this.state.cur_page;
		params["row_size"] = this.state.row_size;
		this.setState({
			columns: columns,
			loading: true,
		});

		axios
			.get(`${this.state.request}`, {
				params: params,
			})
			.then((response) => {
				response = this.props.postProcess(response);
				this.setState({
					totalReactPackages: response.data.data,
					totalRows: response.data.total,
					loading: false,
				});
			});
	}

	handlePageChange = (page, total) => {
		this.setState(
			{
				cur_page: page,
			},
			() => {
				this.fetchUsers();
			}
		);
	};

	handlePerRowsChange = (size, page) => {
		this.setState(
			{
				size: size,
				cur_page: page,
			},
			() => {
				this.fetchUsers();
			}
		);
	};

	render() {
		return (
			<DataTable
				columns={this.state.columns}
				data={this.state.totalReactPackages}
				progressPending={this.state.loading}
				pagination
				paginationServer
				paginationTotalRows={this.state.totalRows}
				paginationDefaultPage={this.state.data.cur_page}
				onChangeRowsPerPage={this.handlePerRowsChange}
				onChangePage={this.handlePageChange}
				onSelectedRowsChange={({ selectedRows }) => console.log(selectedRows)}
			/>
		);
	}
}

class DATATABLE_CARD extends React.Component {
	constructor(props) {
		super(props);
		let reponese_data = this.props.src;
		reponese_data["req_id"] = this.props.id;
		reponese_data["row_id"] = this.props.row_id;

		let card_data = this.props.customizeCard(reponese_data);
		card_data["label"] = this.props.label;
		this.state = { ...card_data };
		this.pictureClick = this.pictureClick.bind(this);
	}

	componentDidMount() {
		this.state.image_temp !== undefined
			? this.getImage(this.state.image_temp)
			: this.setState({ image_temp: "" });
	}

	componentDidUpdate(prevProps, prevState) {
		// 常見用法（別忘了比較 prop）：
		if (this.state.file_exists !== this.props['src'][this.props.id]['file_exists'] || (this.state.image.length === 0 && this.props['src'][this.props.id]['src'] !== undefined)) {
			let reponese_data = this.props.src;
			reponese_data["req_id"] = this.props.id;
			reponese_data["row_id"] = this.props.row_id;
			let card_data = this.props.customizeCard(reponese_data);
			card_data["label"] = this.props.label;
			card_data['image_temp'] !== undefined
				? card_data['image'] = [card_data['image_temp']]
				: this.setState({ image_temp: "" });
			this.setState(card_data);
		}
	}

	getImage(file_id) {
		axios.get(`${file_id}`, { responseType: "blob" }).then((response) => {
			var reader = new window.FileReader();
			reader.readAsDataURL(response.data);
			reader.onload = (e) => {
				let img = new Image();
				var imageDataUrl = reader.result;
				let images = [];
				images.push(imageDataUrl);

				img.src = imageDataUrl;
				img.onload = (e) => {
					this.setState({
						pic_size: {
							width: e.target.width,
							height: e.target.height,
						},
						image: images,
					});
				};
			};
		});
	}

	pictureClick = (e) => {
		this.props.imgBridgeTransport(this.state, e.target);
	}

	render() {
		const state_temp = JSON.parse(JSON.stringify(this.state));
		const row = state_temp.datas;
		const src = state_temp.image[0];
		const label = state_temp.label;
		const pic_size = { ...state_temp.pic_size };
		const cardSize = {
			width: 28.2,
			height: 20,
		};
		let new_width = pic_size.width;
		if (pic_size.width !== undefined && pic_size.width > 225.6) {
			new_width = `${cardSize.width / 2}rem`;
		}

		return (
			<Card style={{ width: `${cardSize.width / 2}rem`, height: `${cardSize.height}rem` }} className="mx-3">
				<Card.Img
					style={{ width: new_width, height: `${cardSize.height / 2}rem` }}
					className="mx-auto"
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

export default DatatableCard;
