import React, { PureComponent } from 'react';
import { Row, Col, Image, Button } from "react-bootstrap";
import ReactCrop from 'react-image-crop';
import axios from "axios";
import Axios from 'axios';
import BasicModal from './BasicModal';
import Magnifier from './quotation/Magnifier';
import { GiCancel } from "react-icons/gi";
import 'react-image-crop/dist/ReactCrop.css';

class DynamicCrop extends PureComponent {
	constructor(props) {
		super(props);
		this.state = {
			src: null,
			old_src: null,
			crop: {
				unit: '%',
				width: 30,
				aspect: 16 / 9
			},
			upload: {
				allowType: ['image/jpg', 'image/jpeg', 'image/png', 'video/mp4', 'application/pdf', 'application/vnd.ms-excel'],
				request_data: {},
				API_location: "/3DConvert/PhaseGallery/order_processes/reprocess_image",
				source: Axios.CancelToken.source(),
			},
			parant_data: this.props.parent_row,
			request_draw: this.props.request,
			request_upload: this.props.request_upload,
			request_delete_crop: this.props.request_delete_crop,
			client_name: 'deleteImg.jpg',
			modal: {
				show: false,
				size: 'sm'
			},
			order_processes: undefined,
			order_processes_file: undefined,
			formDataTemp: undefined,
			enableDragDrop: true,
			is_first_login: true,
		}

		this.child = React.createRef();
		this.onImageChange = this.onImageChange.bind(this);
		this.cropChange = this.cropChange.bind(this);
		this.deleteImg = this.deleteImg.bind(this);
		this.fetchUsers = this.fetchUsers.bind(this);
		this.convertUrlToImageData = this.convertUrlToImageData.bind(this);
		this.getBlobFromUrl = this.getBlobFromUrl.bind(this);
		this.rectSave = this.rectSave.bind(this);
		this.cropBigger = this.cropBigger.bind(this);
		this.callbackReturn = this.callbackReturn.bind(this);
	}

	componentDidMount() {
		if (JSON.stringify(this.state.order_processes) !== JSON.stringify(this.props.parentRow.order_processes) || JSON.stringify(this.state.order_processes_file) !== JSON.stringify(this.props.parentRow.order_processes_file)) {
			let upload_temp = { ...this.state.upload };
			upload_temp['request_data'] = {
				order_processes_id: this.props.parentRow.order_processes_id,
			};
			this.setState({
				order_processes: this.props.parentRow.order_processes,
				order_processes_file: this.props.parentRow.order_processes_file,
				upload: upload_temp,
			})
		}
	}

	componentDidUpdate(prevState, prevProps) {
		if (JSON.stringify(this.props.parentRow.order_processes) !== JSON.stringify(this.state.order_processes) || JSON.stringify(this.props.parentRow.order_processes_file) !== JSON.stringify(this.state.order_processes_file)) {
			let upload_temp = { ...this.state.upload };
			upload_temp['request_data'] = {
				order_processes_id: this.props.parentRow.order_processes_id,
			};
			this.setState({
				order_processes: this.props.parentRow.order_processes,
				order_processes_file: this.props.parentRow.order_processes_file,
				upload: upload_temp,
			})
		}
	}

	getFileId = (img) => {
		let transData = '';
		let formData = new FormData();
		if (img !== undefined) {
			transData = this.str2blob(img);
			formData.append('inputFile', transData, this.state.client_name);
			formData.append('order_processes_id', this.props.parentRow.order_processes_id);

			this.setState({
				formDataTemp: formData,
			})
		}
	}

	onImageLoaded = (image) => {
		this.imageRef = image;
	};

	onCropComplete = (crop) => {
		this.makeClientCrop(crop);
	};

	onCropChange = (crop, percentCrop) => {
		if (this.state.is_first_login) {
			crop.width = 0;
			crop.height = 0;
			this.setState({ crop, is_first_login: false });
		} else {
			this.setState({ crop });
		}

	};

	async makeClientCrop(crop) {
		if (this.imageRef && crop.width && crop.height) {
			const croppedImageUrl = await this.getCroppedImg(
				this.imageRef,
				crop,
				'newFile.jpg'
			);
			this.setState({ cropUrl: croppedImageUrl });
		}
	}

