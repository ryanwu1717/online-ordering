<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;


class RFIDController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }
    //RFID總覽
    public function renderRFIDOverView($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/RFID/index.html');
    }
    //RFID標籤列印
    public function renderRFIDTagPrint($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/RFID/print.html');
    }
    //RFID設定
    public function renderRFIDSetting($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/RFID/setting.html');
    }
    //製令單進出紀錄
    public function renderRFIDTagRecord($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/RFID/rfidTagRecord.html');
    }
    //設備連線狀態
    public function renderRFIDDeviceStatus($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/RFID/rfidDeviceStatus.html');
    }
    public function dataSave($request, $response, $args)
    {
        $db = $this->container->db;
        $data = $request->getParsedBody();
        $business = new RFID($db);
        $aRows = $business->dataSave($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function dataLoad($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new RFID();
        $aRows = $business->dataLoad($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function printLabel($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $oRFID = new RFID();
        $cEPIC = $oRFID->printLabel($data);
        $response = $oRFID->MakeResponse($response, $cEPIC);
        return $response;
    }
    public function settingDataLoad($request, $response, $args)
    {
        $db = $this->container->db;
        $business = new RFID($db);
        $aRfidReader = $business->settingDataLoad();
        $response = $business->MakeResponse($response, $aRfidReader);
        return $response;
    }
    public function settingDataSave($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $db = $this->container->db;
        $business = new RFID($db);
        $result = $business->settingDataSave($data);
        $business->syncAddress($data);
        $response = $business->MakeResponse($response, $result);
        return $response;
    }
    public function getprint($request, $response, $args)
	{
        global $container;
		$business = new RFID($container->db);
		$data = $request->getQueryParams();
		$result = $business->getPrintDetail($data)[0];
        // $response = $response->withHeader('Content-type', 'application/json');
		// $response = $response->withJson($result);
		// return $response;
        $generator = new \Picqer\Barcode\BarcodeGeneratorHTML();
        $params = $request->getQueryParams();
        $report = new report($this->container->db);
        
        // $date = [
        //     'start' => '2019-01-01',
        //     'end' => '2019-01-07',
        // ];
        // if(array_key_exists('date_begin', $params) || array_key_exists('date_end', $params)) {
        //     $date = [
        //         'start' => $params['date_begin'],
        //         'end' => $params['date_end'],
        //     ];
        // }
        // $date = $report->convertDateFormat($date);
        // $params['date_begin'] = $date['start'];
        // $params['date_end'] = $date['end'];

        // $show_date = $params['date_begin'] . " ~ " . $params['date_end'];

        // $result = $report->readAllStaffProductivity($params);

		$rows = "";
		// $rows .= "<td style=\"border:0.1px solid black;\">QQ</td>";

		// foreach ($result as $key => $value) {
		// 	$rows .= "<tr>";
		// 	foreach ($value as $key => $each_value) {
		// 		$rows .= "<td style=\"border:0.1px solid black;\">{$each_value}</td>";
		// 	}
		// 	$rows .= "</tr>";
		// }

		// create new PDF document
		$pdf = new TCPDF_chinese('L', PDF_UNIT, "A4", true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('mil');
		$pdf->SetTitle("人員別生產數量明細");
		$pdf->SetSubject('人員別生產數量明細pdf');
		$pdf->SetKeywords('TCPDF, PDF, mil');

		// set default header data
		// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		// $pdf->SetHeaderData(array(0,64,255), array(0,64,128));
		// $pdf->setFooterData(array(0,64,0), array(0,64,128));

		// remove default header/footer
		$pdf->setPrintHeader(false);
		// $pdf->setPrintFooter(false);

		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
			require_once(dirname(__FILE__) . '/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		// $pdf->SetFont('dejavusans', '', 14, '', true);

		// Set font
		$fontname = TCPDF_FONTS::addTTFfont(__DIR__ . DIRECTORY_SEPARATOR . '/fonts/droidsansfallback.ttf', 'TrueTypeUnicode', '', 96);

		// $pdf->addTTFfont('/Users/laichuanen/droidsansfallback.ttf'); 
		$pdf->SetFont($fontname, '', 12, '', false);
		// $pdf->SetFont('msungstdlight', '', 12);

		// 設定資料與頁面上方的間距 (依需求調整第二個參數即可)
		$pdf->SetMargins(10, 5, 10);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage('P');

		// set text shadow effect
		// $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));

		// Set some content to print
        $tmpdate = date('Y/m/d H:i');
		$result['開單日期'] = date("Y/m/d", strtotime($result['開單日期'] ));
		$result['預計熱處理日期'] = date("Y/m/d", strtotime($result['預計熱處理日期'] ));
		$result['預計開工'] = date("Y/m/d", strtotime($result['預計開工'] ));
		$result['預計完工'] = date("Y/m/d", strtotime($result['預計完工'] ));
		// $result['產品品號barcode'] = 'data:image/png;base64,'. $generator->getBarcode($result['產品品號'], $generator::TYPE_CODE_128);
		$style = array('position'=>'S', 'border'=>false, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>false, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4);
		$result['客戶單號barcode'] = $pdf->serializeTCPDFtagParameters(array($result['客戶單號'], 'C39', '', '', 40, 20, 0.4, $style, 'N'));

		$html = <<<EOD
		<h3 style="text-align: center;">龍畿企業股份有限公司</h3>
		<h4 style="text-align: center;">製造命令單</h4>
		<table border="none" style="width:100%">
			<tr>
			<h4>
				<td style="width:50%">製表日期: {$tmpdate}</td>
				<td style="width:50% ;  text-align:right" >頁次: 1</td>
			</tr>
			<tr>
				<td style="width:50%">開單日期: {$result['開單日期']}</td>
				<td style="width:50%">預計熱處理日期: {$result['預計熱處理日期']}</td>
			</tr>
			<tr>
				<td style="width:50%">預計開工: {$result['預計開工']}</td>
				<td style="width:50%">產品品號: {$result['產品品號']}</td>
			</tr>
			<tr>
				<td style="width:50%">預計完工: {$result['預計完工']}</td>
				<td style="width:50%">品名: {$result['品名']}</td>
			</tr>
			<tr>
				<td style="width:50%">母製令單: {$result['母製令單']}</td>
				<td style="width:50%">規格: {$result['規格']}</td>
			</tr>
			<tr>
				<td style="width:50%">入庫庫別: {$result['入庫庫別']}</td>
				<td style="width:50%">客戶代號: {$result['客戶代號']}</td>
			</tr>
			<tr>
				<td style="width:50%">生產線別: {$result['生產線別']}</td>
				<td style="width:25%">客戶單號: {$result['客戶單號']}</td>
				<td style="width:25%" rowspan="2">
					<tcpdf method="write1DBarcode" params="{$result['客戶單號barcode']}" />
				</td>
			</tr>
			<tr>
			<td style="width:100%" rowspan="2">
				<tcpdf method="write1DBarcode" params="{$result['客戶單號barcode']}" />
			</td>
			</tr>

		</table>
		EOD;
		// $style = array(
		// 	'position' => '',
		// 	'align' => 'C',
		// 	'stretch' => false,
		// 	'fitwidth' => true,
		// 	'cellfitalign' => '',
		// 	'border' => false,
		// 	'hpadding' => 'auto',
		// 	'vpadding' => 'auto',
		// 	'fgcolor' => array(0,0,0),
		// 	'bgcolor' => false, //array(255,255,255),
		// 	'text' => false,
		// 	'font' => 'helvetica',
		// 	'fontsize' => 8,
		// 	'stretchtext' => 4
		// );
		

		// Print text using writeHTMLCell()
		
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
		// $style['position']= 'L';
		// $pdf->write1DBarcode($result['客戶單號'], 'C39', '', '', '', 18, 0.4, $style, 'N');


		// ---------------------------------------------------------

		$file_name = strval("製令單.pdf");
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		ob_end_clean();
		$pdf->Output($file_name, 'D');
        // $result =  $generator->getBarcode('123456', $generator::TYPE_CODE_128);
        // $barcode = $generator->getBarcode('123456', $generator::TYPE_CODE_128);
        // $barcode = base64_encode($barcode);
        // $result = ["img" => $barcode ];


		// $response = $response->withHeader('Content-type', 'application/json');
		// $response = $response->withJson($result);
		// return $response;

		
	}
    /*
    public function renderBusiness($request, $response, $args)
    {

    public function renderBusiness($request, $response, $args)
    {
    }
    public function getEDMRecord($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new RFID($this->container->db);
        $result = $business->getEDMRecord($data);
        foreach ($result as $key => $value) {
            if ($key == 'status' && $value == 'failed') {
                $response = $response->withStatus(500);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getZRecord($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new RFID($this->container->db);
        $result = $business->getZRecord($data);
        $result = $business->getZRecordPicture($result);
        foreach ($result as $key => $value) {
            if ($key == 'status' && $value == 'failed') {
                $response = $response->withStatus(500);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getSparkRecord($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $business = new RFID($this->container->db);
        $result = $business->getSparkRecord($data);
        $result = $business->getSparkRecordPicture($result);
        foreach ($result as $key => $value) {
            if ($key == 'status' && $value == 'failed') {
                $response = $response->withStatus(500);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postCustomerCode($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $business = new Business($this->container->db);
        $result = $business->postCustomerCode($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    */

    
    public function get_machines_area_point($request, $response, $args){
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->getMachineAreaPosition($data)['data'];
        for ($i = 0; $i < count($result); $i++) {
            $point_count = 1;
            foreach ($result[$i]['point'] as $point_list) {
                $result[$i]["point_{$point_count}_x"] = $point_list[0];
                $result[$i]["point_{$point_count}_y"] = $point_list[1];
                $point_count++;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getRFIDOverview($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $data['start'] = $data['date'];
        $data['end'] = date("Y-m-d",strtotime("+1 day",strtotime($data['date'])));;
        $fetch = $rfid->getOrderProcessesDetail($data);
        $result['processing'] = [];
        $result['processing']['count'] = 0;
        $result['processing']['percentage'] = 0;
        $result['waiting'] = [];
        $result['waiting']['count'] = 0;
        $result['waiting']['percentage'] = 0;
        $result['defect'] = [];
        $result['defect']['count'] = 0;
        $result['defect']['percentage'] = 0;
        $result['ready'] = [];
        $result['ready']['percentage'] = 0;
        $result['ready']['count'] = 0;
        $result['abnormal'] = [];
        $result['total'] = 0;
        $result['unfinished'] = 0;
        foreach ($fetch['data'] as $fetch_) {
            if ($fetch_['status'] == 'running') {
                $result['processing']['count'] += $fetch_['amount'];
                $result['unfinished'] += $fetch_['amount'];
            } else if ($fetch_['status'] == 'waiting') {
                $result['waiting']['count'] += $fetch_['amount'];
                $result['unfinished'] += $fetch_['amount'];
            } else if ($fetch_['status'] == 'bad') {
                $result['defect']['count'] += $fetch_['amount'];
            } else if ($fetch_['status'] == 'ready') {
                date_default_timezone_set('Asia/Taipei');
                if($fetch_['order_processes_index'] == $fetch_['order_max'] && ((time() - strtotime($fetch_['time']))/60) >= 1){
                    $result['ready']['count'] += $fetch_['amount'];
                } else {
                    $result['processing']['count'] += $fetch_['amount'];
                    $result['unfinished'] += $fetch_['amount'];
                }
            } 
            $result['total'] += $fetch_['amount'];
        }
        $result['processing']['percentage'] = $result['total']!==0?round($result['processing']['count'] * 100 / $result['total'], 0):0;
        $result['waiting']['percentage'] = $result['total']!==0?round($result['waiting']['count'] * 100 / $result['total'], 0):0;
        $result['percentage'] = $result['total']!==0?round($result['ready']['count'] * 100 / $result['total'], 0):0;
        if ($rfid->getMachineProblem($data)['data']) {
            $result['abnormal']['count'] = count($rfid->getMachineProblem($data)['data']);
        } else {
            $result['abnormal']['count'] = 0;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getRFIDError($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = [];
        $result['data'] = $rfid->getMachineProblem($data)['data'];
        $result['total'] = count($rfid->getMachineProblem($data)['data']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderUnfinishedDate($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $data['start'] = $data['date'];
        $data['end'] = date("Y-m-d",strtotime("+1 day",strtotime($data['date'])));
        $data['type'] = 'notready';
        $fetch = $rfid->getOrderProcesses($data)['data'];
        $group = [];
        $id_checker = [];
        for ($i = 0; $i < count($fetch); $i++) {
            if (!in_array($fetch[$i]['order_processes_id'], $id_checker)) {
                $temp_arr = [
                    "order_processes_id" => $fetch[$i]['order_processes_id'],
                    "order_id" => $fetch[$i]['order_id'],
                    "production_name" => $fetch[$i]['production_name'],
                    "line_name" => $fetch[$i]['line_name'],
                    "processes_name" => $fetch[$i]['processes_name'],
                    "preset_count" => $fetch[$i]['preset_count'],
                    "machine_id" => $fetch[$i]['machine_id'],
                    "work_time" => $fetch[$i]['work_time'],
                    "inbound_time" => "-",
                    "board_time" => "-",
                    "out_bound_time" => "-",
                    "preset_time" => "-",
                    "machine_condition" => "未完工",
                    "img" => $fetch[$i]['file_id'],
                ];
                array_push($group, $temp_arr);
                array_push($id_checker, $fetch[$i]['order_processes_id']);
            }
            $index = array_search($fetch[$i]['order_processes_id'], $id_checker);
            if ($fetch[$i]['status'] == 'waiting') {
                $group[$index]["inbound_time"] = $fetch[$i]['time'];
                $group[$index]["preset_time"] = date("Y-m-d H:i:s", strtotime($fetch[$i]['time'] . "+" . strval($fetch[$i]['work_time'] * 60) . "minute"));
                if ($group[$index]["machine_condition"] != '已完工') {
                    $group[$index]["machine_condition"] = '未完工';
                }
            } else if ($fetch[$i]['status'] == 'ready') {
                $group[$index]["out_bound_time"] = $fetch[$i]['time'];
                $group[$index]["machine_condition"] = '已完工';
            } else if ($fetch[$i]['status'] == 'running') {
                $group[$index]["board_time"] = $fetch[$i]['time'];
                if ($group[$index]["machine_condition"] != '已完工') {
                    $group[$index]["machine_condition"] = '未完工';
                }
            }
        }
        $result = [];
        $result['data'] = [];
        $result['src'] = '/3DConvert/PhaseGallery/order_image/';
        for ($i = 0; $i < count($group); $i++) {
            if ($group[$i]["machine_condition"] == '未完工') {
                array_push($result['data'], $group[$i]);
            }
        }
        $result['total'] = count($result['data']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getNextWeekPredictAmount($request, $response, $args)
    {
        $params = $request->getQueryParams();
        // $params["minus_seven_date"] = "";
        // if (date("N", strtotime($params["request_date"])) == 1) {  /* weekend check */
        //     $params["minus_seven_date"] = date("Y-m-d", strtotime($params["request_date"] . " - 10 days"));  /* include request_date => - 6 - (2 * 2) weekends */
        // } elseif (date("N", strtotime($params["request_date"])) != 1 && date("N", strtotime($params["request_date"])) < 6) {
        //     $params["minus_seven_date"] = date("Y-m-d", strtotime($params["request_date"] . " - 8 days"));  /* include request_date => - 6 - 2 weekends */
        // } else {
        //     return "invalid request date (not a weekday)";
        // }
        $rfid = new RFID($this->container->db);
        $last_week_amount = $rfid->readLastWeekAmount([
            'start'=> date("Ymd",strtotime("-6 day")),
            "end"=> date("Ymd")
        ]);  /* DB data */
        $five_days_amount = $rfid->readLastWeekAmount([
            'start'=> date("Ymd",strtotime("-4 day")),
            "end"=> date("Ymd")
        ]);  /* DB data */
        $three_days_amount = $rfid->readLastWeekAmount([
            'start'=> date("Ymd",strtotime("-2 day")),
            "end"=> date("Ymd")
        ]);  /* DB data */
        $result = [
            'today' => $rfid->readLastWeekAmount([
                'start'=> date("Ymd"),
                "end"=> date("Ymd")
            ])
        ];
        $result['week'] = $rfid->iterateNextWeekPredictAmount(["request_date"=>date('Y-m-d')], $last_week_amount,7);
        $result['five_days'] = $rfid->iterateNextWeekPredictAmount(["request_date"=>date('Y-m-d')], $five_days_amount,5);
        $result['three_days'] = $rfid->iterateNextWeekPredictAmount(["request_date"=>date('Y-m-d')], $three_days_amount,3);
        $result = $rfid->mergePredictAmount($result);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getOrderUnfinished($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $data['type'] = 'notready';
        $fetch = $rfid->getOrderProcesses($data)['data'];
        $group = [];
        $id_checker = [];
        for ($i = 0; $i < count($fetch); $i++) {
            if (!in_array($fetch[$i]['order_processes_id'], $id_checker)) {
                $temp_arr = [
                    "order_processes_id" => $fetch[$i]['order_processes_id'],
                    "order_id" => $fetch[$i]['order_id'],
                    "production_name" => $fetch[$i]['production_name'],
                    "line_name" => $fetch[$i]['line_name'],
                    "processes_name" => $fetch[$i]['processes_name'],
                    "preset_count" => $fetch[$i]['preset_count'],
                    "machine_id" => $fetch[$i]['machine_id'],
                    "work_time" => $fetch[$i]['work_time'],
                    "inbound_time" => "-",
                    "board_time" => "-",
                    "out_bound_time" => "-",
                    "preset_time" => "-",
                    "machine_condition" => "未完工",
                    "img" => $fetch[$i]['file_id'],
                ];
                array_push($group, $temp_arr);
                array_push($id_checker, $fetch[$i]['order_processes_id']);
            }
            $index = array_search($fetch[$i]['order_processes_id'], $id_checker);
            if ($fetch[$i]['status'] == 'waiting') {
                $group[$index]["inbound_time"] = $fetch[$i]['time'];
                $group[$index]["preset_time"] = date("Y-m-d H:i:s", strtotime($fetch[$i]['time'] . "+" . strval($fetch[$i]['work_time'] * 60) . "minute"));
                if ($group[$index]["machine_condition"] != '已完工') {
                    $group[$index]["machine_condition"] = '未完工';
                }
            } else if ($fetch[$i]['status'] == 'ready') {
                $group[$index]["out_bound_time"] = $fetch[$i]['time'];
                $group[$index]["machine_condition"] = '已完工';
            } else if ($fetch[$i]['status'] == 'running') {
                $group[$index]["board_time"] = $fetch[$i]['time'];
                if ($group[$index]["machine_condition"] != '已完工') {
                    $group[$index]["machine_condition"] = '未完工';
                }
            }
        }
        $result = [];
        $result['data'] = [];
        $result['src'] = '/3DConvert/PhaseGallery/order_image/';
        for ($i = 0; $i < count($group); $i++) {
            if ($group[$i]["machine_condition"] == '未完工') {
                array_push($result['data'], $group[$i]);
            }
        }
        $result['total'] = count($result['data']);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getRFIDPosition($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $pgc = new PhaseGalleryController($this->container->db);
        $result = $rfid->getMachinePosition($data)['data'];
        $result = $pgc->getPointList($result);
        for ($i = 0; $i < count($result); $i++) {
            $point_count = 1;
            foreach ($result[$i]['point_list'] as $point_list) {
                $result[$i]["point_{$point_count}_x"] = $point_list[0];
                $result[$i]["point_{$point_count}_y"] = $point_list[1];
                $point_count++;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postRFIDPosition($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->postMachinePosition($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function uploadFloorImage($request, $response, $args)
    {
        $data = $request->getParams();
        $data['files'] = $request->getUploadedFiles();
        $phasegallery = new PhaseGallery($this->container->db);
        $rfid = new RFID($this->container->db);
        $file = $phasegallery->uploadFile($data);
        unset($data['files']);
        $file['floor_id'] = $data['floor_id'];
        $result = $rfid->updateFloorImage($file);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getFloor($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->getFloor($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function postNewFloor($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $stmt = $rfid->insertNewFloor($params);
        $result = $rfid->readNewestFloor();
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patchFloor($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        foreach ($body as $key => $value) {
            $result = $rfid->updateFloor($value);
            if ($result['status'] !== 'success') {
                break;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteFloor($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->deleteFloor($body);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function deleteRFIDPosition($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->deleteMachinePosition($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function updateRFIDPosition($request, $response, $args)
    {
        $params = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        foreach ($params as $params_) {
            $result = $rfid->updateMachinePosition($params_);
            // $rfid->updateAntennaMachine($params_);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function getFloorImage($request, $response, $args)
    {
        $data = $args;
        $rfid = new RFID($this->container->db);
        $file = $rfid->getFloorImage($data);
        $source = $this->compressImage($file, $file, 100);
        ob_clean();
        $width = imagesx($source);
        $height = imagesy($source);
        $newImage   = imagecreatetruecolor($width, $height);
        $white      = imagecolorallocate($newImage, 255, 255, 255);
        imagefilledrectangle($newImage, 0, 0, $width, $height, $white);
        imagecopy($newImage, $source, 0, 0, 0, 0, $width, $height);
        imagejpeg($newImage);
        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . "phasegallery.jpeg" . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public');
        return $response;
    }
    function compressImage($source = false, $destination = false, $quality = 80, $filters = false)
    {
        $info = getimagesize($source);
        switch ($info['mime']) {
            case 'image/jpeg':
                /* Quality: integer 0 - 100 */
                if (!is_int($quality) or $quality < 0 or $quality > 100) $quality = 80;
                return imagecreatefromjpeg($source);

            case 'image/gif':
                return imagecreatefromgif($source);

            case 'image/png':
                /* Quality: Compression integer 0(none) - 9(max) */
                if (!is_int($quality) or $quality < 0 or $quality > 9) $quality = 6;
                return imagecreatefrompng($source);

            case 'image/webp':
                /* Quality: Compression 0(lowest) - 100(highest) */
                if (!is_int($quality) or $quality < 0 or $quality > 100) $quality = 80;
                return imagecreatefromwebp($source);

            case 'image/bmp':
                /* Quality: Boolean for compression */
                if (!is_bool($quality)) $quality = true;
                return imagecreatefrombmp($source);

            default:
                return;
        }
    }

    public function getOrderProcessesOuter($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->getOrderProcessesOuter($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function printOrderProcessesLabel($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $oRFID = new RFID();
        $data += $oRFID->getOrderProcessesLabel($data);
        $data['RFID'] = $oRFID->getRFIDOuter($data);
        // $data['amount'] = $RFIDO['TA015'];
        // $data['TA003'] = $RFIDO['TA003'];
        // $data['TA004'] = $RFIDO['TA004'];
        $data['user_id'] = @$_SESSION['id'];
        $data['order_processes_id'] = $oRFID->getOrderProcessesFK($data);
        $data['rfid_tag'] = '123';
        $data['cPrinterName'] = $oRFID->get_rfid_printer_outer($data);
        // $data['rfid_tag'] = $oRFID->printLabel($data);
        $data['rfid_tag_id'] = $oRFID->insertRFIDTag($data);
        $result = $oRFID->insertOrderProcessesRFIDTag($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postAddress($request, $response, $args)
    {
        $body = $request->getParsedBody();
        // $business = new Discharge($this->container->db);
        $rfid = new RFID($this->container->db);
        $result = $rfid->createAddress($body);
        // $fetch = $rfid->readAddress();
        // $data = [];
        // $data['aRfidReader'] = [];
        // foreach ($fetch as $key => $value) {
        //     $reader = 'Reader' . ($key + 1);
        //     $data['aRfidReader'][$reader] = [
        //         'cIP' => $value['address'],
        //         'iPort' => $value['port']
        //     ];
        // }
        // $business->settingDataSave($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAddress($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->readAddress($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function patchAddress($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->updateAddress($body);
        // $oSetting = json_decode(json_encode($rfid->settingDataLoad()), TRUE);
        // $fetch = $rfid->readAddress();
        // foreach ($fetch as $key => $value) {
        //     $reader = 'Reader' . ($key + 1);
        //     $oSetting['aRfidReader'][$reader] = [];
        //     if ($value['address'] != null) {
        //         $oSetting['aRfidReader'][$reader]['cIP'] = $value['address'];
        //     }
        //     if ($value['port'] != null) {
        //         $oSetting['aRfidReader'][$reader]['iPort'] = $value['port'];
        //     }
        // }
        // $oSetting = $oSetting['aRfidReader'];
        // $result = $rfid->settingDataSave($oSetting);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function deleteAddress($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->deleteAddress($body);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postAntenna($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->createAntenna($body);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAntenna($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->readAntenna($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function patchAntenna($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        foreach ($body as $key => $value) {
            $result = $rfid->updateAntenna($value);
            if ($result['status'] !== 'success') {
                break;
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function deleteAntenna($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->deleteAntenna($body);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postOrderProcessesReferenceKey($request, $response, $args)
    {
        $body = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        foreach ($body as $key => $value) {
            $result = $rfid->createOrderProcessesReferenceKey($key, $value);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOrderProcessesReferencekey($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->readOrderProcessesReferenceKey($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getOrderProcessesReferenceValue($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->readOrderProcessesReferenceValue($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAddressDetail($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->readAddressDetail($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function postRFIDAntennaMachine($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        foreach ($data as $data_) {
            $result = $rfid->insertRFIDAntennaMachine($data_);
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getAntennaMachine($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->readAntennaMachine($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function getMachineStatus($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->readMachineStatus($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function get_machine_process($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_machine_process($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function get_rfid_order_processes_machine_area($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_rfid_order_processes_machine_area($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function get_rfid_order_processes_machine($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_rfid_order_processes_machine($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function get_rfid_order_processes($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_rfid_order_processes($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    public function get_machines_outer($request, $response, $args){
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_machines_outer($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_line_machine_outer($request, $response, $args){
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_line_machine_outer($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_machine_set($request, $response, $args){
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_machine_set($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_rfid_status($request, $response, $args){
        $params = $request->getQueryParams();
        $rfid = new RFID($this->container->db);
        $result = $rfid->get_rfid_status($params);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function post_rfid_status($request, $response, $args){
        $datas = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->post_rfid_status($datas);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function patch_rfid_status($request, $response, $args){
        $datas = $request->getParsedBody();
        $rfid = new RFID($this->container->db);
        $result = $rfid->patch_rfid_status($datas);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function delete_rfid_status($request, $response, $args){
        $datas = $request->getParsedBody();
        $datas = $datas['data'];
        $rfid = new RFID($this->container->db);
        $result = $rfid->delete_rfid_status($datas);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    
    public function get_rfid_order_processes_filter($request, $response, $args){
        $data = $request->getQueryParams();
        $RFID = new RFID($this->container->db);
        $result = [];
        $result['line'] = $RFID->get_lines($data);
        $result['machine'] = $RFID->get_machines($data);
        $result['process'] = $RFID->get_processes($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_machines_area_floor($request, $response, $args){
        $data = $request->getQueryParams();
        $RFID = new RFID($this->container->db);
        $result = $RFID->get_machines_area_floor($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function get_rfid_printer($request, $response, $args){
        $data = $request->getQueryParams();
        $RFID = new RFID($this->container->db);
        $result = $RFID->get_rfid_printer($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}
