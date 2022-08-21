<?php include(__DIR__ . '/..//basic/header.html'); ?>
<style>
	.modal {
		overflow-y: auto;
	}

	.select-wrapper {
		margin: auto;
		max-width: 600px;
		width: calc(100% - 40px);
	}

	.select-pure__select {
		align-items: center;
		background: #f9f9f8;
		border-radius: 4px;
		border: 1px solid rgba(0, 0, 0, 0.15);
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
		box-sizing: border-box;
		color: #363b3e;
		cursor: pointer;
		display: flex;
		font-size: 16px;
		font-weight: 500;
		justify-content: left;
		min-height: 44px;
		padding: 5px 10px;
		position: relative;
		transition: 0.2s;
		width: 100%;
	}

	.select-pure__options {
		border-radius: 4px;
		border: 1px solid rgba(0, 0, 0, 0.15);
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
		box-sizing: border-box;
		color: #363b3e;
		display: none;
		left: 0;
		max-height: 221px;
		overflow-y: scroll;
		position: absolute;
		top: 50px;
		width: 100%;
		/* position:fixed; */
		z-index: 99999;
	}

	.select-pure__select--opened .select-pure__options {
		display: block;
	}

	.select-pure__option {
		background: #fff;
		border-bottom: 1px solid #e4e4e4;
		box-sizing: border-box;
		height: 44px;
		line-height: 25px;
		padding: 10px;

	}

	.select-pure__option--selected {
		color: #e4e4e4;
		cursor: initial;
		pointer-events: none;
	}

	.select-pure__option--hidden {
		display: none;
	}

	.select-pure__selected-label {
		background: #5e6264;
		border-radius: 4px;
		color: #fff;
		cursor: initial;
		display: inline-block;
		margin: 5px 10px 5px 0;
		padding: 3px 7px;
	}

	.select-pure__selected-label:last-of-type {
		margin-right: 0;
	}

	.select-pure__selected-label i {
		cursor: pointer;
		display: inline-block;
		margin-left: 7px;
	}

	.select-pure__selected-label i:hover {
		color: #e4e4e4;
	}

	.select-pure__autocomplete {
		background: #f9f9f8;
		border-bottom: 1px solid #e4e4e4;
		border-left: none;
		border-right: none;
		border-top: none;
		box-sizing: border-box;
		font-size: 16px;
		outline: none;
		padding: 10px;
		width: 100%;
	}
</style>

<script src="/dropzone/dist/dropzone.js"></script>
<link rel="stylesheet" href="/dropzone/dist/dropzone.css">
<link rel="stylesheet" href="/vendor/select-pure/dist/select-pure.css">

<div class="row">
	<!-- search -->
	<div class="col-12">
		<div class="card shadow mb-4">
			<div class="card-body d-flex overflow-auto">
				<div class="d-flex align-self-center" id="list-tab-business">
					<ul class="list-group list-group-horizontal w-100">
						<li class="list-group-item flex-fill w-100">上傳圖檔</li>
						<li class="list-group-item flex-fill w-100">查詢歷史訂單</li>
						<li class="list-group-item flex-fill w-100">全圖比對</li>
						<li class="list-group-item flex-fill w-100">零件分類</li>
						<li class="list-group-item flex-fill w-100">零件比對</li>
						<li class="list-group-item flex-fill w-100">刻度圈選</li>
						<li class="list-group-item flex-fill w-100">刻度修改</li>
					</ul>
				</div>
				<div class="form-group" id="list-tab-other">
				</div>
				<div class="d-flex align-self-center" id="list-tab-end">
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-12 mb-4">
		<div class="card shadow mb-4 h-100">
			<div class="card-header">製程確認
				<i class="fas fa-exclamation-circle" data-toggle="tooltip" data-placement="top" title="查看研發註記，並確認技術填寫之製程"></i>
			</div>
			<div class="card-body">
				<div class="row rows-col-1 rows-col-md-2">
					<div class="col">
						<ul>
							<li>選擇要呈現的廠內圖數量</li>
							<li>可設定要呈現出的數量</li>
						</ul>
						<div class="form-group row">
							<div class="col-sm-auto form-group row">
								<label class="col-form-label col-auto">相似度門檻：</label>
								<div class="col-auto">
									<select class="form-control" id="selectThreshold">
										<option value="0">0%</option>
										<option value="10">10%</option>
										<option value="20">20%</option>
										<option value="30">30%</option>
										<option value="40">40%</option>
										<option value="50">50%</option>
										<option value="60">60%</option>
									</select>
								</div>
							</div>
							<div class="col-sm-auto form-group row">
								<label class="col-form-label col-auto">參考數量：</label>
								<div class="col-auto">
									<select class="form-control" id="selectAmount">
										<option value="10">10</option>
										<option value="20">20</option>
										<option value="30">30</option>
										<option value="40">40</option>
										<option value="50">50</option>
									</select>
								</div>
								<label class="col-form-label col-auto">張</label>
								<label class="col-form-label col-auto">，篩選結果：10 / 20 張</label>
							</div>
						</div>
					</div>
					<div class="col">
						<ul>
							<li>註記的部分會在生管階段時，看到所留下的註記</li>
							<li>勾選的部分會在業務階段時，看到所留下的相似零件</li>
							<li>按下一步後可送至製圖</li>
						</ul>
						<!-- <button type="button" class="btn btn-primary float-right" onclick="buttonPass()">下一步</button> -->
					</div>
				</div>
				<datalist id="datalistOutresourcer">
					<option value="宏崗"></option>
					<option value="奇鼎"></option>
					<option value="吉兵"></option>
					<option value="中日"></option>
					<option value="九井"></option>
					<option value="鋐誠"></option>
					<option value="增慶"></option>
					<option value="冠程"></option>
					<option value="千耕"></option>
					<option value="正昌"></option>
					<option value="廷翼"></option>
					<option value="保興"></option>
					<option value="原裕"></option>
					<option value="巧鑫"></option>
					<option value="衡泰"></option>
					<option value="家程"></option>
					<option value="銘揚"></option>
					<option value="皇億"></option>
					<option value="昇隆"></option>
					<option value="尚陽"></option>
					<option value="瑞裕"></option>
					<option value="易登盛"></option>
					<option value="鼎欣"></option>
					<option value="銘祐"></option>
					<option value="峻峰"></option>
					<option value="永力昇"></option>
					<option value="豐進"></option>
					<option value="偉程"></option>
					<option value="偉至"></option>
				</datalist>
				<div class="form-group row" id="divImage">
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row" id="discriptOther">
	<!-- search -->
	<!-- <div class="col-12">
    <div class="card shadow mb-4">
      <div class="card-header">
        報價單摘要
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-borderless">
            <thead>
              <tr>
                <th>客戶圖縮圖</th>
                <th>訂單資訊</th>
                <th>註記</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td width=10%>
                  <img class="img-thumbnail" id="imgThumbnail"></td>
                <td>
                  <p>客戶圖號：<span id="spanFileId">1</span></p>
                  <p>開單時間：<span id="spanUploadTime">2021/05/27</span></p>
                </td>
                <td id="tdComment">尚未有任何註記</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div> -->
</div>
<div class="row">
	<div class="col-12 mb-4">
		<div id="production_company_now"></div>
	</div>
	<div class="col-12 mb-4">
		<div id="production_outsourcer_now"></div>
	</div>
</div>


<?php include(__DIR__ . '/../basic/footer.html'); ?>
<script src="/vendor/select-pure/dist/select-pure.bundle.min.js"></script>