	fetchUsers() {
		document.getElementById("updfile").click()
	}

	rectSave() {
		axios
			.post(`/3DConvert/PhaseGallery/order_processes/subfile_image/upload`, this.state.formDataTemp, {
				headers: {
					'Content-Type': 'multipart/form-data'
				}
			})
			.then((response) => {
				this.props.addRectData({
					background_src: `${axios.defaults.baseURL}/3DConvert/PhaseGallery/order_image/${response.data.file_id}`,
					order_processes_subfile_id: response.data.order_processes_subfile_id,
				})
			});
	}

	getCroppedImg(image, crop, fileName) {
		const canvas = document.createElement('canvas');
		const pixelRatio = window.devicePixelRatio;
		const scaleX = image.naturalWidth / image.width;
		const scaleY = image.naturalHeight / image.height;
		const ctx = canvas.getContext('2d');

		canvas.width = crop.width * pixelRatio * scaleX;
		canvas.height = crop.height * pixelRatio * scaleY;

		ctx.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
		ctx.imageSmoothingQuality = 'high';

		ctx.drawImage(
			image,
			crop.x * scaleX,
			crop.y * scaleY,
			crop.width * scaleX,
			crop.height * scaleY,
			0,
			0,
			crop.width * scaleX,
			crop.height * scaleY
		);

		return new Promise((resolve, reject) => {
			canvas.toBlob(
				(blob) => {
					if (!blob) {
						//reject(new Error('Canvas is empty'));
						console.error('Canvas is empty');
						return;
					}
					blob.fileName = fileName;
					window.URL.revokeObjectURL(this.fileUrl);
					this.fileUrl = window.URL.createObjectURL(blob);
					resolve(this.fileUrl);
					this.getFileId(blob)
				},
				'image/jpeg'
			);
		});
	}

	resetPic(old_src) {
		this.setState({
			src: old_src,
			old_src: old_src,
		})
	}

	onImageChange = event => {
		let file = event.target.files[0];
		let type = event.target.files[0].type;
		this.UploadImg(file, type);
		file = '';
		type = '';
	};

	UploadImg = (file, type) => {
		var config = { responseType: 'blob' };
		// 到時候要是可調的，允許之型態
		const supportedFilesTypes = this.state.upload.allowType;
		if (supportedFilesTypes.indexOf(type) > -1) {
			var payload = new FormData();
			payload.append('inputFile', file);
			Object.keys(this.state.upload.request_data).map((key, i) => {
				payload.append(key, parseInt(this.state.upload.request_data[key]));
			})

			axios.post(this.state.upload.API_location, payload, {
				// cancelToken: this.state.upload.source.token,
				headers: {
					'Content-Type': 'multipart/form-data'
				},
			}).then(response => {
				let order_processes_file_temp = [...this.state.order_processes_file];
				let return_data = {
					file_id: response.data.file_id,
					order_processes_file_id: response.data.order_processes_file_id,
				}
				order_processes_file_temp.push(return_data);
				this.setState({
					modal: {
						modal_title: "上傳結果",
						modal_body: "上傳成功",
						show: true,
						size: 'md',
					}
				});
				this.props.addCropData(return_data)
				this.callbackReturn();
				// this.child.current.openModal();
			})
			// controller.abort();
			this.setState({ enableDragDrop: false });
		}
		else {
			this.setState({ preview: null, status: "此檔案無法上傳，請再次點擊或拖拉至此", fetchSuccess: true });
		}
	}

	cropChange(e) {
		const crop_src = e.target.getAttribute('src');
		const is_origin = e.target.getAttribute('is_origin');
		if (!is_origin) {
			this.convertUrlToImageData(crop_src);
		} else {
			this.setState({
				src: crop_src,
				is_first_login: false,
			});
		}
	}

	getBlobFromUrl = (myImageUrl) => {
		axios
			.get(myImageUrl, { responseType: 'blob' })
			.then((response) => {
				this.getDataFromBlob(response.data);
			})
	}

	getDataFromBlob = (myBlob) => {
		let reader = new FileReader();
		reader.onload = () => {
			this.setState({
				src: reader.result,
				is_first_login: false,
			});
		};
		reader.readAsDataURL(myBlob);
	}

