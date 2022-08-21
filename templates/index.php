<?php include(__DIR__ . '/basic/header.html'); ?>
<script src="/dropzone/dist/dropzone.js"></script>
<link rel="stylesheet" href="/dropzone/dist/dropzone.css">
<div class="row">
  <!-- search -->
  <div class="col-12">
    <div class="card shadow mb-4" id="quickNews" style="display:none">
      <h3 class="card-title position-relative mb-5">
        <span class="badge rounded position-absolute translate-middle rfid_title p-3 text-center">速報區</span>
      </h3>
      <div class="card-body">
        <div class="row">
          <div class="col-sm-6 col-md-4 d-flex align-items-start flex-column border_right" id="daily_order">
            <h5 class="card-title index_second_title">目前本系統今日處理進度</h5>
          </div>
          <div class="col-sm-6 col-md-4 border_right">
            <h5 class="card-title index_second_title">智能估價狀態分布圖</h5>
            <div class="chart-pie pt-2">
              <canvas id="PieChart"></canvas>
            </div>
          </div>
          <div class="col-sm-6 col-md-4">
            <h5 class="card-title index_second_title">本公司報價數量趨勢圖</h5>
            <div class="chart-pie pt-2">
              <canvas id="AreaChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="card shadow mb-4" id="mainFunction" style="display:none">
      <h3 class="card-title position-relative mb-5">
        <span class="badge rounded position-absolute translate-middle rfid_title p-3 text-center">主功能 <span class="index_small_title_border">智能估價流程引擎</span><span class="index_small_title">各階段待處理件數</span></span>
      </h3>
      <div class="card-body">
        <div class="row align-items-center justify-content-center">
          <div class="col-2 d-flex flex-column">
            <button type="button" class="btn btn-primary m-2 font-weight-bold" id="uniteQuotation" onclick="javascript:location.href='/homeOrder'">建立統一報價</button>
            <button type="button" class="btn btn-secondary m-2 font-weight-bold" id="singleQuotation"onclick="javascript:location.href='/home'">建立單一報價</button>
            <button type="button" class="btn btn-secondary m-2 font-weight-bold" id="btnAuthority" style="display:none" data-toggle="modal" data-target="#exampleModal" data-type="controlUrl">權限管理</button>
          </div>
          <div class="col-10 collapse_box">


            <div class="d-flex overflow-auto section_collapse" id="root">
              <!-- <div class="card h-100"> -->
              <!-- <div class="card-body d-flex overflow-auto" id="root"> -->
              <!-- <div class="flex-nowrap d-flex justify-content-center">
                  <div class="collapse_box"> 
                    <div class="section_collapse" id="root">
                    </div>
                  </div>
                </div> -->
              <!-- </div> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card shadow mb-4 h-100" id="quatationSearch" style="display:none">
          <h3 class="card-title position-relative mb-5">
            <span class="badge rounded position-absolute translate-middle rfid_title p-3 text-center">報價查詢<span class="index_small_title2">上傳</span></span>
          </h3>
          <div class="card-body">
            <div class="form-group row">
              <!-- <select class="form-control" id="selectFinish">
                  <option value="false">今日報價</option>
                  <option value="true">歷史報價</option>
                </select> -->
              <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-outline-primary active">
                  <input type="radio" name="selectFinish" value="false" id="" autocomplete="off" checked> 今日報價
                </label>
                <label class="btn btn-outline-primary">
                  <input type="radio" name="selectFinish" value="true" id="" autocomplete="off"> 歷史報價
                </label>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-form-label col-sm-auto text-dark font-weight-bold" for="input_order_name">搜尋</label>
              <div class="col-sm">
                <input class="form-control" id="input_order_name" />
              </div>
              <div class="col-sm-auto">
              </div>
            </div>
            <div class="form-group row text-dark font-weight-bold">
              <div class="col-md-12 mb-3" hidden>
                <label class="form-label" for="input_order_id">編號：</label>
                <input class="form-control" id="input_order_id" />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="filterDate_start">起：</label>
                <input type="date" name="filterDate" data-type="start" class="form-control" id="filterDate_start" />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="filterDate_end">迄：</label>
                <input type="date" name="filterDate" data-type="end" class="form-control" id="filterDate_end" />
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless" id="dataTable" width=100%>
                <thead>
                  <tr>
                    <th>流水單號</th>
                    <th>報價日期</th>
                    <th>客戶圖號</th>
                    <th>處理狀態</th>
                    <th>客戶名稱</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 col-xl-5 mb-4 d-none">
        <div class="row row-cols-1 h-100">
          <div class="col h-auto d-none" id="quatationSure" style="display:none">
            <!-- <div class="card shadow mb-4 h-100">
              <div class="card-header">
                報價確認
              </div>
              <div class="card-body overflow-auto">
                <div class="form-group row ">
                    <label class="col-form-label col-12">2021-06-25</label>
                    <label class="col-form-label col-auto">報價金額：5000 數量：20 折扣：200</label>
                    <label class="col-form-label col-auto">註記：再增加數量，給予優惠</label>
                    <label class="col-form-label col-12">2021-06-23</label>
                    <label class="col-form-label col-auto">報價金額：4200 數量：15 折扣：100</label>
                    <label class="col-form-label col-auto">註記：增加數量</label>
                    <label class="col-form-label col-12">2021-06-21</label>
                    <label class="col-form-label col-auto">報價金額：3300 數量：10 折扣：0</label>
                    <label class="col-form-label col-auto">註記：第一次報價</label>
                </div>
                <form id="formQuotation">
                  <div class="row ">
                    <div class="form-group row col-auto">
                      <label class="col-form-label col-auto">報價金額：</label>
                      <div class="col-auto">
                        <input class="form-control form-control-user" required="" data-type="quotation" name="inputQuotation" placeholder="ex.123456" type="number" autocomplete="off">
                      </div>
                    </div>
                    <div class="form-group row col-auto">
                      <label class="col-form-label col-auto">報價數量：</label>
                      <div class="col-auto">
                        <input class="form-control form-control-user"  data-type="discription" name="inputQuotation" placeholder="ex.10" type="text" autocomplete="off">
                      </div>
                    </div>
                    <div class="form-group row col-auto">
                      <label class="col-form-label col-auto">報價折扣：</label>
                      <div class="col-auto">
                        <input class="form-control form-control-user"  data-type="discription" name="inputQuotation" placeholder="ex.90" type="text" autocomplete="off">
                      </div>
                      <label class="col-form-label col-auto">%</label>
                    </div>
                    <div class="form-group row col-auto">
                      <label class="col-form-label col-auto">報價註記：</label>
                      <div class="col-auto">
                        <input class="form-control form-control-user"  data-type="discription" name="inputQuotation" placeholder="ex.此報價為初次報價" type="text" autocomplete="off">
                      </div>
                    </div>
                    <div class="form-group row col-auto">
                      <button type="submit" class="btn btn-primary" id="btnQuotation">確認</button>
                    </div>
                  </div>
                </form>
              </div>
            </div> -->
          </div>
          <div class="col-12 h-auto">
            <div class="card shadow mb-4 h-100" id="appraisalSummary" style="display:none">
              <h3 class="card-title position-relative mb-5">
                <span class="badge rounded position-absolute translate-middle rfid_title p-3 text-center">估價摘要</span>
              </h3>
              <div class="card-body h-100 t ext-dark font-weight-bold text-dark font-weight-bold">
                分為以下兩種計算方式
                <ul style="list-style: square;">
                  <li><span class="text-secondary text-dark font-weight-bold">依各部門建議之相似零件均價之價格</span></li>
                  <li><span class="text-secondary text-dark font-weight-bold">以智能辨識最相似之價格</span></li>
                </ul>
                <div class="col">
                  <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                      <a class="nav-link active" id="pills-develop-tab" data-toggle="pill" href="#pills-develop" role="tab" aria-controls="pills-develop" aria-selected="true">各部門建議</a>
                    </li>
                    <li class="nav-item" role="presentation">
                      <a class="nav-link" id="pills-deeplearning-tab" data-toggle="pill" href="#pills-deeplearning" role="tab" aria-controls="pills-deeplearning" aria-selected="false">智能估價建議</a>
                    </li>
                  </ul>
                </div>
                <div class="tab-content" id="pills-tabContent">
                  <div class="tab-pane fade show active " id="pills-develop" role="tabpanel" aria-labelledby="pills-develop-tab">
                    <div class="col-8">
                      <label class="col-form-label col-auto">零件相似度門檻：</label>
                      <div class="col-auto">
                        <select class="form-control" id="selectThreeshold_develop" name="selectThreeshold">
                          <option value="0">0%</option>
                          <option value="10">10%</option>
                          <option value="20">20%</option>
                          <option value="30">30%</option>
                          <option value="40">40%</option>
                          <option value="50">50%</option>
                          <option value="60">60%</option>
                          <option value="70">70%</option>
                          <option value="80">80%</option>
                          <option value="90">90%</option>
                          <option value="100">100%</option>
                        </select>
                      </div>
                      <label class="col-form-label col-auto">各部門建議數量：</label>
                      <div class="col-auto mb-3">
                        <select class="form-control" id="selectLimit_develop" name="selectLimit">
                          <option value="1">1</option>
                          <option value="2">2</option>
                          <option value="3">3</option>
                          <option value="4">4</option>
                          <option value="5">5</option>
                          <option value="6">6</option>
                          <option value="7">7</option>
                          <option value="8">8</option>
                          <option value="9">9</option>
                          <option value="10">10</option>
                          <option value="11">11</option>
                          <option value="12">12</option>
                          <option value="13">13</option>
                          <option value="14">14</option>
                          <option value="15">15</option>
                          <option value="16">16</option>
                          <option value="17">17</option>
                          <option value="18">18</option>
                          <option value="19">19</option>
                          <option value="20">20</option>
                        </select>
                      </div>
                      <div class="col">
                        <label>篩選結果：</label>
                        <label id="labelCount_develop">2</label>
                        <label>/</label>
                        <label id="labelTotal_develop">30</label>
                        <label>張</label>
                      </div>
                    </div>
                    <div class="col-12 overflow-auto">
                      <table class="table" width=100% id="dataTable_develop">
                        <thead>
                          <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">智能建議金額</th>
                            <th class="text-center">研發用料成本</th>
                            <th class="text-center">技術製程成本</th>
                            <th class="text-center">生管外包成本</th>
                            <th><button class="btn btn-primary" onclick="itemAdd(this)">新增項目</button></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr onclick="getDetail('process_mapping',0)">
                            <td>1</td>
                            <td class="text-center">69.80</td>
                            <td><span class="col-auto">原始成本 1000</span> + <span class="col-auto text-nowrap">追加成本 500</span></td>
                            <td><span class="col-auto">原始成本 1000</span> + <span class="col-auto text-nowrap">追加成本 500</span></td>
                            <td><span class="col-auto">原始成本 1200</span> + <span class="col-auto text-nowrap">追加成本 1400</span></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="tab-pane fade" id="pills-deeplearning" role="tabpanel" aria-labelledby="pills-deeplearning-tab">
                    <div class="col-8">
                      <label class="col-form-label col-auto">零件相似度門檻：</label>
                      <div class="col-auto">
                        <select class="form-control col-auto" id="selectThreeshold_develop" name="selectThreeshold">
                          <option value="0">0%</option>
                          <option value="10">10%</option>
                          <option value="20">20%</option>
                          <option value="30">30%</option>
                          <option value="40">40%</option>
                          <option value="50">50%</option>
                          <option value="60">60%</option>
                          <option value="70">70%</option>
                          <option value="80">80%</option>
                          <option value="90">90%</option>
                          <option value="100">100%</option>
                        </select>
                      </div>
                      <label class="col-form-label col-auto">智能辨識建議數量：</label>
                      <div class="col-auto">
                        <select class="form-control col-auto" id="selectLimit_develop" name="selectLimit">
                          <option value="1">1</option>
                          <option value="2">2</option>
                          <option value="3">3</option>
                          <option value="4">4</option>
                          <option value="5">5</option>
                          <option value="6">6</option>
                          <option value="7">7</option>
                          <option value="8">8</option>
                          <option value="9">9</option>
                          <option value="10">10</option>
                          <option value="11">11</option>
                          <option value="12">12</option>
                          <option value="13">13</option>
                          <option value="14">14</option>
                          <option value="15">15</option>
                          <option value="16">16</option>
                          <option value="17">17</option>
                          <option value="18">18</option>
                          <option value="19">19</option>
                          <option value="20">20</option>
                        </select>
                      </div>
                      <label class="col-form-label col-auto">篩選結果：</label>
                      <label class="col-form-label col-auto" id="labelCount_develop">2</label>
                      <label class="col-form-label col-auto">/</label>
                      <label class="col-form-label col-auto" id="labelTotal_develop">30</label>
                      <label class="col-form-label col-auto">張</label>
                    </div>
                    <div class="col-12">
                      <table class="table" width=100% id="dataTable_deeplearning">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>智能建議金額</th>
                            <th>研發用料成本</th>
                            <th>技術製程成本</th>
                            <th>生管外包成本</th>
                            <th><button class="btn btn-primary">新增項目</button></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <th>1</th>
                            <td>零件A</td>
                            <td>300</td>
                          </tr>
                          <tr>
                            <th>2</th>
                            <td>零件B</td>
                            <td>300</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
    </div>
    <?php include(__DIR__ . '/basic/footer.html'); ?>
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

      var url = new URL(window.location.href);
      var urlmodule_id = url.searchParams.get("module_id");
      $(document).on('click', '#dataTable tbody tr', function() {
        if ($(this).hasClass('table-active')) {
          $(this).removeClass('table-active');
        } else {
          $('#dataTable tbody tr').removeClass('table-active');
          $(this).addClass('table-active');
        }
      });
      $(function() {
        $.ajax({
          url: `/system/user`,
          type: 'get',
          success: function(response) {
            $.each(response, function() {
              if (urlmodule_id == null)
                urlmodule_id = this.module_id
              return false;
            })
          },
          complete() {
            init();
          }
        })
        //隱藏建立報價按鈕
        if(urlmodule_id == 2 || urlmodule_id == 3 || urlmodule_id == 4 || urlmodule_id == 5){
          console.log(urlmodule_id)
          $("#uniteQuotation").attr("style", "display: none");
          $("#singleQuotation").attr("style", "display: none");
        }
      })

      function init() {
        $('#root').attr('module_id', urlmodule_id);
        import("/static/js/listTab.js");
        let setting_business = JSON.parse(JSON.stringify(setting));
        $('#dataTable').DataTable(setting_business);
        // getappraisalSummary();
        getOverview();
        getOrderTable();
        getQuatationSure();

        $('#exampleModal').on('show.bs.modal', function(e) {
          var type = $(e.relatedTarget).data('type');
          if (type == 'controlUrl') {
            controlUrl();
          }
        });
      }

      function inLoad(id) {
        getQuatationSure(id);

        getappraisalSummary(id)
      }

      function getappraisalSummary(id) {
        window.sharedVariable = {
          file_id: id,
          file_id_dest: id,
          type: 'home',

        };
        $("#appraisalSummary").load(`/discript/appraisalSummary`);
      }



      function getQuatationSure(id) {
        window.sharedVariable = {
          file_id: id,
          file_id_dest: id,
          type: 'home',
          // module_name:'業務'
        };
        // $("#quatationSure").load(`/quotation/check`);
      }

      function getDailyOrder(data) {
        $(data).each(function() {
          $('#daily_order').append(`
        <h5 class="card-title my-auto index_list text-black">${this.name}：${this.count || 0}</h5>
      `);
        })
      }

      function controlUrl() {
        $('#exampleModal .modal-title').html('權限管理')
        $('#exampleModal .modal-body').html(`
    <div class="form-group">
      <label for="moduleSelect">選擇部門</label>
      <select  class="form-control" id="moduleSelect">
        <option value="" disabled selected>請選擇</option>
      </select>
      
    </div>
    <div id="divFunction">
      <label >主頁功能</label></p>
    </div>
    <div id="divProgress">
      <label>階段</label></p>
    </div>
    

    `)
        getAllCard();
        getAllUrl();
        $.ajax({
          url: `/setting/module`,
          type: 'get',
          data: {},
          dataType: 'json',
          success: function(response) {
            $.each(response, function() {
              $('#moduleSelect').append(`<option value="${this.id}">${this.name}</option>`)
            })

            $(document).on('change', '#moduleSelect', function() {
              inModuleAuthority($(this).val())
            })
          }
        });


      }

      function getAllCard() {
        $.ajax({
          url: `/setting/card`,
          type: 'get',
          data: {},
          dataType: 'json',
          success: function(response) {

            $('#divFunction').append(`
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" disabled id="inlineCheckboxAllCard" value="">
            <label class="form-check-label" for="inlineCheckboxAllCard">全選</label>
          </div>`)
            $.each(response, function() {
              $('#divFunction').append(`
          <div class="form-check form-check-inline">
            <input class="form-check-input" disabled type="checkbox" data-type="${this.name}" name="checkboxCard" id="checkboxCard${this.id}" value="${this.id}">
            <label class="form-check-label" for="checkboxCard${this.id}">${this.chinese_name}</label>
          </div>`)
            })

          }
        });
      }

      function getAllUrl() {
        $.ajax({
          url: `/setting/url`,
          type: 'get',
          data: {},
          dataType: 'json',
          success: function(response) {
            $('#divProgress').append(`
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" disabled id="inlineCheckboxAll" value="">
            <label class="form-check-label" for="inlineCheckboxAll">全選</label>
          </div>`)
            $.each(response, function() {
              $('#divProgress').append(`
          <div class="form-check form-check-inline">
            <input class="form-check-input" disabled type="checkbox" name="checkedboxUrl" id="inlineCheckbox${this.id}" value="${this.id}">
            <label class="form-check-label" for="inlineCheckbox${this.id}">${this.name}</label>
          </div>`)
            })


          }
        });
      }




      function inModuleAuthority(module_id) {
        $('[name="checkedboxUrl"],#inlineCheckboxAll,[name="checkboxCard"],#inlineCheckboxAllCard').attr('disabled', false)
        $('[name="checkedboxUrl"],#inlineCheckboxAll,[name="checkboxCard"],#inlineCheckboxAllCard').prop('checked', false);
        getModuleAuthority(module_id)
        getCardAuthority(module_id)

        $('[name="checkedboxUrl"]').unbind().on('click', function() {
          updateModuleAuthority(module_id)

        })

        $('#inlineCheckboxAll').unbind().on('change', function() {
          if ($('#inlineCheckboxAll:checked').length > 0) {
            $('[name="checkedboxUrl"]').prop('checked', true);
            updateModuleAuthority(module_id)

          } else {
            $('[name="checkedboxUrl"]').prop('checked', false);
            updateModuleAuthority(module_id)

          }
        })


        $('[name="checkboxCard"]').unbind().on('click', function() {
          if ($('[name="checkboxCard"][data-type="quatationSearch"]').prop('checked') == false) {
            $('[name="checkboxCard"][data-type="quatationSure"]').prop('checked', false);
            $('[name="checkboxCard"][data-type="appraisalSummary"]').prop('checked', false);
          }
          updateCardAuthority(module_id)

        })

        $('#inlineCheckboxAllCard').unbind().on('change', function() {
          if ($('#inlineCheckboxAllCard:checked').length > 0) {
            $('[name="checkboxCard"]').prop('checked', true);
            updateCardAuthority(module_id)

          } else {
            $('[name="checkboxCard"]').prop('checked', false);
            updateCardAuthority(module_id)

          }
        })



      }

      function updateModuleAuthority(module_id) {
        let urlArr = [];

        $('[name="checkedboxUrl"]:checked').each(function() {
          urlArr.push($(this).val())
        });
        $.ajax({
          url: `/url/authority`,
          type: 'patch',
          data: {
            module_id: module_id,
            urlArr: urlArr
          },
          success: function(response) {

          }
        });
      }

      function getModuleAuthority(module_id) {
        $.ajax({
          url: `/url/authority`,
          type: 'get',
          data: {
            module_id: module_id,
          },
          success: function(response) {
            $.each(response, function() {
              $(`#inlineCheckbox${this.progress_id}`).prop('checked', true);
            });

          }
        });
      }

      function updateCardAuthority(module_id) {
        let urlArr = [];

        $('[name="checkboxCard"]:checked').each(function() {
          urlArr.push($(this).val())
        });
        $.ajax({
          url: `/card/authority`,
          type: 'patch',
          data: {
            module_id: module_id,
            urlArr: urlArr
          },
          success: function(response) {

          }
        });

      }

      function getCardAuthority(module_id) {
        $.ajax({
          url: `/card/authority`,
          type: 'get',
          data: {
            module_id: module_id,
          },
          success: function(response) {
            $.each(response, function() {
              $(`#checkboxCard${this.card_id}`).prop('checked', true);
            });

          }
        });
      }

      function getOverview() {
        if (urlmodule_id == null) {
          $('#btnAuthority').show()
          urlmodule_id = 0
        }
        $.ajax({
          url: `/overview`,
          type: 'get',
          data: {
            module_id: urlmodule_id
          },
          success: function(response) {
            showCard(response.card_authority)
            getAreaChart(response.dow_order);
            getPieChart(response.history_order);
            getDailyOrder(response.daily_order);
            // getDetail(response.all_order);
            test();
          }
        })

      }

      let timeout = null;
      let finishorder = false;
      $(document).on('input', '#input_order_name,#input_order_id,[name="filterDate"]', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
          getOrderTable();
        }, 1000)
      })
      $(document).on('change', '[name="selectFinish"]', function() {
        clearTimeout(timeout);
        finishorder = $('[name="selectFinish"]:checked').val()
        console.log(finishorder)
        timeout = setTimeout(function() {
          getOrderTable();
        }, 1000)
      })





      function getOrderTable() {
        let setting_business = JSON.parse(JSON.stringify(setting));
        $('#dataTable').DataTable(setting_business).destroy();
        if (urlmodule_id == null) {
          urlmodule_id = 0
        }
        setting_business['ajax'] = {
          url: `/files`,
          type: 'get',
          "data": function(d) {
            d.id = $('#input_order_id').val(),
              d.order_name = $('#input_order_name').val(),
              d.module_id = urlmodule_id,
              d.starttime = $('[name="filterDate"][data-type="start"]').val().replace("T", " "),
              d.endtime = $('[name="filterDate"][data-type="end"]').val().replace("T", " "),
              d.finish = finishorder
          }
        };
        setting_business['processing'] = true;
        setting_business['serverSide'] = true;
        setting_business['createdRow'] = function(row, data, dataIndex) {
          $(row).attr('onclick', `inLoad(${data['id']})`);
          $(row).attr('style', `cursor:pointer`);
        };

        setting_business['columns'] = [{
            "data": 'tmpid'
          },
          {
            "data": "upload_time"
          },
          {
            "data": "order_name"
          }, {
            "data": null,
            render: function(data, type, row, meta) {
              return `<a type="button" class="btn btn-primary btn-sm" href="${row['id']===null?`/file/by_fk?fk=${encodeURIComponent(row['fk'])}`:`${row['url']}?id=${row['id']}&file_id_dest=${row['file_id_destination']}`}">${row['progress']}</a>${$('[name="selectFinish"]:checked').val()=='true'&&row['update_time']!=null?`<p>${row['update_time']}</p>`:''}`;
            }
          }, {
            "data": "customer_code",
          },
        ];
        $('#dataTable').DataTable(setting_business);
      }


      // collapse_API
      // function getDetail(data) {
      //   $('#list-tab').html(``);
      //   let list_color = null;
      //   $(data).each(function(index) {
      //     if (index == 0 || list_color != this.module_color) {
      //       list_color = this.module_color;
      //       if (index != 0)
      //         $('#list-tab').append($(list_tab)[0].outerHTML);
      //       list_tab = $(`
      //             <div class="section_item d-flex align-items-center card${this.module_id%5} text-black justify-content-center overflow-auto">
      //                     <div class="d-flex">
      //                       <h1 class="collapse_title mx-0 text-center">${this.module_name}</h1>
      //                     </div>
      //                     <div class="content_list d-none ml-3">
      //                       <ul class="d-inline-block py-1">
      //                       </ul>
      //                     </div>
      //                   </div>
      //   `);
      //     }
      //     $(list_tab).find('ul').append(`
      //   <li>${this['name']}：${this['count']}</li>
      // `);
      //     if (index == data.length - 1) {
      //       $('#list-tab').append($(list_tab)[0].outerHTML);
      //     }
      //   })


      // }

      function showCard(tmpcard) {
        $(tmpcard).each(function() {
          $(`#${this.name}`).show();
        })
      }


      function getAreaChart(dow_order) {
        let labels = [],
          data = [];
        $(dow_order).each(function() {
          labels.push(this.day);
          data.push(this.count);
        })

        // Set new default font family and font color to mimic Bootstrap's default styling
        Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
        Chart.defaults.global.defaultFontColor = '#858796';

        function number_format(number, decimals, dec_point, thousands_sep) {
          // *     example: number_format(1234.56, 2, ',', ' ');
          // *     return: '1 234,56'
          number = (number + '').replace(',', '').replace(' ', '');
          var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
              var k = Math.pow(10, prec);
              return '' + Math.round(n * k) / k;
            };
          // Fix for IE parseFloat(0.55).toFixed(0) = 0;
          s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
          if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
          }
          if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
          }
          return s.join(dec);
        }

        // Area Chart Example
        var ctx = document.getElementById("AreaChart");
        var myLineChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [{
              label: "訂單",
              backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 205, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(201, 203, 207, 0.2)'
              ],
              borderColor: [
                'rgb(255, 99, 132)',
                'rgb(255, 159, 64)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(54, 162, 235)',
                'rgb(153, 102, 255)',
                'rgb(201, 203, 207)'
              ],
              borderWidth: 1,
              data: data,
            }],
          },
          options: {
            maintainAspectRatio: false,
            layout: {
              padding: {
                left: 10,
                right: 25,
                top: 25,
                bottom: 0
              }
            },
            scales: {
              xAxes: [{
                time: {
                  unit: 'date'
                },
                gridLines: {
                  display: false,
                  drawBorder: false
                },
                ticks: {
                  maxTicksLimit: 7
                }
              }],
              yAxes: [{
                ticks: {
                  maxTicksLimit: 5,
                  padding: 10,
                  // Include a dollar sign in the ticks
                  callback: function(value, index, values) {
                    return number_format(value);
                  }
                },
                gridLines: {
                  color: "rgb(234, 236, 244)",
                  zeroLineColor: "rgb(234, 236, 244)",
                  drawBorder: false,
                  borderDash: [2],
                  zeroLineBorderDash: [2]
                }
              }],
            },
            legend: {
              display: false
            },
            tooltips: {
              backgroundColor: "rgb(255,255,255)",
              bodyFontColor: "#858796",
              titleMarginBottom: 10,
              titleFontColor: '#6e707e',
              titleFontSize: 14,
              borderColor: '#dddfeb',
              borderWidth: 1,
              xPadding: 15,
              yPadding: 15,
              displayColors: false,
              intersect: false,
              mode: 'index',
              caretPadding: 10,
              callbacks: {
                label: function(tooltipItem, chart) {
                  var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
                  return datasetLabel + ': ' + number_format(tooltipItem.yLabel);
                }
              }
            }
          }
        });
      }

      function getPieChart(dow_order) {
        let labels = [],
          data = [];
        $(dow_order).each(function() {
          labels.push(this.name);
          data.push(this.count);
        })

        // Set new default font family and font color to mimic Bootstrap's default styling
        Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
        Chart.defaults.global.defaultFontColor = '#858796';

        // Pie Chart Example
        var ctx = document.getElementById("PieChart");
        var myPieChart = new Chart(ctx, {
          type: 'pie',
          data: {
            labels: labels,
            datasets: [{
              label: 'My First Dataset',
              data: data,
              backgroundColor: [
                
                'rgb(253,243,216)',
                'rgb(210,244,232)',
                'rgb(231,231,234)',
                'rgb(220,227,249)',
                'rgb(250,219,216)',
                // 'rgb(47, 65, 89)',
                // 'rgb(66, 101, 121)'
              ],
              hoverOffset: 4
            }],
          },
          options: {
            maintainAspectRatio: false,
          },
        });
      }

      function inspin() {
        $('#exampleModal .modal-title').html('讀取中')
        $('#exampleModal .modal-footer').html('')
        $('#exampleModal .modal-body').html(`<div class="spinner-border text-primary" role="status">
      <span class="sr-only">Loading...</span>
    </div>`);
        $('#exampleModal').modal('show');
      }

      function test() {
        window.setTimeout(function() {
          $('.section_collapse').css('opacity', '1');
        }, 2000);
        $('.section_item').addClass('default_collapse');
        $('.section_item').on('click', function() {
          var e = $('.section_collapse > .section_item');
          var e2 = $('.section_collapse > .section_item > .content_list');
          if (e.hasClass('expand_collapse')) {
            // 移除所有卡片展開效果
            e.removeClass('expand_collapse');
            // 使所有卡片置中
            e.addClass('justify-content-center');
            // 使所有卡片內層都隱藏
            e2.removeClass('d-flex justify-content-around');
            e2.addClass('d-none');
            // // 當前點擊展開
            $(this).addClass('expand_collapse');
            // // 當前點擊新增由左->右排序
            $(this).removeClass('justify-content-center');
            $(this).addClass('justify-content-start');
            // // 當前內容顯現
            $(this).find('.content_list').removeClass('d-none');
            // // 當前內容顯示，並內層用around排序
            $(this).find('.content_list').addClass('d-flex justify-content-around align-items-center');
          } else {
            // 當前卡片展開，並新增由左->右排序
            $(this).addClass('expand_collapse justify-content-start');
            // 當前卡片移除置中
            $(this).removeClass('justify-content-center');
            // 當前內容顯現
            $(this).find('.content_list').removeClass('d-none');
            // 當前內容顯示，並內層用around排序 
            $(this).find('.content_list').addClass('d-flex justify-content-around');
          }
        })
      }
    </script>
    <link href="/static/css/listTab.css" rel="stylesheet" />
