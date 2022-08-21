import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Row from 'react-bootstrap/Row';
import "bootstrap/dist/js/bootstrap.bundle.js";
import "bootstrap/dist/css/bootstrap.css";
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Pie, getElementsAtEvent } from 'react-chartjs-2';

ChartJS.register(ArcElement, Tooltip, Legend);
class CategoryBlock extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			backgroundColor: [
				"rgba(95, 173, 86 ,0.5)", "rgba(169, 183, 82,0.5)", "rgba(242, 193, 78,0.5)", "rgba(245, 161, 81,0.5)",
				"rgba(247, 129, 84,0.5)", "rgba(205, 133, 93,0.5)", "rgba(162, 137, 102,0.5)", "rgba(77, 144, 120,0.5)",
				"rgba(180, 67, 108,0.5)", "rgba(50, 57, 57,0.5)", "rgba(129, 106, 114,0.5)",
				"rgba(164, 186, 183,0.5)", "rgba(202, 214, 188,0.5)", "rgba(239, 242, 192,0.5)", "rgba(202, 139, 113,0.5)",
				"rgba(158, 112, 163,0.5)", "rgba(113, 85, 213,0.5)", "rgba(86, 101, 99,0.5)"],
			labels: props.datas.labels,
			data: props.datas.data,
			options: {
				plugins: {
					legend: {
						labels: {
							font: {
								size: 20
							}
						}
					},
				}
			},
			number: 0,
		};
		// this.setBackgroundColor = this.setBackgroundColor.bind(this);
		// this.getBackgroundColor = this.getBackgroundColor.bind(this);
		this.changeLabelAndData = this.changeLabelAndData.bind(this);
		this.triggerTooltip = this.triggerTooltip.bind(this);
		this.onMouseEnter = this.onMouseEnter.bind(this);
		this.chartRef = React.createRef();

	}
	componentDidMount() {
		// this.setBackgroundColor(this.state.data)

	}
	componentDidUpdate(prevProps) {
		// this.setBackgroundColor()
		if (this.props.number !== prevProps.number) {
			this.triggerTooltip(this.chartRef.current)
		}
	}
	triggerTooltip(chart: ChartJS | null) {
		const tooltip = chart?.tooltip;
		tooltip.setActiveElements([], { x: 0, y: 0 });
		if (!tooltip) {
			return;
		}
		if (tooltip.getActiveElements().length > 0) {
			tooltip.setActiveElements([], { x: 0, y: 0 });
		} else {
			const { chartArea } = chart;
			tooltip.setActiveElements(
				[
					{
						datasetIndex: 0,
						index: this.props.number,
					},
				],
				{
					x: (chartArea.left + chartArea.right) / 2,
					y: (chartArea.top + chartArea.bottom) / 2,
				}
			);
		}
		chart.update();
	}
	// getBackgroundColor() {
	//   let r = Math.floor(Math.random() * 255);
	//   let g = Math.floor(Math.random() * 255);
	//   let b = Math.floor(Math.random() * 255);
	//   let a = 0.5;
	//   return `rgba(${r},${g},${b},${a})`;
	// }
	// setBackgroundColor(inputdata) {
	//   let length = inputdata.length;
	//   let  backgroundColorArr = new Array();
	//   for (var i = 0; i < length; i++) {
	//     backgroundColorArr.push(this.getBackgroundColor());
	//   }
	//   this.setState({
	//     backgroundColor : backgroundColorArr
	//   })
	// }
	changeLabelAndData(labels, data) {
		this.setState({
			labels: labels,
			data: data
		})
		// this.setBackgroundColor(this.state.data)
	}

	renderCustomizedLabel = ({ cx, cy, midAngle, innerRadius, outerRadius, percent, index }) => {
		const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
		const x = cx + radius;
		const y = cy + radius;

		return (
			<text x={x} y={y} fill="white" textAnchor={x > cx ? 'start' : 'end'} dominantBaseline="central">
				{`${(percent * 100).toFixed(0)}%`}
			</text>
		);
	};
	onMouseEnter = (event) => {
		if(getElementsAtEvent(this.chartRef.current, event).length !== 0 ) {
			this.setState({number: getElementsAtEvent(this.chartRef.current, event)[0].index})
			this.props.handleSetTable(getElementsAtEvent(this.chartRef.current, event)[0].index)
		}
	}

	render() {
		const data = {
			labels: this.state.labels,
			datasets: [
				{
					label: '# of Votes',
					data: this.state.data,
					backgroundColor: this.state.backgroundColor,
				},
			],
		};
		return (
			<Row>
				{this.state.backgroundColor.length ?
					<Pie
						data={data}
						ref={this.chartRef}
						options={this.state.options}
						onMouseMove={this.onMouseEnter}
						onMouseLeave={this.props.handleSetLabelLeave}
						 /> : null}
			</Row>
		);
	}
}

export default CategoryBlock;