	convertUrlToImageData = async (myImageUrl) => {
		try {
			this.getBlobFromUrl(myImageUrl);
		} catch (err) {
			console.log(err);
			return null;
		}
	}

	deleteImg(e) {
		let order_processes_file_id_temp = parseInt(e.target.closest('.card').getAttribute('deledata'));
		let order_processes_reprocess_row_index = e.target.closest('.card').getAttribute('crop_row');
		this.props.deleteCrop(order_processes_file_id_temp, order_processes_reprocess_row_index);
	}

	cropBigger() {
		this.setState({
			modal: {
				modal_body: <Magnifier alt='no image' src={this.state.src}></Magnifier>,
				show: false,
				size: 'lg',
			}
		});
		this.child.current.openModal();
	}

	changeFileArray() {
		this.setState({
			order_processes_file: this.props.parentRow.order_processes_file,
		})
	}

	callbackReturn() {
		let modal_temp = { ...this.state.modal };
		setTimeout(function () {
			modal_temp['show'] = false;
			this.setState({
				modal: modal_temp,
			})
		}.bind(this), 1000)
	}

	str2blob = txt => new Blob([txt]);

	render() {
		const { crop, src, old_src } = this.state;
		return (
			<>
				<Col md='12' className='mb-2'>
					<Row>
						<Button className="mx-2" variant="secondary" onClick={this.fetchUsers} style={{ width: 'auto', background: "#5e789f", color: "white", fontWeight: "bold" }}>上傳底圖</Button>
						<Button className="mx-2" variant="secondary" onClick={this.rectSave} style={{ width: 'auto', background: "#5e789f", color: "white", fontWeight: "bold" }}>截圖</Button>
						<Button className="mx-2" variant="secondary" onClick={this.cropBigger} style={{ width: 'auto', background: "#5e789f", color: "white", fontWeight: "bold" }}>觀看底圖(大)</Button>
						{this.state.modal.show ? <Col style={{ color: "#B22222", fontWeight: "bold" }}>{this.state.modal.modal_body}</Col> : ''}
						<input id="updfile" type="file" ref={this.fileInputRef} onChange={(e) => this.onImageChange(e)} hidden />
					</Row>
				</Col>
				<Col className='overflow-auto d-flex'>
					{
						<Col>
							<div className="card" style={{ width: "8rem" }}>
								<Image alt='no image' className='card-img-top position-relative mt-3' is_origin="true" src={old_src} onClick={this.cropChange}></Image>
							</div>
						</Col>
					}
					{this.state.order_processes_file && this.state.order_processes_file.map((row, index) => {
						return (
							<Col>
								<div className="card" key={index} deledata={row.order_processes_file_id} crop_row={index} style={{ width: "8rem" }}>
									<Image alt='no image' className='card-img-top position-relative mt-3' src={`${axios.defaults.baseURL}/3DConvert/PhaseGallery/order_image/${row.file_id}`} crop_row={index} onClick={this.cropChange}></Image>
									<GiCancel className="icon position-absolute" onClick={this.deleteImg} style={{ top: 0, right: 0 }} />
								</div>
							</Col>
						)
					})}
				</Col>
				{
					//等接後端
				}
				{/* <Row>
					{Object.keys(this.props.componentData.return_data).map((key, index) => {
						return (
							<Col>{this['props']['componentData']['label'][key]}: {this['props']['componentData']['return_data'][key]}</Col>
						)
					})
					}
				</Row> */}
				<Row className='mt-3'>
					<Col>
						{src && (
							<ReactCrop
								src={src}
								crop={crop}
								ruleOfThirds
								onImageLoaded={this.onImageLoaded}
								onComplete={this.onCropComplete}
								onChange={this.onCropChange}
							/>
						)}
					</Col>
				</Row>
				<BasicModal
					modal_title={this.state.modal.modal_title}
					modal_body={this.state.modal.modal_body}
					show={this.state.modal.show}
					ref={this.child}
					size={this.state.modal.size}
				></BasicModal>
			</>
		);
	}
}

export default DynamicCrop