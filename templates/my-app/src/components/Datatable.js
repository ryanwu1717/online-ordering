import React from "react";
import axios from "axios";
import DataTable from "react-data-table-component";
import DatatableCard from "./DatatableCard"

class Datatable extends DatatableCard {
	constructor(props) {
		super(props);
		this.state = {
			data: this.props.datatables.require,
			loading: false,
			totalRows: 0,
			cur_page: 1, //目前資料頁數
			size: 10, //一次資料總數
			row_size: 5, //一行資料總數
			columns: [],
			totalReactPackages: [],
			date: "2022-02-21",
			// date: new Date().getFullYear() + "-" + this.addz(((new Date().getMonth() + 1).toString()), 2) + "-" + this.addz((new Date().getDate().toString()), 2)
		};
		this.customizeCard = this.customizeCard.bind(this);
		this.handleDelete = this.handleDelete.bind(this);


	}

	addz(num, length) {
		if (num.length >= length) { return num }
		else {
			return this.addz(("0" + num), length)
		}
	}


	fetchUsers() {
		console.log(this.props.datatables.require)
		let columns = this.props.datatables.thead;
		let params = this.props.datatables.require;
		params["size"] = this.state.size;
		params["cur_page"] = this.state.cur_page;
		params['date'] = this.state.date;

		this.setState({
			columns: columns,
			loading: true,
		});
		axios
			.get(`${this.props.api_location}`, {
				params: params,
			})
			.then((response) => {
				response = this.props.postProcess(response);
				// add serial id
				let cur_page = parseInt(this.state.cur_page);
				response.data.data.map((value, index) => (
					value["id"] = ((cur_page - 1) * 10) + index + 1
				))
				this.setState({
					totalReactPackages: response.data.data,
					totalRows: response.data.total,
					loading: false,
				});
				// console.log(this.state.totalReactPackages)
			});
		// this.setState({
		//         totalReactPackages: this.state.data,
		//         totalRows: 20,
		//         loading: false,
		//   });
	}
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
	handleDelete = (arr) => {
		let params = this.props.datatables.require
		console.log(params)
		axios
			.delete(`${this.props.api_location}`, {
				data: params,
			})
			.then((response) => {
				this.fetchUsers()

			});
	}


	render() {
		return (
			<DataTable
				id={this.props.id}
				ref={this.props.ref}
				columns={this.state.columns}
				data={this.state.totalReactPackages}
				progressPending={this.state.loading}
				pagination
				paginationServer
				paginationTotalRows={this.state.totalRows}
				paginationDefaultPage={this.state.data.cur_page}
				onChangeRowsPerPage={this.handlePerRowsChange.bind(this)}
				onChangePage={this.handlePageChange.bind(this)}
				onSelectedRowsChange={({ selectedRows }) => console.log(selectedRows)}
				onRowClicked={this.props.rowClickedHandler}
				defaultSortFieldId={5}
				defaultSortAsc={true}
				sortServer={this.props.sortDatatble | false}
				onSort={this.props.handleSort}
				expandableRows={this.props.expandableRows}
				expandableRowsComponent={this.props.expandedComponent}
				expandOnRowClicked={this.props.expandableRows}
				expandableRowsHideExpander={this.props.expandableRows}
				expandableRowsComponentProps={this.props.expandableRowsComponentProps}
			/>
		);
	}
}


export default Datatable;
