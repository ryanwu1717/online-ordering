<?php include(__DIR__ . '/basic/header.html'); ?>
<script src="/dropzone/dist/dropzone.js"></script>
<link rel="stylesheet" href="/dropzone/dist/dropzone.css">
<div>
    <div>
        <input class="form-control" type="text" placeholder="輸入以查詢訂單內容">
    </div>
</div>
<div class="table-responsive">
    <table class="table" id="orderTable">
        <thead class="thead-dark">
            <tr>
                <th scope="col">訂單日期</th>
                <th scope="col">定單單別單號序號</th>
                <th scope="col">品號</th>
                <th scope="col">品名</th>
                <th scope="col">規格</th>
                <th scope="col">客戶圖號</th>
                <th scope="col">圖面板次</th>
                <th scope="col">運輸方式</th>
                <th scope="col">運輸方式中文</th>
                <th scope="col">鍍鈦種類</th>
                <th scope="col">鍍鈦材質</th>
                <th scope="col">材質代號</th>
                <th scope="col">材質名稱</th>
                <th scope="col">交易幣別</th>
                <th scope="col">匯率</th>
                <th scope="col">訂單數量</th>
                <th scope="col">訂單單價</th>
                <th scope="col">訂單金額</th>
                <th scope="col">預計採購廠商</th>
                <th scope="col">廠商簡稱</th>
            </tr>
        </thead>
        <tbody >
            <tr>
                <th scope="row">20201201</th>
                <td>2210-1091201001-0001</td>
                <td>06080010001000011002</td>
                <td>HEX PUNCH</td>
                <td>4.06*5*7*100L</td>
                <td>A0005-008-00</td>
                <td>05 201116</td>
                <td>7</td>
                <td>"EMS"</td>
                <td>1</td>
                <td>"TiN"</td>
                <td>20</td>
                <td>"MIL60S"</td>
                <td>"USD"</td>
                <td>"30.1875"</td>
                <td>20</td>
                <td>"37.2"</td>
                <td>"744"</td>
                <td>"I0005"</td>
                <td>"衡泰"</td>
            </tr>
        </tbody>
    </table>

</div>

<?php include(__DIR__ . '/basic/footer.html'); ?>

<script>
</script>