<script>
	var setting = {
		"lengthChange": true,
		"destroy": true,
		"info": true,
		"searching": false,
		"order": [],
		"language": {
			"processing": "處理中...",
			"loadingRecords": "載入中...",
			"lengthMenu": "顯示 _MENU_ 項結果",
			"zeroRecords": "沒有符合的結果",
			"info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
			"infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
			"infoFiltered": "(從 _MAX_ 項結果中過濾)",
			"infoPostFix": "",
			"search": "搜尋:",
			"paginate": {
				"first": "第一頁",
				"previous": "上一頁",
				"next": "下一頁",
				"last": "最後一頁"
			},
			"aria": {
				"sortAscending": ": 升冪排列",
				"sortDescending": ": 降冪排列"
			}
		}
	}

	let process = [];

	function sortObject(obj) {
		var arr = [];
		for (var prop in obj) {
			if (obj.hasOwnProperty(prop)) {
				arr.push({
					'key': prop,
					'value': obj[prop]
				});
			}
		}
		arr.sort(function(a, b) {
			return a.value - b.value;
		});
		//arr.sort(function(a, b) { a.value.toLowerCase().localeCompare(b.value.toLowerCase()); }); //use this to sort as strings
		return arr; // returns array
	}

	var url = new URL(window.location.href);
	var id = url.searchParams.get("id");
	var file_id_dest = url.searchParams.get("id");
	var module_id;
	var module_name = '生管';
	var historybool = [];
	var temperarybool = [];
	var allbool = [];
	var responsehistory, responsetemperary, responseall;
	var delivery_week;
	let file_comment_department;
  	let outsourcercost ;
	let itemno

	$(document).on('input', '[name="inputFileComment"]', function() {
		insaveFileComment();

	});

	function insaveFileComment() {
		$.ajax({
			url: `/file/file_comment`,
			type: 'post',
			data: {
				file_id: id,
				module_id: module_id,
				comment: $('[name="inputFileComment"]').val(),

			},
			success: function(response) {

			}
		});
	}

	function getcanvas() {
		$.ajax({
			url: `/file/file_comment/canvas`,
			type: 'get',
			data: {
				file_id: id,
				module_id: module_id,

			},
			success: function(response) {
				$(response).each(function() {
					$('[name="inputFileComment"]').val(this.comment)
				})
			}
		});
	}

	function getFileComment() {
		$.ajax({
			url: `/file/file_comment`,
			type: 'get',
			data: {
				file_id: id,
				module_id: module_id,

			},
			success: function(response) {
				$.each(response, function(key, value) {
					let row = this;
					if (key === 'comment') {
						$(row).each(function() {
							if (this.name === "技術") {
								file_comment_department = this.comment;
							}
						})
					}
				})
			}
		});
	}

  function getoutsourcercost(){
    $.ajax({
			url: `/file/outsourcer/cost`,
			type: 'get',
			data: {
			},
			success: function(response) {
        outsourcercost =response;
      }
    });
  }

	$(function() {
		getInfo();
		getModule();
		get_production_company_now();
		production_outsourcer_now();
		getFileComment();
    	getoutsourcercost();
		if (file_id_dest == null) {
			$('#basicModal').find('.modal-header').text(`系統訊息`);
			$('#basicModal').find('.modal-body').text(`請從全圖比對進入`);
			$('#basicModal').find('.modal-footer').html(`
            <button type="button" class="btn btn-secondary"onclick="javascript:location.href='/file/compare?id=${id}'">前往</button>
        `);
			$('#basicModal').modal('show')
		} else {
			getListState(id);
			// getResult(id);
			getComment();
			getResultComponents()
			getModifyProcess()
		}
	});
	const get_production_company_now = ()=>{
		import('/static/js/production_company_now.js')
	}
	const production_outsourcer_now = ()=>{
		import('/static/js/production_outsourcer_now.js')
	}

	function getInfo() {
		$.ajax({
			url: `/file/information`,
			type: 'get',
			data: {
				file_id: id,
			},
			dataType: 'json',
			success: function(response) {
				$.each(response, function() {
					delivery_week = this.delivery_week;
					itemno  = this.itemno
				})

			}
		});
	}

	var modifyprocess = new Object();

	function getModifyProcess() {
		$.ajax({
			url: `/modifyprocess`,
			type: 'get',
			data: {
				file_id: id
			},
			success: function(response) {
				modifyprocess = response
				// let modifyprocessArr=[];
				// $.each(response,function(){
				//   let tmpcollapse = `#collapseDetail_${this.component_id}_${this.process_id}`;
				//   console.log($(tmpcollapse))
				//   if(!modifyprocessArr.includes(tmpcollapse) ){
				//     $(tmpcollapse).html('')
				//     modifyprocessArr.push(tmpcollapse)
				//   }

				//   $(tmpcollapse).append(`
				//   <li class="ui-state-default list-inline-item col-auto" >
				//       <div class=" form-group row text-nowrap">
				//             <label class="col-form-label col-auto  col-md-5" for="">加工順序</label>
				//             <input class="form-control col-md-6" name="inputModify_process" data-type="num" id="" value="${this.num}">
				//       </div>
				//       <div class=" form-group row text-nowrap">
				//             <label class="col-form-label col-auto  col-md-5" for="">製程代號</label>
				//             <input class="form-control col-md-6" name="inputModify_process" data-type="code" id="" value="${this.code}">
				//       </div>
				//       <div class=" form-group row text-nowrap">
				//             <label class="col-form-label col-auto  col-md-5" for="">製程名稱</label>
				//             <input class="form-control col-md-6" name="inputModify_process" data-type="name" id="" value="${this.name}">
				//       </div>
				//       <div class=" form-group row text-nowrap">
				//             <label class="col-form-label col-auto col-md-5" for="">廠商</label>
				//             <input class="form-control col-md-6" name="inputModify_process" data-type="outsourcer" id="" value="${this.outsourcer}">
				//       </div>
				//       <div class=" form-group row text-nowrap">
				//             <label class="col-form-label col-auto col-md-5" for="">註記</label>
				//             <input class="form-control col-md-6" name="inputModify_process" data-type="mark" id="" value="${this.mark}">
				//       </div>
				//       <div class=" form-group row text-nowrap">
				//             <label class="col-form-label col-auto col-md-5" for="">外包成本</label>
				//             <input class="form-control col-md-6" name="inputModify_process" data-type="cost"  value="${this.cost}">
				//       </div>
				//       <p>歷史追加成本：</p>
				//       <p>2017-05-22：1100</p>
				//       <p>2018-04-23：1400</p>
				//       <p>2019-03-24：1600</p>
				//       <p>2020-05-24：2000</p>
				//       <p>平均值：1525</p>
				//       <p>標準差：326.91</p>
				//       <p>智能建議：2125</p>
				//     </li>
				//   `)



				// })
			}
		});
	}

	function getModule() {
		$.ajax({
			url: `/setting/module`,
			type: 'get',
			data: {},
			dataType: 'json',
			success: function(response) {
				$.each(response, function() {
					if (this.name == module_name) {
						module_id = this.id;
					}

				})
				console.log(module_id)
				getDiscriptOther();
			}
		});
	}

	function getDiscriptOther() {
		window.sharedVariable = {
			file_id: id,
			module_name: '生管',
			module_id: module_id
		};
		$("#discriptOther").load(`/discript/newother`);
	}

	function getComment() {
		$.ajax({
			url: `/file/comment`,
			type: 'get',
			data: {
				file_id: id,
				file_id_dest: file_id_dest
			},
			success: function(response) {
				if (response.length == 0)
					$('#tdComment').html(`尚未有任何註記`)
				else
					$('#tdComment').html(``)
				$(response).each(function() {
					$('#tdComment').append(`
                        <p>${this.module_name}：${this.comment}</p>
                    `);
				})
			}
		})
	}

  $(document).on('change', '[name="inputModify_process"][data-type="code"]', function() {
    let tmpvalue = $(this).val();
    let tmpelement = $(this).closest('li').find('[name="divoutsourcercost"]')
    $(tmpelement).html('<p>歷史追加成本</p>')
    $(outsourcercost).each(function(index,value){
      if($.trim(value.製程代號) == $.trim(tmpvalue)){
        $(value.cost).each(function(){
          $(tmpelement).append(`<p>${this.廠商簡稱} ${this.生效日} : ${Math.round(parseInt(this.單價))}${this.幣別}</p>`)
        });
      }
      
    });
	});

	$(document).on('change', '#selectAmount,#selectThreshold', function() {
		getResultComponents()
	});


	var focusID, focusItemID;

	function inputFocus(resID, resItemID) {
		console.log('22')
		focusID = resID;
		focusItemID = resItemID;
	}


	let crops_arr = new Object();

	function getResultComponents() {
		var processArr = [];
		let haveresult = false;
		$.ajax({
			url: `/processes/crop/${file_id_dest}`,
			type: 'get',
			success: function(response) {
				processArr = response.process
				var compareObj = new Object();
				let crops = $(`<div></div>`);
				$(response.crop).each(function() {
					$(crops).append(`
					<img src="/fileCrop/${this}" style="width:100px;height:100px" class="col figure-img img-thumbnail rounded" alt="..." />
				`);
				})

				$('#divImage').html(``)
				$.each(response.process, function(key, value) {
					process.push(value)
					$('#divImage').append(`
						<div class="col-12">
							<a class="btn btn-light" style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;width: 100%;min-width: 1px;" data-toggle="collapse" href="#divCollapse_${key+1}" role="button" aria-expanded="false" aria-controls="multiCollapseExample1">零件${key+1}</a>
						</div>
						<div class="col">
							<div class="collapse multi-collapse show" id="divCollapse_${key+1}">
								<div class="card">
									<div class="card-body">
										<div class="form-group row">
											<label class="col-form-label col-auto">零件${key+1}</label>
											<div class="col-auto">
												${crops.html()}
											</div>
											<div class="form-group row">
												<div class="col-12 col-md-12 col-lg-12 col-xl-12">
													<div class="card shadow mb-4">
														<div class="card-group d-flex flex-row flex-nowrap overflow-auto" id="cardTextBox"></div>
													</div>
												</div>  
											</div>
										</div>
										<div class="ow">
											<div class="row">
												<div class="col-8 ">
													<div class="card shadow mb-4">
														<div class="card-header">
															客戶圖面
															<i class="fas fa-exclamation-circle" data-toggle="tooltip" data-placement="top" title="查看研發註記"></i>
														</div>
														<div class="card-body">								
															<div class="row">
																<div class="col-12">
																	<button type="button" class="btn btn-primary" data-type="false" id="showTextBoxAll" onclick="showTextBoxAll(this)">隱藏標記</button>
																	${itemno=="001"?"新圖":"舊圖"}
																</div>
																
															</div>
															<div class="row">
																<div id="divpaint" class="col-12 overflow-auto">
																</div>
																<canvas id="bcPaintCanvas" class="border border-dark rounded" ></canvas>
															</div>
														</div>
													</div>
												</div>
												<div class="col-4">
													<div class="row">
														<div class="card shadow mb-4">
															<div class="card-header">
																製程成本
																<i class="fas fa-exclamation-circle" data-toggle="tooltip" data-placement="top" title="技術部門勾選的最相似圖，底下為技術排定之製程"></i>
															</div>
															<div class="card-body overflow-auto">
																<table class="table table-borderless " width=100%>
																	<thead>
																		<tr>
																			<th>客戶切割圖</th>
																			<th>技術部門的註記</th>
																		</tr>
																	</thead>
																	<tbody id="divImage_${value}">
																	</tbody>
																</table>												
															</div>
														</div>
													</div>
												</div>
												
											</div>
										</div>
										<div class="row">
											<table class="table table-borderless" width=100%>
												<thead>
													<tr>
														<th></th>
														<th></th>
														<th width=30%></th>
														<th width=30% class="text-nowrap"></th>
													</tr>
												</thead>
												<tbody id="newdivImage_${value}">
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>	
						</div>
          	  		`);
					compareObj[value] = [];
				});
				$('[data-toggle="tooltip"]').tooltip();

				processinterval = [];
				setTimeout(function() {
					getcanvasdraw();
				}, 3000);

				$.each(processArr, function(key, value) {
					process_id = processArr[key];
					processinterval[process_id] = setTimeout(process_resultMatch(process_id), 3000)
				})
        $('[name="inputModify_process"][data-type="code"]').change();

				var modifyProcessObj = {
					加工順序: 'num',
					製程代號: 'code',
					製程名稱: 'name'
				};

				function process_resultMatch(process_id) {
					$.ajax({
						url: `/components/Match/${process_id}`,
						type: 'get',
						data: {
							threshold: $('#selectThreshold').val(),
							amount: $('#selectAmount').val(),
							module_name: '生管',
						},
						success: function(response) {
							// console.log($(`#divImage_${focusID}  #divImageResultMatch_${focusItemID} td [name="inputComment"]`))
							if ($(`#divImage_${focusID}  #divImageResultMatch_${focusItemID} td [name="inputComment"]`).length == 1) {
								$(`#divImage_${focusID}  #divImageResultMatch_${focusItemID} td [name="inputComment"]`).focus();
							}
							let selector_not = ``;
							$(response.result).each(function() {
								selector_not += `:not(#divImageResultMatch_${this.id})`;
							});

							$(`#divImage_${response.id} [id*=divImageResultMatch_]${selector_not}`).each(function() {
								$(this).remove();
							})
							$(response.result).each(function(index) {

								responseItem = this;

								// if (responseItem.comment == null)
								// console.log(responseItem)
								// return;
								haveresult = true;
								// console.log(response.id)

								// console.log(compareArr.indexOf(responseItem.id))
								let comment = null;
								// let comment_other = '';
								// if (isJson(this.comment)) {
								// 	$(JSON.parse(this.comment)).each(function() {
								// 		if (this.module_name == '生管') {
								// 			comment = this.comment
								// 		} else if (this.module_name != null && this.comment != null) {
								// 			comment_other += `<p>${this.module_name}：${this.comment}</p>`;
								// 		}
								// 	})
								// } else {
								// 	comment_other = this.comment
								// 	comment_other = ''
								// }
								let crops = $(`
									<tr> 
									</tr> 
								`);
								if (isJson(this.crop_ids)) {
									$(JSON.parse(this.crop_ids)).each(function() {
										if (!crops_arr.hasOwnProperty(this.crop_id)) {
											crops_arr[this.crop_id] = new Object();
										}
										if (this.source != null)
											crops_arr[this.crop_id][this.source] = this.confidence;
									})
									$(JSON.parse(this.crop_ids)).each(function() {
										if ($(crops).find(`[src="/fileCrop/${this.crop_id}"]`).length == 0)
											$(crops).append(`<td><img src="/fileCrop/${this.crop_id}" style="height:100px;width:auto" onclick="getConfidence(${this.crop_id},${this.source})" class="figure-img img-fluid img-thumbnail rounded " alt="..." /></td>`);
									})
								}
								let file_id = id
								let process_obj = $("<div></div>");
								if ($(`#divImage_${response.id}  #divImageResultMatch_${responseItem.id}`).length == 0) {
									var $boolAppend = false;
									var $tmpAppend = `
										<tr name="divImageResultMatch${response.id}" id="divImageResultMatch_${responseItem.id}" data-avg="${Number.parseFloat(responseItem.avg).toFixed(2)}" >
											<td>
												<img src="/file/${responseItem.fileID}" style="height:100px;width:auto" class="figure-img img-fluid img-thumbnail rounded" alt="..." />
											</td>
											<td>
												${file_comment_department || ''}
											</td>
										</tr>
										<tr>
											<td colspan="2" overflow-auto>
												<table style="width:50vw" >
													${crops[0].outerHTML}
												</table>
											</td>
										</tr>
										<tr>
											<td>
												給技術部門的建議：
												<div class="form-inline">
													<input value="${this.comment||''}"  data-type="comment"  type="text"  class="form-control" name="inputComment" data-process_id="${response.id}" data-crop_id="${responseItem.id}" data-confidence="${Number.parseFloat(responseItem.avg).toFixed(2)}"/>
												</div>
											</td>
										</tr>			
									`;

									let $newtmpAppend = `
										<tr id="tr_${response.id}_multiCollapseExample${responseItem.id}">
											<td colspan=3>
												<div class="collapse multi-collapse show" id="divImage_${response.id}_multiCollapseExample${responseItem.id}" >
													<ul class="d-inline-flex list-inline list-unstyled overflow-auto inline-flex" id="collapseDetail_${response.id}" data-component_id="${response.id}" data-process_id="${responseItem.id}">
													</ul>
												</div>
											</td>
											<td>
												給外包的建議：
												<div class="form-inline">
													<input value="${this.outsourcer_comment!=null?this.outsourcer_comment:''}" data-type="outsourcer_comment"  onfocus="inputFocus(${response.id},${responseItem.id})" type="text" class="form-control" name="inputComment" data-process_id="${response.id}" data-crop_id="${responseItem.id}" data-confidence="${Number.parseFloat(responseItem.avg).toFixed(2)}"/>
												</div>
											
												<div class="form-inline">
													<input disabled data-type="outsourcer_cost" placeholder="追加外包成本" onfocus="inputFocus(${response.id},${responseItem.id})" type="text" value="${comment!=null?comment:''}" class="form-control" name="inputComment" data-process_id="${response.id}" data-crop_id="${responseItem.id}" data-confidence="${Number.parseFloat(responseItem.avg).toFixed(2)}"/>
												</div>
												<div class="form-group row">
													<label for="inputdelivery_week" class="col-sm-auto col-form-label">交貨週數</label>
													<input type="number" class="form-control col-sm-auto" value=${delivery_week} data-type="delivery_week" id="inputdelivery_week" >
												</div>
												<button type="button" class="btn btn-primary float-right" onclick="buttonPass()">下一步</button>
												<div class="form-group row col-12">
													<label class="col-form-label col-sm-auto">註記：</label>
													<div class="col" name="divFileComment" >
														<textarea class="form-control" name="inputFileComment" rows="3" ></textarea>
													</div>
												</div>
											</td>
										</tr>`
									// <button class="btn btn-secondary" type="button" name="btnAddProcess" data-component_id="${response.id}"  data-process_id="${responseItem.id}" onclick="inAddProcess(${response.id},${responseItem.id})">新增製程</button>
									// if($(`[name="divImageResultMatch${response.id}"]`).length == 0){
									//   $(`#divImage_${response.id}`).append($tmpAppend);
									//   $boolAppend = true;
									// }else{
									$(`[name="divImageResultMatch${response.id}"]`).each(function() {
										if ($(this).data('avg') <= Number.parseFloat(responseItem.avg).toFixed(2)) {
											$($tmpAppend).insertBefore((this))
											$boolAppend = true;
											return false;
										}
									});
									// }
									if (!$boolAppend) {
										$(`#divImage_${response.id}`).append($tmpAppend);
										$(`#newdivImage_${response.id}`).append($newtmpAppend);
									}
								}
								console.log('index' + index)
								if (index == 0) {
									let tmpIndex = 0;
									$(`#divImage_${response.id} #divImageResultMatch_${responseItem.id} [name="tdDetail"]`).html(``);
									$(response.process.result[index].processes).each(function() {
										console.log('inin')
										$(`#divImage_${response.id} #divImageResultMatch_${responseItem.id} [name="collapseBtn"]`).show();
										let row = this;

										var liDetail = '';
										$.each(row, function(key, value) {
											if (key == "零件名稱")
												return
											liDetail += `
												<div class=" form-group row text-nowrap">
														<label class="col-form-label col-auto  col-md-5" for="">${key}</label>
														<input class="form-control col-md" name="inputModify_process" data-type="${modifyProcessObj[key]}" id="" value="${value}">
												</div>
											`;
											if (tmpIndex > 2) {

											} else {
												$(`#divImage_${response.id} #divImageResultMatch_${responseItem.id} [name="tdDetail"]`).append(`
													<p class="text-nowrap">${key}：${value}</p>
												`);
											}
											tmpIndex++;
										})

										$(`#collapseDetail_${response.id}_${responseItem.id}`).append(`
											<li class="ui-state-default list-inline-item col-auto" >
												${liDetail}
												<div class=" form-group row text-nowrap">
													<label class="col-form-label col-auto col-md-5" for="">廠商</label>
													<input class="form-control col-md" list="datalistOutresourcer" name="inputModify_process" data-type="outsourcer" id="" value="">
											</div>
											<div class=" form-group row text-nowrap">
													<label class="col-form-label col-auto col-md-5" for="">註記</label>
													<input class="form-control col-md" name="inputModify_process" data-type="mark" id="" value="">
											</div>
											<div class=" form-group row text-nowrap">
													<label class="col-form-label col-auto col-md-5" for="">外包成本</label>
													<input class="form-control col-md" name="inputModify_process" data-type="outsourcer_cost"  value="">
											</div>
											<div class=" form-group row text-nowrap" hidden>
													<label class="col-form-label col-auto col-md-5" for="">成本</label>
													<input class="form-control col-md" name="inputModify_process" data-type="cost"  value="">
											</div>
                      <div  name="divoutsourcercost">
                        <p>歷史追加成本</p>

                      </div>
											</li>
										`);
									});
									$('.ui-state-default.list-inline-item.col-auto').each(function(index) {
										$(this).find('input').eq(0).val('00' + (index + 1) + '0');
									})
								}
								// $(`#divImage_${response.id}`).append($(`#divImage_${response.id}  #divImageResultMatch_${responseItem.id}`));
								$(`#newdivImage_${response.id}`).append($(`#tr_${response.id}_multiCollapseExample${responseItem.id}`));
								return false
							})
							if (modifyprocess.length > 0) {
								$.each(modifyprocess, function(key, value) {
									$(`#collapseDetail_${value['component_id']}`).append(`
										<li class="ui-state-default list-inline-item col-auto" >
										<div class=" form-group row text-nowrap">
												${key==0?'<label class="col-form-label col-auto  col-md-5" for="">加工順序</label>':''}
												<input class="form-control col-md" name="inputModify_process" data-type="num" id="" value="${this.num}">
										</div>
										<div class=" form-group row text-nowrap">
												${key==0?'<label class="col-form-label col-auto  col-md-5" for="">製程代號</label>':''}
												<input class="form-control col-md" name="inputModify_process" data-type="code" id="" value="${this.code}">
										</div>
										<div class=" form-group row text-nowrap">
												${key==0?'<label class="col-form-label col-auto  col-md-5" for="">製程名稱</label>':''}
												<input class="form-control col-md" name="inputModify_process" data-type="name" id="" value="${this.name}">
										</div>
										<div class=" form-group row text-nowrap">
												${key==0?'<label class="col-form-label col-auto  col-md-5" for="">廠商</label>':''}
												<input class="form-control col-md" list="datalistOutresourcer" name="inputModify_process" data-type="outsourcer" id="" value="${this.outsourcer||''}">
										</div>
										<div class=" form-group row text-nowrap">
												${key==0?'<label class="col-form-label col-auto  col-md-5" for="">註記</label>':''}
												<input class="form-control col-md" name="inputModify_process" data-type="mark" id="" value="${this.mark}">
										</div>
										<div class=" form-group row text-nowrap">
												${key==0?'<label class="col-form-label col-auto  col-md-5" for="">外包成本</label>':''}
												<input class="form-control col-md" name="inputModify_process" data-type="outsourcer_cost"  value="${this.outsourcer_cost||''}">
										</div>
										<div class=" form-group row text-nowrap" hidden>
												${key==0?'<label class="col-form-label col-auto  col-md-5" for="">成本</label>':''}
												<input class="form-control col-md" name="inputModify_process" data-type="cost"  value="${this.cost}">
										</div>
                    <div  name="divoutsourcercost">
                      <p>歷史追加成本</p>

                    </div>
										
										</li>
									`);
								});
							}
							// $(response.status).each(function() {
							//   if (this.status == "stop") {
							//     clearTimeout(processinterval[response.id]);
							//   }else{
							// processinterval[response.id] = setTimeout(process_resultMatch(response.id), 3000)
							//   }
							// })
							$(`[id*=collapseDetail_${response.id}]`).sortable({
								revert: true,
								stop: function() {
									console.log("<p>用滑鼠拖曳</p>拖曳已停止!");
									$('.ui-state-default.list-inline-item.col-auto').each(function(index) {
										$(this).find('input').eq(0).val('00' + (index + 1) + '0').trigger("input");
									})
								}
							});
							// $('[name="btnAddProcess"]').on('click',function(){
							//   inAddProcess($(this).data('component_id'),$(this).data('process_id'));
							// });
							// $( "#draggable" ).draggable({
							//   connectToSortable: "#sortable",
							//   helper: "clone",
							//   revert: "invalid"
							// });
							// $( "ul, li" ).disableSelection();
							if (!haveresult) {
								haveresult = true;
								var $tmpAppend = `
									<tr id="tr_${response.id}_multiCollapseExample">
										<td colspan=3>
											<div class="collapse multi-collapse show" id="divImage_${response.id}_multiCollapseExample" >
											<ul class="d-inline-flex list-inline list-unstyled overflow-auto inline-flex" id="collapseDetail_${response.id}" data-component_id="${response.id}" data-process_id="0">
											</ul>
											</div>
											</td>
										<td>
											給外包的建議：
											<div class="form-inline">
											</div>
										
											<div class="form-inline">
											</div>
											<div class="form-group row">
											<label for="inputdelivery_week" class="col-sm-auto col-form-label">交貨週數</label>
											<input type="number" class="form-control col-sm-auto" value=${delivery_week} data-type="delivery_week" id="inputdelivery_week" >
											</div>
										</td>
									</tr>
								`;
								// <button class="btn btn-secondary" type="button" name="btnAddProcess" data-component_id="${response.id}"  data-process_id="${responseItem.id}" onclick="inAddProcess(${response.id},${responseItem.id})">新增製程</button>
								// if($(`[name="divImageResultMatch${response.id}"]`).length == 0){
								//   $(`#divImage_${response.id}`).append($tmpAppend);
								//   $boolAppend = true;
								// }else{
								$(`[name="divImageResultMatch${response.id}"]`).each(function() {
									$($tmpAppend).insertBefore((this))
									$boolAppend = true;
									return false;
								});
								$(`#newdivImage_${response.id}`).append($tmpAppend);
								$.each(modifyprocess, function(key, value) {
									$(`#collapseDetail_${value['component_id']}`).append(`
									<tr>
										<td colspan="3">
											<li class="ui-state-default list-inline-item col-auto" style="width:300px">
												<div class=" form-group row text-nowrap">
														<label class="col-form-label col-auto  col-md-5" for="">加工順序</label>
														<input class="form-control col-md" name="inputModify_process" data-type="num" id="" value="${this.num}">
												</div>
												<div class=" form-group row text-nowrap">
														<label class="col-form-label col-auto  col-md-5" for="">製程代號</label>
														<input class="form-control col-md" name="inputModify_process" data-type="code" id="" value="${this.code}">
												</div>
												<div class=" form-group row text-nowrap">
														<label class="col-form-label col-auto  col-md-5" for="">製程名稱</label>
														<input class="form-control col-md" name="inputModify_process" data-type="name" id="" value="${this.name}">
												</div>
												<div class=" form-group row text-nowrap">
														<label class="col-form-label col-auto col-md-5" for="">廠商</label>
														<input class="form-control col-md" name="inputModify_process" data-type="outsourcer" list="datalistOutresourcer"  id="" value="${this.outsourcer||''}">
												</div>
												<div class=" form-group row text-nowrap">
														<label class="col-form-label col-auto col-md-5" for="">註記</label>
														<input class="form-control col-md" name="inputModify_process" data-type="mark" id="" value="${this.mark}">
												</div>
												<div class=" form-group row text-nowrap">
														<label class="col-form-label col-auto col-md-5" for="">外包成本</label>
														<input class="form-control col-md" name="inputModify_process" data-type="outsourcer_cost"  value="${this.outsourcer_cost}">
												</div>
												<div class=" form-group row text-nowrap" hidden>
														<label class="col-form-label col-auto col-md-5" for="">成本</label>
														<input class="form-control col-md" name="inputModify_process" data-type="cost"  value="${this.cost}">
												</div>
                        <div  name="divoutsourcercost">
                          <p>歷史追加成本</p>

                        </div>
											</li>
										</td>
									</tr>

								`);
								});
							}
              $('[name="inputModify_process"][data-type="code"]').change();

							getcanvas();
						}
					})
				}
			}
		})

	}
	let tmpcanvasarr = []
	let tmpmarkarr = []
	let tmpcanvasorg = ''

	function getcanvasdraw() {
		var image = new Image()
		image.onload = function(e) {

			const tmpcanvas = document.getElementById('bcPaintCanvas');
			$('#bcPaintCanvas').css('background-image', `url(/file/${file_id_dest})`);
			$('#bcPaintCanvas').css('background-size', `100% 100%`);
			tmpcanvas.height = $('#divpaint').width() / e.path[0].width * e.path[0].height;
			tmpcanvas.width = $('#divpaint').width();
			console.log($('#divpaint').width())

			$.ajax({
				url: `/file/file_comment/canvas`,
				type: 'get',
				data: {
					file_id: id,
					module_id: 2,

				},
				success: function(response) {

					$(response).each(function() {
						let tmpcanvas = document.getElementById("bcPaintCanvas");
						let ctx = tmpcanvas.getContext("2d");
						let image = new Image();
						image.onload = function() {
							ctx.drawImage(image, 0, 0, tmpcanvas.width, tmpcanvas.height);
						};
						image.src = this.canvas
						tmpcanvasorg = this.canvas

					})
				},
				complete: function(e) {
					$.ajax({
						url: `/file/file_comment/textbox`,
						type: 'get',
						data: {
							file_id: id,
						},
						success: function(response) {
							let tmpXArr = []
							let tmpYArr = []
							let tmpwidthArr = []
							let tmpheightArr = []
							$(response).each(function() {
								tmpcanvasarr.push(this.canvas);
								tmpmarkarr.push(this.mark);
								tmpXArr.push(parseInt(this.x || '0'));
								tmpYArr.push(parseInt(this.y || '0'));
								tmpwidthArr.push(parseInt(this.width || '0'));
								tmpheightArr.push(parseInt(this.height || '0'));
							})
							$(tmpcanvasarr).each(function(i) {
								$('#cardTextBox').append(`
										<div class="card" style="min-width:200px">
											<div class="card-body">
												<button type="button" class="card-title btn btn-link" name="buttonTextBox" onclick="showTextBox(${i})">${i+1}</h5>
												<input type="text" class="form-control" name="inputTextBox" data-id="${i}" data-x="${tmpXArr[i]}" data-y="${tmpYArr[i]}" data-width="${tmpwidthArr[i]}" value="${tmpmarkarr[i]}" disabled />
											</div>
										</div>
									`);
							})
							setTimeout(function(){ 
								showTextBoxAll($('#showTextBoxAll'))
							}, 3000);
						}
					});
				}
			});


		}
		image.src = `/file/${file_id_dest}`
	}

	function showTextBoxAll(tmpbtn) {
		let tmpcanvas = document.getElementById("bcPaintCanvas");
		let ctx = tmpcanvas.getContext("2d");
		if ($(tmpbtn).data('type') == false) {
			let image = new Array();
			image[0] = new Image();
			image[0].onload = function() {
				ctx.clearRect(0, 0, tmpcanvas.width, tmpcanvas.height);
				ctx.drawImage(image[0], 0, 0, tmpcanvas.width, tmpcanvas.height);
			};
			image[0].src = tmpcanvasorg

			$(tmpcanvasarr).each(function(index, value) {
				let element = $(`[name="inputTextBox"][data-id="${index}"]`)
				let tmpX = $(element).data('x')
				let tmpY = $(element).data('y')
				let tmptext = $(element).val();
				let tmpwidth = $(element).data('width');
				let tmpheight = $(element).data('height');
				let ratio = tmpcanvas.width / tmpwidth

				image[index + 1] = new Image();
				image[index + 1].onload = function() {
					// ctx.clearRect(0, 0, tmpcanvas.width, tmpcanvas.height);
					ctx.drawImage(image[index + 1], 0, 0, tmpcanvas.width, tmpcanvas.height);

					ctx.font = "15px Arial";

					var textwidth = ctx.measureText(tmptext).width;
					var textheight = ctx.measureText(tmptext).height;
					ctx.fillStyle = '#f50';
					ctx.fillRect(tmpX * ratio, tmpY * ratio - parseInt("Arial", 15), textwidth, parseInt("Arial", 15));
					ctx.fillStyle = '#000';

					ctx.fillText(tmptext, tmpX * ratio, tmpY * ratio);
				};
				image[index + 1].src = value
			});

			$(tmpbtn).data('type', true)

		} else {
			let image = new Image();
			image.onload = function() {
				ctx.clearRect(0, 0, tmpcanvas.width, tmpcanvas.height);
				ctx.drawImage(image, 0, 0, tmpcanvas.width, tmpcanvas.height);

			};
			image.src = tmpcanvasorg

			$(tmpbtn).data('type', false)
		}
	}

	function showTextBox(i) {
		let tmpcanvas = document.getElementById("bcPaintCanvas");
		let ctx = tmpcanvas.getContext("2d");
		let element = $(`[name="inputTextBox"][data-id="${i}"]`)
		let tmpX = $(element).data('x')
		let tmpY = $(element).data('y')
		let tmptext = $(element).val();
		let tmpwidth = $(element).data('width');
		let tmpheight = $(element).data('height');
		let ratio = tmpcanvas.width / tmpwidth
		let image = new Image();
		image.onload = function() {
			ctx.clearRect(0, 0, tmpcanvas.width, tmpcanvas.height);
			ctx.drawImage(image, 0, 0, tmpcanvas.width, tmpcanvas.height);
			ctx.font = "15px Arial";

			var textwidth = ctx.measureText(tmptext).width;
			var textheight = ctx.measureText(tmptext).height;
			ctx.fillStyle = '#f50';
			ctx.fillRect(tmpX * ratio, tmpY * ratio - parseInt("Arial", 15), textwidth, parseInt("Arial", 15));
			ctx.fillStyle = '#000';

			ctx.fillText(tmptext, tmpX * ratio, tmpY * ratio);
		};
		if (i == null) {
			image.src = tmpcanvasorg
		} else {
			image.src = tmpcanvasarr[i]
		}
	}
	$(document).on('change', '#inputdelivery_week', function() {
		$.ajax({
			url: `/file/delivery_week`,
			type: 'patch',
			data: {
				file_id: id,
				delivery_week: $('#inputdelivery_week').val()
			},
			dataType: 'json',
			success: function(response) {

			}
		});
	});

	// name="inputModify_process" data-type=
	$(document).on('input', '[name="inputModify_process"][data-type="outsourcer_cost"]', function() {
		newInSave($(this).closest('ul'));
		let sum = 0;
		$(this).closest('tr').find('[name="inputModify_process"][data-type="outsourcer_cost"]').each(function() {
			sum += parseInt(this.value) || 0;
		})
		$(this).closest('tr').find('[name=inputComment][data-type=outsourcer_cost]').val(sum)
		$(this).closest('tr').find('[name=inputComment][data-type=outsourcer_cost]').change()
	});
	$(document).on('input', '[name="inputModify_process"]', function() {
		newInSave($(this).closest('ul'));
		// let domModify_process = $(this).closest('li');
		// let tmpArr = new Object();
		// tmpArr['file_id'] = id;
		// tmpArr['component_id'] = $(domModify_process).data('component_id');
		// tmpArr['process_id'] = $(domModify_process).data('process_id');

		// $(domModify_process).find('[name="inputModify_process"]').each(function(){
		//   // console.log($(this).val(),$(this).data('type'))
		//   tmpArr[$(this).data('type')] = $(this).val();
		// });

		// $.ajax({
		//   url:'/modifyprocess/outsourcer',
		//   type:'post',
		//   data:{
		//     tmpArr:tmpArr
		//   },
		//   dataType:'json',
		//   success:function(response){

		//   }
		// })


	})
	let timeoutInSave = null
  let modifyprocesslabelArr=['加工順序','製程代號','製程名稱','廠商','註記','外包成本']

	function newInSave(box) {
    $('ul').find('li.ui-state-default').each(function(index,value){
      $(this).find( "label" ).remove()
    })
    $('ul').find('li.ui-state-default:eq(0)').find('div').each(function(index,value){
      if(index<6){
      $(this).prepend(`<label class="col-form-label col-auto  col-m-5" for="">${modifyprocesslabelArr[index]}</label>`)

      }

    })
		clearTimeout(timeoutInSave)
		timeoutInSave = setTimeout(function() {
			var modifyArr = []
			$(box).find('li').each(function() {
				// console.log(this)
				var tmpObj = new Object();
				tmpli = this;
				$(tmpli).find('[name="inputModify_process"]').each(function() {
					// console.log($(this).data('type'),$(this).val())
					tmpObj[$(this).attr('data-type')] = $(this).val();
					if ($(this).attr('data-type') == 'deadline') {
						if ($(this).val() != null) {
							tmpObj[$(this).attr('data-type')] = $(this).val().replace('T', ' ');
						}

					}

				});



				modifyArr.push(tmpObj)
			});
			$.ajax({
				url: '/modifyprocess/outsourcer',
				type: 'post',
				data: {
					id: id,
					component_id: $(box).data('component_id'),
					process_id: $(box).data('process_id'),
					arr: modifyArr
				},
				dataType: 'json',
				success: function(response) {
					// if(response.status == 'success'){

					// }
				}
			});

		}, 1000);

	}


	function inSave() {
		clearTimeout(timeoutInSave)
		timeoutInSave = setTimeout(function() {
			var modifyArr = []
			$('[name="liprocess"]').each(function() {
				// console.log(this)
				var tmpObj = new Object();
				tmpli = this;
				$(tmpli).find('input[data-input="draggableInput"]').each(function() {
					// console.log($(this).data('type'),$(this).val())
					tmpObj[$(this).attr('data-type')] = $(this).val();
				});
				tmpObj['component_id'] = $(tmpli).closest('[name="draggableUl"]').data('component_id');
				tmpObj['process_id'] = $(tmpli).closest('[name="draggableUl"]').data('process_id');


				modifyArr.push(tmpObj)
			});
			console.log(modifyArr)
			$.ajax({
				url: '/modifyprocess',
				type: 'post',
				data: {
					id: id,
					arr: modifyArr
				},
				dataType: 'json',
				success: function(response) {
					if (response.status == 'success') {

					}
				}
			});
		}, 1000);
	}

	function inDeleteLi(tmpbutton) {
		// console.log($(tmpbutton).closest('li'))
		$(tmpbutton).closest('li').remove();
		$('.ui-state-default.list-inline-item.col-auto').each(function(index) {
			$(this).find('input').eq(0).val('00' + (index + 1) + '0');
		})
	}
	$(document).on('input', '[name="inputCost"]', function() {
		let cost = 0;
		$('[name="inputCost"]').each(function() {
			cost += parseInt($(this).val() || 0);
		})
		$('#inputCost').val(cost)
	})

	function inAddProcess(component_id, process_id) {
		// console.log(process_id,component_id)
		var liDetail = `
    <li class="ui-state-default list-inline-item col-auto">
      <div class=" form-group row text-nowrap">
            <label class="col-form-label col-auto col-md-5" for="">加工順序</label>
            <input class="form-control col-md" id="" value="">
      </div>
      <div class=" form-group row text-nowrap">
            <label class="col-form-label col-auto col-md-5" for="">製程代號</label>
            <input class="form-control col-md" id="" value="">
      </div>
      <div class=" form-group row text-nowrap">
            <label class="col-form-label col-auto col-md-5" for="">製程名稱</label>
            <input class="form-control col-md" id="" value="">
      </div>
      <div class=" form-group row text-nowrap">
            <label class="col-form-label col-auto col-md-5" for="">註記</label>
            <input class="form-control col-md" id="" value="">
      </div>
      <div class=" form-group row text-nowrap">
            <label class="col-form-label col-auto col-md-5" for="">廠商</label>
            <input class="form-control col-md" id="" value="">
      </div>
      <div class=" form-group row text-nowrap">
            <label class="col-form-label col-auto col-md-5" for="">製程成本</label>
            <input class="form-control col-md" name="inputCost" value="">
      </div>
      <div  name="divoutsourcercost">
        <p>歷史追加成本</p>

      </div>
    </li>
    `;
		$(`#collapseDetail_${component_id}_${process_id}`).append(`
        ${liDetail}
    `);
		$('.ui-state-default.list-inline-item.col-auto').each(function(index) {
			$(this).find('input').eq(0).val('00' + (index + 1) + '0');
		})
		// $( '#collapseDetail' ).sortable({
		//             revert: true
		//           });
	}

	function nextpage() {
		$.ajax({
			url: '/file/progress',
			type: 'post',
			data: {
				url: window.location.href,
				id: id
			},
			dataType: 'json',
			success: function(response) {
				$(response).each(function() {
					window.location.href = `${this.url}?id=${file_id}&file_id_dest=${file_id_dest}`
				})
			}
		})
	}

	function sendemail(modules) {
		let content = `
      報價編號${id} ${module_name}部門已完成填寫
      檢視連結如下：{部門連結}`;
		$.ajax({
			url: `/business/dispatch/email`,
			type: 'post',
			data: {
				id: id,
				content: content,
				message: content,
				module: modules,
				// deadline:$('#inputDeadline').val().replace('T',' ')

			},
			dataType: 'json',
			success: function(response) {
				nextpage()
			}
		})

	}


	function buttonPass() {
		let file_id = id;
		$('#basicModal').find('.modal-header').text(``);
		$('#basicModal').find('.modal-body').text(`請稍等...`);
		$('#basicModal').find('.modal-footer').text(``);
		$('#basicModal').modal('show');
		$.ajax({
			url: `/notify/finish/module`,
			type: 'get',
			data: {
				finish: module_id,
				file_id: file_id,


			},
			dataType: 'json',
			success: function(response) {

				var moduleArr = []
				$.each(response, function() {
					moduleArr.push(this.notify)
				})
				// console.log(moduleArr)
				if (moduleArr.length > 0) {
					sendemail(moduleArr)

				} else {
					nextpage()
				}
			}
		})
	}
	$(document).on('change', '[name=inputCheck]', function() {
		let element = this;
		if ($(this).prop('checked')) {
			$.ajax({
				url: `/components/comment`,
				type: 'post',
				data: {
					process_id: $(element).attr('data-process_id'),
					crop_id: $(element).attr('data-crop_id'),
					confidence: $(element).attr('data-confidence'),
					comment: '',
					outsourcer_comment: $(element).closest('tr').find('[name=inputComment][data-type=outsourcer_comment]').val(),
					outsourcer_cost: $(element).closest('tr').find('[name=inputComment][data-type=outsourcer_cost]').val(),
					module_name: '生管',
					material: '',
					stuff: '',
					process: ''

				},
			})
		} else {
			$.ajax({
				url: `/components/comment`,
				type: 'delete',
				data: {
					process_id: $(element).attr('data-process_id'),
					crop_id: $(element).attr('data-crop_id'),
					module_name: '生管'
				},
			})
		}
	})
	$(document).on('input change', '[name=inputComment]', function() {
		let element = this;
		// if($(element).closest('tr').find('[name=inputCheck]').prop('checked')){
		$.ajax({
			url: `/components/comment`,
			type: 'post',
			data: {
				process_id: $(element).attr('data-process_id'),
				crop_id: $(element).attr('data-crop_id'),
				confidence: $(element).attr('data-confidence'),
				comment: $(element).closest('table').find('[name=inputComment][data-type=comment]').val(),
				outsourcer_comment: $(element).closest('table').find('[name=inputComment][data-type=outsourcer_comment]').val(),
				outsourcer_cost: $(element).closest('table').find('[name=inputComment][data-type=outsourcer_cost]').val(),
				module_name: '生管',
				material: '',
				stuff: '',
				process: ''
			},
		})
		// }
	})

	function getComponents() {
		$.ajax({
			url: `/business/components`,
			type: 'get',
			dataType: 'json',
			success: function(response) {}
		});
	}

	function getListState(file_id) {
		$.ajax({
			url: `/file/state/${file_id}`,
			type: 'get',
			data: {
				module_name: '生管'
			},
			dataType: 'json',
			success: function(response) {
				$(response.file_information).each(function() {
					$('#spanUploadTime').text(this.upload_time)
					$('#spanFileId').text(this.order_name)
					$('#imgThumbnail').attr('src', `/file/${this.id}`)
					$('#tdThumbnailDest').html(`
						<img src="/file/${file_id_dest}" class="img-thumbnail" />
					`)
				})

				$('#list-tab-business').html(``);
				let list_tab = $(`<ul class="list-group list-group-horizontal w-100"></ul>`);
				let list_color = null;
				$(response.state).each(function(index) {
					if (index == 0 || list_color != this.module_color) {
						list_color = this.module_color;
						if (index != 0)
							if (this.module_name == '研發')
								$('#list-tab-business').append($(list_tab)[0].outerHTML);
							else
								$('#list-tab-other').append($(list_tab)[0].outerHTML);
						list_tab = $(`
							<div class="alert alert${this.module_color} form-group d-inline-flex col-12" role="alert">
								<span class="col-auto">${this.module_name}</span>
								<div class="list-group list-group-horizontal col" role="tablist">
								<ul class="list-group list-group-horizontal">
								</ul>
								</div>
							</div>
						`);
					}
					if (this.progress.indexOf('完成報價') != -1) {
						console.log(100)
						$('#list-tab-end').append(`
							<div class="alert alert${this.module_color} form-group d-inline-flex col-12" role="alert"">
								<span class="col-auto">${this.module_name}</span>
								<div class="list-group list-group-horizontal col" role="tablist">
								<ul class="list-group list-group-horizontal">
									<li class="list-group-item list-group-item${this['update_time']!=null?this.module_color:''} flex-fill text-nowrap ${location.href.indexOf(this.url+'?')!=-1?'active':''}"  ${this.redirect?``:`onclick="javascript:location.href='${this['url']}?id=${file_id}&file_id_dest=${file_id_dest}'"`}>${this['progress']}</li>
								</ul>
								</div>
							</div>
						`);
					} else {
						$(list_tab).find('ul').append(`
							<li class="list-group-item list-group-item${this['update_time']!=null?this.module_color:''} flex-fill text-nowrap ${location.href.indexOf(this.url+'?')!=-1?'active':''}"  ${this.redirect?``:`onclick="javascript:location.href='${this['url']}?id=${file_id}&file_id_dest=${file_id_dest}'"`}>${this['progress']}</li>
						`);
					}
					// if(index==response.state.length-1){
					//   $('#list-tab-other').append($(list_tab)[0].outerHTML);
					// }
				})
				// $('#divStation').empty();
				// $(response.station).each(function(){
				//   let row = this;
				//   let tr = $(`<tbody></tbody>`);
				//   $(this.station).each(function(index){
				//     let information = $(`<div></div>`);
				//     $.each(this,function(key,value){
				//       if(key!="crop_id" && value != null)
				//         $(information).append(`
				//           <p>${key}：${value}</p>
				//         `);
				//     })
				//     $(tr).append(`
				//       <tr>
				//         <td width=50%><img src="/fileCrop/${this.crop_id}" class="figure-img img-fluid img-thumbnail rounded" alt="..."></td>
				//         <td width=50%>${index%2==0?'前沖棒':'後沖棒'}</td>
				//         <td class="text-nowrap">${information.html()}</td>
				//       </tr>
				//     `)
				//   })
				//   $('#divStation').append(`
				//     <div class="card shadow mb-4 form-group">
				//       <div class="card-header">
				//         ${this.name}
				//       </div>
				//       <div class="card-body">
				//         <div class="row">
				//           <label class="col-form-label col-1">相似度結果</label>
				//           <div class="col table-responsive">
				//             <table class="table table-borderlress">
				//               <tbody>
				//               ${tr.html()}
				//               </tbody>
				//             </table>
				//           </div>
				//         </div>
				//       </div>
				//     </div>
				//   `);
				// })
			}
		})
		// $.ajax({
		//   url: `/file/state/${file_id}`,
		//   type: 'get',
		//   dataType: 'json',
		//   success: function(response) {
		//     $(response.file_information).each(function() {
		//       $('#spanUploadTime').text(this.upload_time)
		//       $('#spanFileId').text(this.order_name)
		//       $('#imgThumbnail').attr('src',`/file/${this.id}`)
		//       $('#tdThumbnailDest').html(`
		//           <img src="/file/${file_id_dest}" class="img-thumbnail" />
		//       `)
		//     })

		//     $('#list-tab-business').html(``);
		//     let list_tab = $(`<ul class="list-group list-group-horizontal w-100"></ul>`);
		//     let list_color = null;
		//     $(response.state).each(function(index) {
		//       if(index==0 || list_color != this.module_color){
		//         list_color = this.module_color;
		//         if(index!=0)
		//           if(this.module_name=='生管')
		//             $('#list-tab-business').append($(list_tab)[0].outerHTML);
		//           else
		//             $('#list-tab-other').append($(list_tab)[0].outerHTML);
		//         list_tab = $(`
		//           <div class="alert alert${this.module_color} form-group d-inline-flex col-12" role="alert">
		//             <span class="col-auto">${this.module_name}</span>
		//             <div class="list-group list-group-horizontal col" role="tablist">
		//               <ul class="list-group list-group-horizontal">
		//               </ul>
		//             </div>
		//           </div>
		//         `);
		//       }
		//       if(this.progress.indexOf('完成報價')!=-1){
		//         console.log(100)
		//         $('#list-tab-end').append(`
		//           <div class="alert alert${this.module_color} form-group d-inline-flex col-12" role="alert"">
		//             <span class="col-auto">${this.module_name}</span>
		//             <div class="list-group list-group-horizontal col" role="tablist">
		//               <ul class="list-group list-group-horizontal">
		//                 <li class="list-group-item list-group-item${this['update_time']!=null?this.module_color:''} flex-fill text-nowrap ${location.href.indexOf(this.url+'?')!=-1?'active':''}" onclick="javascript:location.href='${this['url']}?id=${file_id}&file_id_dest=${file_id_dest}'">${this['progress']}</li>
		//               </ul>
		//             </div>
		//           </div>
		//         `);
		//       }else{
		//         $(list_tab).find('ul').append(`
		//           <li class="list-group-item list-group-item${this['update_time']!=null?this.module_color:''} flex-fill text-nowrap ${location.href.indexOf(this.url+'?')!=-1?'active':''}" onclick="javascript:location.href='${this['url']}?id=${file_id}&file_id_dest=${file_id_dest}'">${this['progress']}</li>
		//         `);
		//       }
		//       // if(index==response.state.length-1){
		//       //   $('#list-tab-other').append($(list_tab)[0].outerHTML);
		//       // }
		//     })
		//   }
		// })
	}

	function getResult(file_id) {
		let process_id = null;
		$.each(response['process'], function(key, value) {
			if (key == 'process_id') {
				process_id = value;
			}
		});
		if (process.length != 0) {
			$(process).each(function(index) {
				clearInterval(process[index])
			})
			$('#divImage').empty();
		}
		console.log(process)
		process[process_id] = setInterval(() => {
			// console.log(process_id)

			$.ajax({
				url: `/components/Match/${process_id}`,
				type: 'get',
				success: function(response) {
					$(response).each(function() {
						if ($(`#divImageResultMatch_${this.id}`).length == 0) {
							$('#divImage').append(`
								<div class="form-group row col-sm-4" id="divImageResultMatch_${this.id}">
									<div class="col-12">
									<img src="/file/${this.id}" class="figure-img img-fluid img-thumbnail rounded" alt="..." />
									</div>
									<label class="col-form-label col-12">${responseItem.avg}</label>
								</div>
							`);
						}
					})
					//   $('#tableCrops').find('tbody').empty();
					//   let components_list = $(`<select></select>`);
					//   $(response['crops']).each(function(index){
					//     $(components_list).append(`
					//       <option value="${(index+1)}" ${(index==0?"selected":'')}>${(index+1)}</option>
					//     `);
					//   })
					//   $(response['crops']).each(function(index){
					//     $('#tableCrops').find('tbody').append(`
					//       <tr>
					//         <td><img src="/fileCrop/${this.id}" class="img-fluid img-thumbnail"/></td>
					//         <td><select class="form-control" name="selectCrops" data-id="${this.id}">${components_list.html()}</td>
					//       </tr>
					//     `);
					// });
				}
			})
		}, 2000)
	}
</script>