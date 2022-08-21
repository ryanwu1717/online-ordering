<?php

use nknu\base\xBaseWithDbop;

class RFID extends xBaseWithDbop
{
    protected $container;
    protected $db;
    protected $db_sqlsrv;
    public function __construct()
    {
        parent::__construct();
        global $container;
        $this->container = $container;
        $this->db = $container->db;
        $this->db_sqlsrv = $container->db_sqlsrv;
    }
    public function getPrintDetail($data){
        $values = [
            'TA001' => 0,
            'TA002' => 0,
            
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        // var_dump($values);
        $sql = " SELECT TOP 100 RTRIM(LTRIM([MOCTA].[TA001]))+' '+RTRIM(LTRIM([MOCTA].[TA002]))\"母製令單\"
            ,MOCTA.TA003 \"開單日期\"
            ,MOCTA.TA009 \"預計開工\"
            ,MOCTA.TA010 \"預計完工\"
            ,GETDATE()  \"製表日期\"
            ,MOCTA.TA010 \"預計完工\"
            ,MOCTA.TA020 + '    ' + CMSMC.MC002 \"入庫庫別\"
            ,MOCTA.TA021 + '    ' + CMSMD.MD002 \"生產線別\"
            ,COPTD.TD008 \"訂單數量\"
            ,COPTD.TD010 \"訂單單位\"
            ,CMSXB.XB002 \"材質\"
            ,COPTD.TD205 \"硬度\"
            ,' ' \"焊接爐號\"
            ,PURMA.MA002 \"加工廠商\"
            ,COPTD.TD020 \"訂單備註事項\"
            ,COPTD.TD015 \"訂單單頭備註\"
            ,MOCTA.TA029 \"製令備註\"
            ,PURMA.MA002 \"加工廠商\"
            ,RTRIM(LTRIM([MOCTA].[TA001]))+' '+RTRIM(LTRIM([MOCTA].[TA002]))\"製令編號\" 
            ,RTRIM(LTRIM([MOCTA].[TA026]))+' '+RTRIM(LTRIM([MOCTA].[TA027])) +' '+RTRIM(LTRIM([MOCTA].[TA028]))\"製令編號\" 
            ,MOCTA.TA201  \"預計熱處理日期\"
            ,COPTD.TD004 \"產品品號\"
            ,COPTD.TD005  \"品名\"
            ,COPTD.TD006  \"規格\"
            ,COPTC.TC004  \"客戶代號\"
            ,COPTC.TC012  \"客戶單號\"
            ,COPTC.TC020  \"代理訂單\"
            ,COPTD.TD201  \"圖號\"
            ,COPTD.TD214  \"圖面板次\"
            ,COPTD.TD204  \"鍍鈦方式\"
            ,COPTD.TD207  \"印logo\"
            ,COPTD.TD038  \"生產包裝資訊\"
            ,COPTD.TD200  \"加印文字內容\"
            ,COPTD.TD015 \"訂單單身備註\"
            ,COPTD.TD013 \"訂單交期\"

            -- MOCTA.TA001  \"製令單號\",
            -- MOCTA.TA002,
            -- COPTD.TD013 \"訂單交期\",
            -- COPTD.TD008 \"訂單數量\",
            -- MOCTA.TA009 \"預計生產完成日\",
            -- COPTD.TD201 order_name,
            -- COPTD.TD201 \"客戶圖號\", 
            -- COPTD.TD001, 
            -- COPTD.TD002, 
            -- COPTD.TD003,
            -- ROW_NUMBER() OVER (ORDER BY MOCTA.TA002 ASC) \"key\"
        FROM MIL.dbo.MOCTA
        RIGHT OUTER JOIN MIL.dbo.COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
        LEFT JOIN MIL.dbo.MOCTB ON  (MOCTA.TA006 = MOCTB.TB003 AND MOCTA.TA001 =  MOCTB.TB001 AND MOCTA.TA002 =  MOCTB.TB002)
        LEFT JOIN MIL.dbo.CMSMC ON  MOCTA.TA020 =CMSMC.MC001 
        LEFT JOIN MIL.dbo.CMSMD ON  MOCTA.TA021=CMSMD.MD001 
        LEFT JOIN MIL.dbo.CMSXB ON  CMSXB.XB001 = COPTD.TD205
        LEFT JOIN MIL.dbo.PURMA ON  MOCTA.TA032 = PURMA.MA001
        LEFT JOIN MIL.dbo.COPTC ON  COPTC.TC001 = COPTD.TD001 AND COPTC.TC002 = COPTD.TD002

        -- WHERE MOCTA.TA001  IS nOT NULL AND MOCTA.TA002  IS nOT NULL
        WHERE   RTRIM(LTRIM([MOCTA].[TA001]))=  RTRIM(LTRIM(:TA001)) AND  RTRIM(LTRIM([MOCTA].[TA002])) =  RTRIM(LTRIM(:TA002))
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute($values)) return ["status"=>$stmt->errorInfo()];
        $result = [];
        $result=$stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row_key => $row) {
            foreach ($row as $key => $value) {
                if ($this->isJson($value)) {
                    $result[$row_key][$key] = json_decode($value, true);
                }
            }
        }
        return $result;
    }

    public function dataSave($aRows)
    {
        $aFields = ["cReaderName", "cIP", "cTagID", "iAntennaID", "cTagEvent", "dTime", "bTest"];
        $aData_Insert = $this->oDbop->MakeInsertData("RFID_TABLE_Log", $aFields, $aRows);
        if ($this->bErrorOn) {
            return;
        }

        $this->oDbop->Connect("db");
        if ($this->oDbop->bErrorOn) {
            return;
        }
        $result = $this->oDbop->RunSql($aData_Insert["cSql"], $aData_Insert["htSql"]);
        if ($this->oDbop->bErrorOn) {
            return;
        }
        $this->oDbop->Disconnect();

        $this->SetOK();
        return "";
    }
    public function dataLoad($data) {
        $iQueryType = -1;
        $bCheckInput = false;
        if (array_key_exists('dTime', $data)) {
            $iQueryType = 0;
            $dTime = $data["dTime"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cReaderName\", \"cIP\", \"cTagID\", \"iAntennaID\", \"cTagEvent\", \"dTime\"
	            FROM public.\"RFID_TABLE_Log\"
                WHERE ABS(extract(epoch from (:dTime - \"dTime\" ))) < 50
                ORDER BY ABS(extract(epoch from (:dTime - \"dTime\" ))) ASC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('dTime_Start', $data) && array_key_exists('dTime_End', $data)) {
            $iQueryType = 1;
            $dTime_Start = $data["dTime_Start"];
            $dTime_End = $data["dTime_End"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cReaderName\", \"cIP\", \"cTagID\", \"iAntennaID\", \"cTagEvent\", \"dTime\"
	            FROM public.\"RFID_TABLE_Log\"
                WHERE \"dTime\" BETWEEN :dTime_Start AND :dTime_End
                ORDER BY \"iAutoIndex\" DESC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('cIndexList', $data)) {
            $iQueryType = 2;
            $cIndexList = $data["cIndexList"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cReaderName\", \"cIP\", \"cTagID\", \"iAntennaID\", \"cTagEvent\", \"dTime\"
	            FROM public.\"RFID_TABLE_Log\"
                WHERE \"iAutoIndex\" IN ({$cIndexList})
                ORDER BY \"iAutoIndex\"
            ";
            $bCheckInput = true;
        }
        if (!$bCheckInput) { $this->SetError("傳入參數不正確"); return; }

        if ($iQueryType == 0) {
            $htSql = [ 'dTime'=>$dTime ];;
        } else if ($iQueryType == 1) {
            $htSql = [ 'dTime_Start'=>$dTime_Start, 'dTime_End'=>$dTime_End ];;
        } else {    //$iQueryType == 2
            $htSql = [];
        }
        $this->oDbop->Connect("db"); if ($this->oDbop->bErrorOn) { return; }
        $result = $this->oDbop->SelectSql($cSql, $htSql); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();
        $this->SetOK(); return $result;
    }

    public function printLabel($data)
    {
        $cEPIC = null;
        if (!isset($data["cPrinterName"])) {
            // $this->SetError("No data. cPrinterName");
            $data["cPrinterName"] = "Printer1";
            // goto EndFunction;
        }
        if (!isset($data["cLine1"])) {
            $this->SetError("No data. cLine1");
            goto EndFunction;
        }
        if (!isset($data["cLine2"])) {
            $this->SetError("No data. cLine2");
            goto EndFunction;
        }

        $cData = nknu\utility\xStatic::ToJson(["cPrinterName" => $data["cPrinterName"], "cLine1" => $data["cLine1"], "cLine2" => $data["cLine2"]]);
        $cJsonData = nknu\utility\xStatic::ToJson(["apiName" => "labelPrinter", "action" => "printRfidLabel", "data" => $cData]);
        $oCall = new nknu\utility\xCall();
        $cEPIC = $oCall->WindowFormApi($cJsonData);
        if ($oCall->bErrorOn) {
            $this->SetError($oCall->cMessage);
            goto EndFunction;
        }
        $this->SetOK();

        EndFunction:
        return $cEPIC;
    }

    public function settingDataLoad()
    {
        return $this->callApiByArray(["apiName" => "setting", "action" => "get", "rfidReader" => true]);
    }
    public function settingDataSave($aRfidReader)
    {
        $cJson = json_encode($aRfidReader);
        return $this->callApiByArray(["apiName" => "setting", "action" => "set", "rfidReader" => $cJson]);
    }
    public function callApiByArray($aData)
    {
        $cJsonData = json_encode($aData);
        return $this->callApiByJson($cJsonData);
    }
    public function callApiByJson($cJsonData)
    {
        $oCall = new nknu\utility\xCall();
        $cJsonResult = $oCall->WindowFormApi($cJsonData);
        if ($oCall->bErrorOn) {
            $this->SetError($oCall->cMessage);
            return null;
        }
        $oCallBack = $cJsonResult == null ? true : nknu\utility\xStatic::ToClass($cJsonResult);
        $this->SetOK();
        return $oCallBack;
    }

    /*
    public function getZRecord($data)
    {
        if (array_key_exists('time', $data)) {
            $sql = "SELECT *
                FROM(
                    SELECT \"iAutoIndex\", \"fValue\", \"dTime\", \"bDisconnect\", ROW_NUMBER() OVER()
	                FROM public.\"Z_TABLE_Log\"
                    WHERE ABS(extract(epoch from (:time - \"dTime\" ))) < 30
                    ORDER BY ABS(extract(epoch from (:time - \"dTime\" ))) ASC
                    limit 60
                )result
                ORDER BY \"iAutoIndex\" DESC;
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':time', $data['time']);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $result = [
                "status" => "success",
                "data" => $result
            ];
            return $result;
        }
        return ["status" => "failed", "message" => "time欄位不存在"];
    }
    public function getZRecordPicture($data)
    {
        if (array_key_exists('data', $data)) {
            foreach ($data['data'] as $key => $value) {
                if ($value['row_number'] == 1) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://172.25.25.34/Z/ajaxImage.aspx?iAutoIndex=" . $value['iAutoIndex']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    $head = curl_exec($ch);
                    $result = json_decode($head, true);
                    curl_close($ch);
                    foreach ($result as $key => $value) {
                        if ($key == 'oData') {
                            $data['src'] = 'data:image/png;base64,' . $value;
                        }
                    }
                }
            }
            return $data;
        }
        return $data;
    }
    public function getSparkRecord($data)
    {
        if (array_key_exists('time', $data)) {
            $sql = "SELECT *
                FROM(
                    SELECT \"iAutoIndex\", \"iCenterX\", \"iCenterY\", CONCAT(\"iCenterX\", ',' , \"iCenterY\") AS \"火花亮點\", \"iRadius\" AS \"火花大小\", \"iBright\", \"dTime\", ROW_NUMBER() OVER()
                    FROM public.\"Discharge_TABLE_Log\"
                    WHERE ABS(extract(epoch from (:time - \"dTime\" ))) < 30
                    ORDER BY ABS(extract(epoch from (:time - \"dTime\" ))) ASC
                    limit 60
                )result
                ORDER BY \"dTime\" ASC
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':time', $data['time']);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $time_simu = 0.0;
            $last = [];
            $ack = [];
            foreach ($result as $key => $value) {
                if (empty($last)) {
                    $last = $value;
                } else if (abs($value['iCenterX'] - $last['iCenterX']) < 10 && abs($value['iCenterY'] - $last['iCenterY']) < 10 && $value['火花大小'] > 100) {
                    $date = new DateTime($last['dTime']);
                    $date2 = new DateTime($value['dTime']);
                    $diffInSeconds = $date2->getTimestamp() - $date->getTimestamp();
                    $time_simu += $diffInSeconds;
                } else {
                    $time_simu = 0;
                }
                $result[$key]['火花持續時間'] = $time_simu;
                $last = $value;
                array_unshift($ack, $result[$key]);
            }
            $result = [
                "status" => "success",
                "data" => $ack
            ];
            return $result;
        }
        return ["status" => "failed", "message" => "time欄位不存在"];
    }
    public function getSparkRecordPicture($data)
    {
        if (array_key_exists('data', $data)) {
            foreach ($data['data'] as $key => $value) {
                if ($value['row_number'] == 1) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://172.25.25.34/Discharge/ajaxImage.aspx?iAutoIndex=" . $value['iAutoIndex']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    $head = curl_exec($ch);
                    $result = json_decode($head, true);
                    curl_close($ch);
                    foreach ($result as $key => $value) {
                        if ($key == 'oData') {
                            $data['src'] = 'data:image/png;base64,' . $value;
                        }
                    }
                }
            }
            return $data;
        }
        return $data;
    }
    */
    public function getOrderProcessesDetail($data)
    {
        $business = new Business($this->container->db);
        $allProcess = json_encode($business->getallProcess());
        if (array_key_exists('start', $data) && array_key_exists('end', $data) && array_key_exists('processes_id', $data)) {
            $sql = "SELECT DISTINCT ON (order_id) order_processes.order_id, order_processes_detail.order_processes_id, amount, status, time, order_processes.order_processes_index, order_max.order_max
                FROM order_processes_detail
                LEFT JOIN order_processes ON order_processes_detail.order_processes_id = order_processes.order_processes_id
                LEFT JOIN processes ON order_processes.processes_id = processes.processes_id
                LEFT JOIN json_to_recordset('{$allProcess}')as process_outer (id text,name text)
                    ON process_outer.name = processes.processes_name
                LEFT JOIN (
                    SELECT order_id, MAX(order_processes_index) order_max FROM order_processes
                    GROUP BY order_id
                ) order_max ON order_processes.order_id = order_max.order_id
                WHERE :start::DATE <= time AND time <= :end::DATE AND trim(process_outer.id) = trim(:processes_id)
                AND amount IS NOT NULL AND order_processes.order_id IS NOT NULL
                ORDER BY order_id ASC, time DESC
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':start', $data['start']);
            $stmt->bindValue(':end', $data['end']);
            $stmt->bindValue(':processes_id', $data['processes_id']);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $result = [
                "status" => "success",
                "data" => $result
            ];
            return $result;
        }
        return ["status" => "failed", "message" => "time欄位不存在"];
    }
    public function getMachineProblem($data)
    {
        if (array_key_exists('date', $data)) {
            if ($data['size'] < 0) {
                $length = '';
                $start = 0;
                $limit = '';
            } else {
                $length = $data['cur_page'] * $data['size'];
                $start = $length - $data['size'];
                $limit = 'LIMIT';
            }
            $sql = "SELECT * FROM (
                SELECT machine_problem_id, machine_problem.machine_id, machine_name, problem, time, ROW_NUMBER() OVER (ORDER BY machine_problem_id) row_num
                FROM public.machine_problem
                LEFT JOIN machine ON machine_problem.machine_id = machine.machine_id
                WHERE CAST(:date AS DATE) = time
                {$limit} {$length}
                ) mp
                WHERE mp.row_num > {$start}
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':date', $data['date']);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [
                "status" => "success",
                "data" => $result
            ];
            return $result;
        }
        return ["status" => "failed", "message" => "time欄位不存在"];
    }
    public function getOrderProcesses($data)
    {
        if (array_key_exists('start', $data) && array_key_exists('end', $data)) {
            if ($data['size'] < 0) {
                $length = '';
                $start = 0;
                $limit = '';
            } else {
                $length = $data['cur_page'] * $data['size'];
                $start = $length - $data['size'];
                $limit = 'LIMIT';
            }
            if ($data['type'] == 'ready') {
                $type = 'NOT';
            } else {
                $type = '';
            }
            if (array_key_exists('machine_id', $data)) {
                $condition = ' WHERE line_machine.machine_id = :machine_id';
            } else {
                $condition = '';
            }
            $sql = "SELECT order_processes.order_processes_id, order_processes.order_id, \"item\".name production_name, line_name, processes_name, default_amount preset_count, line_machine.machine_id,
                time, status, work_time, coptd_file.file_id
                FROM (
                        SELECT inside.order_processes_id, order_id, line_machine_processes_id, processes_id, default_amount, work_time FROM(
                            SELECT order_processes.order_processes_id, order_id, line_machine_processes_id, processes_id, default_amount, work_time, ROW_NUMBER() OVER (ORDER BY order_processes.order_processes_id) row_num
                            FROM order_processes
                            LEFT JOIN order_processes_detail ON order_processes.order_processes_id = order_processes_detail.order_processes_id AND status = 'ready'
                            WHERE time IS {$type} NULL
                            GROUP BY order_processes.order_processes_id
                            {$limit} {$length}
                        ) inside
                        LEFT JOIN order_processes_detail ON inside.order_processes_id = order_processes_detail.order_processes_id
                        WHERE CAST(:start AS DATE) <= time AND time <= CAST(:end AS DATE) AND row_num > {$start}
                        GROUP BY inside.order_processes_id, order_id, line_machine_processes_id, processes_id, default_amount, work_time
                ) order_processes
                LEFT JOIN order_processes_detail ON order_processes.order_processes_id = order_processes_detail.order_processes_id
                LEFT JOIN public.\"order\" ON order_processes.order_id = \"order\".order_id
                LEFT JOIN \"item\" ON \"order\".item_id = \"item\".id
                LEFT JOIN line_machine_processes ON order_processes.line_machine_processes_id = line_machine_processes.line_machine_processes_id
                LEFT JOIN line_machine ON line_machine_processes.line_machine_id = line_machine.line_machine_id
                LEFT JOIN line ON line_machine.line_id = line.line_id
                LEFT JOIN processes ON order_processes.processes_id = processes.processes_id
                LEFT JOIN machine ON line_machine.machine_id = machine.machine_id
                LEFT JOIN phasegallery.coptd_file ON \"order\".order_id = coptd_file.order_id
                {$condition}
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':start', $data['start']);
            $stmt->bindValue(':end', $data['end']);
            if (array_key_exists('machine_id', $data)) {
                $stmt->bindValue(':machine_id', $data['machine_id']);
            }
            $stmt->execute();
            $result = $stmt->fetchAll();
            $result = [
                "status" => "success",
                "data" => $result
            ];
            return $result;
        }
    }
    public function readLastWeekAmount($params)
    {
        $values = [
            'start'=> date("Ymd",strtotime("-7 day")),
            "end"=> date("Ymd")
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$params)){
                $values[$key] = $params[$key];
            }
        }
        // MOCTA.TA001 +'-'+MOCTA.TA002 "(製令單別)+(製令單號)",MOCTA.TA009 "預計生產完成日",COPTD.TD201 "客戶圖號"
        $sql = "SELECT [SFCTA].[TA004] as processes_id, [CMSMW].[MW002] as processes_name,COUNT(*) AS amount,COUNT(*) AS [current],
                (COUNT(*)-COUNT(CASE WHEN SFCTA.TA032='Y' THEN 1 END)-COUNT(CASE WHEN SFCTA.TA032='N' THEN 1 END)) AS [bad],
                COUNT(CASE WHEN SFCTA.TA032='N' THEN 1 END) AS unfinish,COUNT(CASE WHEN SFCTA.TA032='Y' THEN 1 END) AS [done],[SFCTA].[TA009],
                ROW_NUMBER () OVER (
                    PARTITION BY [SFCTA].[TA004]
                    ORDER BY [SFCTA].[TA004], [SFCTA].[TA009]
                ) row_number
            FROM [MIL].[dbo].[CMSMW],[MIL].[dbo].[COPTD],[MIL].[dbo].[MOCTA],[MIL].[dbo].[SFCTA]
            WHERE CMSMW.MW001=SFCTA.TA004
            and COPTD.TD001=MOCTA.TA026 
            and COPTD.TD002=MOCTA.TA027
            and COPTD.TD003=MOCTA.TA028
            and SFCTA.TA001=MOCTA.TA001 
            and SFCTA.TA002=MOCTA.TA002
            AND MOCTA.TA001=SFCTA.TA001 
            and MOCTA.TA002=SFCTA.TA002
            AND ([SFCTA].[TA009] BETWEEN CONVERT(NVARCHAR,'{$values['start']}',112) AND CONVERT(NVARCHAR,'{$values['end']}',112))
            AND SFCTA.TA005=1
            GROUP BY [CMSMW].[MW002],[SFCTA].[TA004],[SFCTA].[TA009]
            ORDER BY [SFCTA].[TA004], [SFCTA].[TA009]
        ";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => $sql]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head,true);
            // $sql = "SELECT
            //         ROW_NUMBER () OVER (
            //             PARTITION BY processes.processes_id
            //             ORDER BY processes.processes_id, order_processes_detail.time
            //         ),
            //         processes.processes_id, processes.processes_name, order_processes_detail.amount, order_processes_detail.time
            //     FROM order_processes_detail
            //     LEFT JOIN order_processes ON order_processes.order_processes_id = order_processes_detail.order_processes_id
            //     LEFT JOIN processes ON processes.processes_id = order_processes.processes_id
            //     WHERE order_processes_detail.status = 'ready'
            //         AND :minus_seven_date::DATE <= order_processes_detail.time AND order_processes_detail.time <= :request_date::DATE
            // ";
            // $stmt = $this->container->db->prepare($sql);
            // $stmt->bindValue(':request_date', $params['request_date']);
            // $stmt->bindValue(':minus_seven_date', $params['minus_seven_date']);
            // $stmt->execute();
            // $result = $stmt->fetchAll();
            return $result;
        // }
        return ["status" => "failed", "message" => "date欄位不存在"];
    }
    public function doLinearRegression($x, $y)
    {
        // calculate number points
        $n = count($x);

        // ensure both arrays of points are the same size
        if ($n != count($y)) {
            trigger_error("doLinearRegression(): Number of elements in coordinate arrays do not match.", E_USER_ERROR);
        }

        // calculate sums
        $x_sum = array_sum($x);
        $y_sum = array_sum($y);

        $xx_sum = 0;
        $xy_sum = 0;

        for ($i = 0; $i < $n; $i++) {
            $xy_sum += ($x[$i] * $y[$i]);
            $xx_sum += ($x[$i] * $x[$i]);
        }

        // calculate slope
        $slope = (($n * $xx_sum) - ($x_sum * $x_sum))==0?0:(($n * $xy_sum) - ($x_sum * $y_sum)) / (($n * $xx_sum) - ($x_sum * $x_sum));

        // calculate intercept
        $intercept = ($y_sum - ($slope * $x_sum)) / $n;

        // return result
        return array("slope" => $slope, "intercept" => $intercept);
    }
    public function groupingLRResponse($params, $lr_result, $sql_row,$count)
    {
        $group = [
            "processes_id" => $sql_row["processes_id"],
            "processes_name" => $sql_row["processes_name"],
            "predict_sum" => 0
        ];
        $i = 1;
        $end = $count;
        while ($i <= $end) {
            $predict_date = date("Y-m-d", strtotime($params["request_date"] . " + {$i} days"));  /* request_date + 1~7 */
            if (date("N", strtotime($predict_date)) < ($count-1)) {  /* weekend check */
                $predict_x = $count + $i;  /* x = row_number */
                $predict_y = round($predict_x * $lr_result["slope"] + $lr_result["intercept"]);  /* y = amount */
                $group["predict_sum"] += $predict_y;
            } else {
                $end++;
            }
            $i++;
        }
        return $group;
    }
    public function iterateNextWeekPredictAmount($params, $last_week_amount,$count)
    {
        $result = [];
        $last_x = [];
        $last_y = [];
        $idx = 1;
        foreach ($last_week_amount as $key => $value) {
            array_push($last_x, $value["row_number"]);
            array_push($last_y, $value["amount"]);
            if ($idx % $count === 0) {  /* 7 days per processes_id */
                $lr_result = $this->doLinearRegression($last_x, $last_y);
                array_push($result, $this->groupingLRResponse($params, $lr_result, $value,$count));
                $last_x = [];  /* reinit */
                $last_y = [];
                $idx = 0;
            }
            $idx++;
        }
        return $result;
    }
    public function mergePredictAmount($data){
        $result = $data['today'];
        foreach ($result as $index => $row) {
            foreach ($data['week'] as $key => $value) {
                if($value['processes_id']===$row['processes_id']){
                    $result[$index]['predict_sum_week'] = $value['predict_sum'];
                    break;
                }
            }
            foreach ($data['five_days'] as $key => $value) {
                if($value['processes_id']===$row['processes_id']){
                    $result[$index]['predict_sum_5days'] = $value['predict_sum'];
                    break;
                }
            }
            foreach ($data['three_days'] as $key => $value) {
                if($value['processes_id']===$row['processes_id']){
                    $result[$index]['predict_sum_3days'] = $value['predict_sum'];
                    break;
                }
            }
        }
        return $result;
    }
    public function getMachineAreaPosition($data)
    {
        $values=[
            "machines_area_id" => 0
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = ($data[$key]);
            }
        }
        $sql = "SELECT machines_area.machines_area_id,machines_area.machines_area_name,machine.*,position.*,point.point
        FROM public.machine
        LEFT JOIN rfid.machines_area ON machines_area.machines_area_id = machine.machines_area_id
        LEFT JOIN public.position ON position.position_id = machine.position_id
        LEFT JOIN (
            SELECT position_id, 
            '[' || STRING_AGG (
            '[\"' || x::TEXT || '\",\"' || y::TEXT || '\"]',
                ','
                ORDER BY point_id
            ) || ']' point
            FROM public.point
            GROUP BY position_id
        ) AS point ON point.position_id = position.position_id
        WHERE machines_area.machines_area_id =:machines_area_id ; 
        ";
        $stmt = $this->container->db->prepare($sql);
        if($stmt->execute($values)){
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row_key => $row) {
                foreach ($row as $key => $value) {
                    if ($this->isJson($value)) {
                        $result[$row_key][$key] = json_decode($value, true);
                    }
                }
            }
            $result = [
                "status" => "success",
                "data" => $result
            ];
        }else{
            $result = [
                "status" => "failed"
            ];
        }
            
        return $result;
    }
    public function getMachinePosition($data)
    {
        if (array_key_exists('floor_id', $data)) {
            $sql = "SELECT machine.machine_id, machine.floor_id, machine.position_id, x, y, machine_code, canvas_width, canvas_height, machines_area.machines_area_id
                FROM machine
                LEFT JOIN rfid.machines_area ON machine.machines_area_id = machines_area.machines_area_id
                LEFT JOIN position ON machine.position_id = position.position_id
                LEFT JOIN point ON position.position_id = point.position_id
                WHERE machine.floor_id = :floor_id
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':floor_id', $data['floor_id']);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [
                "status" => "success",
                "data" => $result
            ];
            return $result;
        }
    }
    public function postMachinePosition($data)
    {
        if (array_key_exists('floor_id', $data)) {
            $sql = "INSERT INTO position(
                    canvas_width, canvas_height)
                    VALUES (:canvas_width, :canvas_height)
                    RETURNING position_id;
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':canvas_width', $data['canvas_width']);
            $stmt->bindValue(':canvas_height', $data['canvas_height']);
            $stmt->execute();
            $position_id = $stmt->fetch(PDO::FETCH_ASSOC);
            foreach ($data['point_list'] as $point) {
                $sql = "INSERT INTO point(
                    x, y, position_id)
                    VALUES (:x, :y, :position_id);
                ";
                $stmt = $this->container->db->prepare($sql);
                $stmt->bindValue(':x', $point['px']);
                $stmt->bindValue(':y', $point['py']);
                $stmt->bindValue(':position_id', $position_id['position_id']);
                $stmt->execute();
            }
            $sql = "INSERT INTO machine(
                floor_id, position_id)
                VALUES (:floor_id, :position_id)
                RETURNING machine_id;
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':floor_id', $data['floor_id']);
            $stmt->bindValue(':position_id', $position_id['position_id']);
            if ($stmt->execute()) {
                $result = [
                    "status" => "success",
                    "data" => $stmt->fetch()['machine_id']
                ];
            } else {
                $result = [
                    "status" => "fail"
                ];
            }
            return $result;
        }
    }
    public function updateFloorImage($data)
    {
        $sql = "UPDATE floor
            SET file_name=:file_name, file_client_name=:file_client_name
            WHERE floor_id = :floor_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':file_name', $data['file_name'], PDO::PARAM_STR);
        $stmt->bindParam(':file_client_name', $data['file_client_name'], PDO::PARAM_STR);
        $stmt->bindParam(':floor_id', $data['floor_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return [
                "status" => "success",
                "file_client_name" => $data['file_client_name']
            ];
        } else {
            return [
                "status" => "failed"
            ];
        }
    }
    public function getFloorImage($data)
    {
        $sql = "SELECT file_name
            FROM floor
            WHERE floor_id = :floor_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':floor_id', $data['floor_id'], PDO::PARAM_INT);
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($files as $file) {
            return $this->container->upload_directory . '/' . $file['file_name'];
        }
    }
    public function getFloor()
    {
        $sql = "SELECT floor_id, floor_name, file_client_name
                FROM floor
                WHERE floor_name IS NOT NULL
                ORDER BY floor_name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function insertNewFloor($params)
    {
        $sql = "INSERT INTO floor (floor_name)
                VALUES (NULL)
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "fail"];
        }
    }
    public function readNewestFloor()
    {
        $sql = "SELECT floor_id, floor_name
                FROM floor
                ORDER BY floor_id DESC LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
    public function updateFloor($body)
    {
        $sql = "UPDATE floor
                SET floor_name = :floor_name
                WHERE floor_id = :floor_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':floor_name', $body['floor_name'], PDO::PARAM_STR);
        $stmt->bindParam(':floor_id', $body['floor_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return [
                "status" => "failure",
                "error_info" => $stmt->errorInfo()
            ];
        }
    }
    public function deleteFloor($body)
    {
        $sql = "DELETE FROM floor
                WHERE floor_id = :floor_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':floor_id', $body['floor_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return [
                "status" => "failure",
                "error_info" => $stmt->errorInfo()
            ];
        }
    }
    public function deleteMachinePosition($data)
    {
        $sql = "DELETE FROM machine
            WHERE machine_id = :machine_id
            RETURNING position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':machine_id', $data['machine_id'], PDO::PARAM_INT);
        $stmt->execute();
        $position_id = $stmt->fetch();
        $sql = "DELETE FROM point
            WHERE position_id = :position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':position_id', $position_id['position_id'], PDO::PARAM_INT);
        $stmt->execute();
        $sql = "DELETE FROM position
            WHERE position_id = :position_id;
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':position_id', $position_id['position_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return ["status" => "fail"];
        }
    }
    public function updateMachinePosition($data)
    {
        $values = [
            'machines_area_id' => null
        ];
        foreach (array_keys($values) as $key) {
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $stmt_string="";
        $stmt_array=[];
        if(!is_null($values['machines_area_id'])){
            $stmt_string=",machines_area_id=:machines_area_id";
            $stmt_array = $values;
        }
        $stmt_array['machine_code']=$data['machine_code'];
        $stmt_array['machine_name']=$data['machine_code'];
        $stmt_array['machine_id']=$data['machine_id'];
        $sql = "UPDATE machine
            SET machine_name = :machine_name, machine_code = :machine_code
                {$stmt_string}
            WHERE machine_id = :machine_id
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($stmt_array)) {
            return ["status" => "fail"];
        }
        // $sql = "INSERT INTO public.rfid_antenna_machine(machine_id, antenna_id, status)
        //     VALUES (:machine_id, :antenna_id, :status)
        //     ON CONFLICT (antenna_id)
        //     DO UPDATE SET machine_id=EXCLUDED.machine_id,status=EXCLUDED.status;
        // ";
        // $stmt = $this->db->prepare($sql);
        // $stmt->bindParam(':machine_id', $data['machine_id'], PDO::PARAM_INT);
        // $stmt->bindParam(':antenna_id', $data['antenna_id'], PDO::PARAM_INT);
        // $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        // if ($stmt->execute()) {
        //     return ["status" => "success"];
        // } else {
        //     return ["status" => "fail"];
        // }
    }

    public function getOrderProcessesOuter($data)
    {
        $values = [
            'date_begin' => date("Ymd"),
            'date_end' => date("Ymd"),
            'size' => 10,
            'cur_page' => 1,
            'keyword' => '',
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $keyword = $values['keyword'];
        $length = $values['cur_page']*$values['size'];
        $start = $length-$values['size'];
        unset($values['keyword']);
        unset($values['cur_page']);
        unset($values['size']);
        $keyword_array = ['MOCTA.TA001'=>'keyword_MOCTA_TA001','MOCTA.TA002'=>'keyword_MOCTA_TA002','COPTD.TD001'=>'keyword_COPTD_TD001','COPTD.TD002'=>'keyword_COPTD_TD002','COPTD.TD003'=>'keyword_COPTD_TD003'];
        $keyword_string = implode(' OR ',array_map(function($key,$value){
            return " $key LIKE '%'+:{$value}+'%' ";
        },array_keys($keyword_array),array_values($keyword_array)));
        $keyword_string =  ' AND ( ' . $keyword_string . ')';
        foreach ($keyword_array as $array) {
            $values[$array] = $keyword;
        }
        $with = "WITH dt as (
            SELECT MOCTA.TA001 \"製令單別\",MOCTA.TA001,MOCTA.TA002 \"製令單號\",MOCTA.TA002,COPTD.TD013 \"訂單交期\",COPTD.TD008 \"訂單數量\",MOCTA.TA009 \"預計生產完成日\",COPTD.TD201 order_name,COPTD.TD201 \"客戶圖號\", COPTD.TD001, COPTD.TD002, COPTD.TD003,ROW_NUMBER() OVER (ORDER BY MOCTA.TA002 ASC) \"key\"
            FROM [MIL].[dbo].[MOCTA]
                RIGHT OUTER JOIN MIL.dbo.COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
            WHERE MOCTA.TA009 BETWEEN :date_begin AND :date_end
                AND
            (
                MOCTA.TA001  Is Null
                OR
                MOCTA.TA001  NOT IN  ( '5202','5205','5198','5199','5207','5203','5204'  )
            )
            {$keyword_string}
        )";

        $sql = $with."SELECT *,dt.TA001+'_'+dt.TA002 \"key\"
            FROM (
                SELECT TOP {$length} *
                FROM dt
            )dt
            WHERE \"key\" > {$start}
            ORDER BY dt.TA002 ASC
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute($values)) return ["status"=>"failure"];
        $result = [];
        $result["data"]=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $result['data'] = json_encode($result['data']);

        $sql = "WITH countTB AS (
           SELECT order_name,COUNT(*)
            FROM (
                SELECT  replace(CONCAT(order_processes.fk->'TA001' ::TEXT ,'-', order_processes.fk->'TA002'),'\"','') AS order_name,rfid_tag_id 
                FROM public.order_processes
                LEFT JOIN order_processes_tag ON order_processes.order_processes_id = order_processes_tag.order_processes_id

                GROUP BY replace(CONCAT(order_processes.fk->'TA001' ::TEXT ,'-', order_processes.fk->'TA002'),'\"','') , rfid_tag_id
            ) AS tmpdb
            GROUP BY order_name
            ),order_processes_outer AS (
                  SELECT *
                FROM json_to_recordset('{$result['data']}') as order_processes_outer(\"製令單別\" text,\"製令單號\" text,\"TA001\" text,\"TA002\" text,\"訂單交期\" text,\"訂單數量\" text,\"預計生產完成日\" text,\"客戶圖號\" text,\"order_name\" text,\"TD001\" text,\"TD002\" text,\"TD003\" text,\"key\" text)

            )
            SELECT order_processes_outer.*,COALESCE(order_processes.rfid_tag_time::TEXT,'-') AS \"列印時間\", COALESCE(order_processes.count,0) AS \"列印次數\", COALESCE(\"user\".name,'-') AS \"列印人\"
            FROM order_processes_outer 
            LEFT JOIN (
                SELECT order_processes.order_processes_id, replace(CONCAT(order_processes.fk->'TA001') ,'\"','') AS \"TA001\",replace(CONCAT(order_processes.fk->'TA002') ,'\"','') AS \"TA002\",rfid_tag.user_id,rfid_tag.rfid_tag_time,countTB.count,
		ROW_NUMBER() OVER(PARTITION BY replace(CONCAT(order_processes.fk->'TA001' ::TEXT ,'-', order_processes.fk->'TA002'),'\"','') ORDER BY rfid_tag_time DESC) AS rownum
                FROM public.order_processes
                LEFT JOIN order_processes_tag ON order_processes.order_processes_id = order_processes_tag.order_processes_id
                LEFT JOIN rfid_tag ON rfid_tag.rfid_tag_id = order_processes_tag.rfid_tag_id
                LEFT JOIN countTB ON RTRIM(LTRIM(countTB.order_name)) = RTRIM(LTRIM(replace(CONCAT(order_processes.fk->'TA001' ::TEXT ,'-', order_processes.fk->'TA002'),'\"','')))
                ORDER BY rfid_tag_time DESC,order_processes.order_processes_id ASC
            )AS order_processes ON RTRIM(LTRIM(order_processes_outer.\"TA001\")) = RTRIM(LTRIM(order_processes.\"TA001\"))  
                AND RTRIM(LTRIM(order_processes_outer.\"TA002\"))  = RTRIM(LTRIM(order_processes.\"TA002\"))  AND  order_processes.rownum = 1
            LEFT JOIN system.\"user\" ON \"user\".id = order_processes.user_id
        ";

       
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute())  return ["status"=>"failure"];
        $result["data"]=$stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = $with."SELECT COUNT(*)
            FROM dt
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute($values)) return ["status"=>"failure"];
        $result["total"] = $stmt->fetchColumn(0);
        return $result;
    }
    public function getOrderProcessesLabel($data)
    {
        $values = [
            'TA001' => '',
            'TA002' => '',
            'order_name' => ''
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $result = [
            "cLine1" => "{$data['TA001']}-{$data['TA002']}",
            "cLine2" => "{$data['order_name']}"
        ];
        return $result;
    }
    public function insertRFIDTag($data)
    {
        $values = [
            "rfid_tag" => '',
            "user_id" => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $sql = "INSERT INTO rfid_tag (rfid_tag,user_id)
            VALUES (:rfid_tag,:user_id)
            RETURNING rfid_tag_id;
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchColumn(0);
    }
    public function insertOrderProcessesTag($data)
    {
        $values = [
            "rfid_tag_id" => 0,
            "order_processes_id" => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
    }
    public function getOrderProcessesFK($data)
    {
        $result = [];
        foreach ($data['RFID'] as $row) {
            $values = [
                "TA001" => '',
                "TA002" => '',
                "TA003" => '',
                "TA004" => ''
            ];
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $row)) {
                    $values[$key] = $row[$key];
                }
            }
            $jsonfk = [
                "fk" => json_encode($values)
            ];

            $sql = "SELECT order_processes_id
                FROM order_processes
                WHERE fk = :fk
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->execute($jsonfk);
            if ($stmt->rowCount() != 0) {
                $result[] = $stmt->fetchColumn(0);
            } else {
                //order_id
                // $sql = "SELECT order_id FROM public.\"order\"
                //     WHERE fk->>'coptd_td001' = :coptd_td001 AND fk->>'coptd_td002' = :coptd_td002 AND fk->>'coptd_td003' = :coptd_td003
                //     ORDER BY order_id ASC;
                // ";
                // $stmt = $this->container->db->prepare($sql);
                // $stmt->bindValue(':coptd_td001', $values['TD001'], PDO::PARAM_STR);
                // $stmt->bindValue(':coptd_td002', $values['TD002'], PDO::PARAM_STR);
                // $stmt->bindValue(':coptd_td003', $values['TD003'], PDO::PARAM_STR);
                // $stmt->execute();
                // $jsonb_fk['order_id'] = intval($stmt->fetchColumn(0));
                //processes_id
                // $sql = "SELECT processes_id FROM public.\"processes_fk\"
                //     WHERE processes_fk_key = 'CMSMW.MW001' AND TRIM(processes_fk_value) = :processes_fk_value
                //     ORDER BY processes_id ASC;
                // ";
                // $stmt = $this->container->db->prepare($sql);
                // $stmt->bindValue(':processes_fk_value', $values['TA004'], PDO::PARAM_STR);
                // $stmt->execute();
                // $jsonb_fk['processes_id'] = intval($stmt->fetchColumn(0));
                //order_processes_index
                // $jsonfk['order_processes_index'] = intval($values['TA003']);
                // $jsonfk['amount'] = $row['TA015'];
                // $jsonfk['amount'] = intval($jsonfk['amount']);
                // $jsonfk['order_id'] = 0;
                // $jsonfk['order_id'] = 0;
                $sql = "INSERT INTO public.order_processes (fk)
                    VALUES (:fk)
                    RETURNING order_processes_id;
                ";
                $stmt = $this->container->db->prepare($sql);
                $stmt->execute($jsonfk);
                $result[] = $stmt->fetchColumn(0);
            }
        }
        return $result;
    }
    public function insertOrderProcessesRFIDTag($data)
    {
        $values = [
            "order_processes_id" => [],
            "rfid_tag_id" => 0
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $stmt_string = implode(
            ",",
            array_map(function($index){
                return "(:order_processes_id_{$index},:rfid_tag_id_{$index})";
            },range(0,count($values['order_processes_id'])-1))
        );
        $stmt_array = [];

        foreach($values['order_processes_id'] as $index => $order_processes){
            $stmt_array["order_processes_id_{$index}"] = $order_processes;
            $stmt_array["rfid_tag_id_{$index}"] = $values['rfid_tag_id'];
        }

        $sql = "INSERT INTO public.order_processes_tag(order_processes_id, rfid_tag_id)
            VALUES {$stmt_string}
            RETURNING order_processes_tag_id;
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->execute($stmt_array);
        return [
            "status" => "success"
        ];
    }
    public function createAddress()
    {
        $sql = "INSERT INTO rfid_address (port)
                VALUES (5084)
                RETURNING id address_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'address_id' => $stmt->fetchColumn()
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function readAddress()
    {
        $sql = "SELECT id address_id, \"tAddress\" address, port
                FROM rfid_address
                ORDER BY id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function updateAddress($body)
    {
        /* $body = "
        [
            {
              \"id\":1, //address_id
              \"tAddress\":\"192.0.0.2\",
              \"port\":5084,
              \"antenna\":[
                {
                  \"iAntennaID\":1, //第幾支天線
                  \"machine_id\":1,
                  \"status\":\"running\"
                }
              ]
            }
          ]
        "; */
        foreach ($body as $body_index => $data) {
            $sql = "UPDATE rfid_address
                    SET \"tAddress\" = :tAddress, port = :port
                    WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tAddress', $data['tAddress'], PDO::PARAM_STR);
            $stmt->bindValue(':port', $data['port'], PDO::PARAM_STR);
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                return [
                    'status' => 'failure',
                    'error_info' => $stmt->errorInfo(),
                    'message' => [
                        'address' => $body_index
                    ]
                ];
            }
            if(array_key_exists("antenna",$data)){
                $antennas = $data['antenna'];
                foreach ($antennas as $antenna_index => $antenna) {
                    $values = [
                        "address_id"=>$data['id'],
                        "iAntennaID" => 0,
                        "TransmitPowerIndex" => null
                    ];
                    foreach ($values as $key => $value) {
                        if(array_key_exists($key,$antenna))
                            $values[$key] = $antenna[$key];
                    }
                    /*  */
                    $sql = "INSERT INTO rfid_antenna(address_id,\"iAntennaID\",\"TransmitPowerIndex\")
                        VALUES(:address_id,:iAntennaID,:TransmitPowerIndex)
                        ON CONFLICT(address_id,\"iAntennaID\")
                        DO UPDATE SET \"iAntennaID\" = rfid_antenna.\"iAntennaID\",address_id = rfid_antenna.address_id, \"TransmitPowerIndex\" = :TransmitPowerIndex
                        RETURNING id
                    ";
                    $stmt = $this->db->prepare($sql);
                    if(!$stmt->execute($values)){
                        return [
                            'status' => 'failure',
                            'error_info' => $stmt->errorInfo(),
                            'message' => [
                                'antenna' => $antenna_index,
                                'address' => $body_index
                            ]
                        ];
                    }
                    /*  */
                    unset($antenna['antenna_id']);
                    $values = [
                        'antenna_id' => $stmt->fetchColumn(0),
                        'status' => null,
                        'machine_id' => 0
                    ];
                    foreach ($values as $key => $value) {
                        if(array_key_exists($key,$antenna))
                            $values[$key] = $antenna[$key];
                    }
                    $sql = "INSERT INTO public.rfid_antenna_machine(machine_id,antenna_id,status)
                        VALUES(:machine_id,:antenna_id,:status)
                        ON CONFLICT(antenna_id)
                        DO UPDATE SET \"machine_id\" = EXCLUDED.\"machine_id\",status = EXCLUDED.status
                    ";
                    $stmt = $this->db->prepare($sql);
                    if(!$stmt->execute($values)){
                        return [
                            'status' => 'failure',
                            'error_info' => $stmt->errorInfo(),
                            'message' => [
                                'antenna' => $antenna_index,
                                'address' => $body_index
                            ]
                        ];
                    }
                }
            }
        }
    }
    public function deleteAddress($body)
    {
        $sql = "DELETE FROM rfid_antenna
                WHERE address_id = :address_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':address_id', $body['address_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            $sql = "DELETE FROM rfid_address
                    WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $body['address_id'], PDO::PARAM_INT);
            if ($stmt->execute()) {
                return ['status' => 'success',];
            } else {
                return [
                    'status' => 'failure',
                    'error_info' => $stmt->errorInfo()
                ];
            }
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function createAntenna($body)
    {
        $sql = "INSERT INTO rfid_antenna (address_id)
                VALUES (:address_id)
                RETURNING id antenna_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':address_id', $body['address_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'antenna_id' => $stmt->fetchColumn()
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function readAntenna($params)
    {
        $sql = "SELECT id antenna_id, \"iAntennaID\" antenna_code,\"TransmitPowerIndex\"
                FROM rfid_antenna
                WHERE address_id = :address_id
                ORDER BY id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':address_id', $params['address_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function updateAntenna($body)
    {
        $sql = "UPDATE rfid_antenna
                SET \"iAntennaID\" = :iAntennaID
                WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':iAntennaID', $body['antenna_code'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $body['antenna_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['status' => 'success',];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function deleteAntenna($body)
    {
        $sql = "DELETE FROM rfid_antenna
                WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $body['antenna_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['status' => 'success',];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function createOrderProcessesReferenceKey($key, $value)
    {
        $sql = "INSERT INTO reference_key(outer_key, local_key)
                VALUES (:outer_key, :local_key)
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->bindValue(':outer_key', $key, PDO::PARAM_STR);
        $stmt->bindValue(':local_key', $value, PDO::PARAM_STR);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function readOrderProcessesReferenceKey($params)
    {
        $sql = "SELECT outer_key, local_key, meaning
                FROM reference_key
                WHERE local_key = :local_key
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->bindValue(':local_key', $params, PDO::PARAM_STR);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function readOrderProcessesReferenceValue($params)
    {
        $sql = "SELECT order_processes_value AS local_val, \"{$params['outer_key']}\" outer_val
                FROM order_processes_fk,
                    JSONB_TO_RECORDSET(order_processes_fk.order_processes_jsonb) AS outer_val(\"{$params['outer_key']}\" TEXT)
                WHERE order_processes_key = :local_key
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->bindValue(':local_key', $params['local_key'], PDO::PARAM_STR);
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo(),
            ];
        }
    }
    public function readAddressDetail($params)
    {
        $sql = "SELECT rfid_address.id address_id, \"tAddress\" address,
                COALESCE(rfid_antenna_machine.antennas,'[]') antennas
            FROM rfid_address
            LEFT JOIN (
                SELECT rfid_antenna.address_id,
                    JSON_AGG(JSON_BUILD_OBJECT('TransmitPowerIndex',\"TransmitPowerIndex\",'status',rfid_antenna_machine.status,'antenna_id', rfid_antenna.id, 'antenna_code', rfid_antenna.\"iAntennaID\", 'machine_id', machine.machine_id, 'machine_code', machine.machine_code))antennas
                FROM rfid_antenna_machine
                LEFT JOIN rfid_antenna ON rfid_antenna_machine.antenna_id = rfid_antenna.id
                LEFT JOIN machine ON machine.machine_id = rfid_antenna_machine.machine_id
                GROUP BY rfid_antenna.address_id
            )rfid_antenna_machine ON rfid_antenna_machine.address_id = rfid_address.id
            ORDER BY rfid_address.id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row_key => $row) {
                foreach ($row as $key => $value) {
                    if ($this->isJson($value)) {
                        $result[$row_key][$key] = json_decode($value, true);
                    }
                }
            }
            return $result;
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    public function insertRFIDAntennaMachine($data)
    {
        $sql = "INSERT INTO public.rfid_antenna_machine(
                antenna_id, machine_id)
                VALUES (:antenna_id, :machine_id)
                ON CONFLICT (machine_id)
                DO UPDATE SET antenna_id = :antenna_id;
        ";
        $stmt = $this->container->db->prepare($sql);
        $stmt->bindValue(':antenna_id', $data['antenna_id'], PDO::PARAM_INT);
        $stmt->bindValue(':machine_id', $data['machine_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ["status" => "success"];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function createAntennaMachine($body)
    {
        $sql = "INSERT INTO rfid_antenna_machine (machine_id, antenna_id)
                VALUES (:machine_id, :antenna_id)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':machine_id', $body['machine_id'], PDO::PARAM_INT);
        $stmt->bindValue(':antenna_id', $body['antenna_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['status' => 'success',];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function readAntennaMachine($params)
    {
        $sql = "SELECT rfid_antenna_machine.machine_id, rfid_antenna_machine.antenna_id, rfid_antenna.address_id
                FROM rfid_antenna_machine
                LEFT JOIN rfid_antenna ON rfid_antenna.id = rfid_antenna_machine.antenna_id
                LEFT JOIN machine ON machine.machine_id = rfid_antenna_machine.machine_id
                WHERE machine.floor_id = :floor_id
                ORDER BY machine_id, antenna_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':floor_id', $params['floor_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function syncAddress($data)
    {
        $local = $this->readAddress();
        for ($i = 0; $i < count($data); $i++) {
            $index = $i + 1;
            $target = "Reader{$index}";
            if (isset($local[$i])) {
                $sql = "UPDATE rfid_address
                        SET \"tAddress\" = :tAddress, port = :port
                        WHERE id = :id
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':tAddress', $data[$target]['cIP'], PDO::PARAM_STR);
                $stmt->bindValue(':port', $data[$target]['iPort'], PDO::PARAM_STR);
                $stmt->bindValue(':id', $local[$i]['address_id'], PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $sql = "INSERT INTO rfid_address (\"tAddress\", port)
                        VALUES (:tAddress, :port)
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':tAddress', $data[$target]['cIP'], PDO::PARAM_STR);
                $stmt->bindValue(':port', $data[$target]['iPort'], PDO::PARAM_STR);
                $stmt->execute();
            }
        }
    }
    public function readMachineStatus($params)
    {
        $sql = "SELECT machine_id, status
                FROM machine
                ORDER BY machine_id
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function getRFIDOuter($data)
    {
        // MOCTA.TA001 +'-'+MOCTA.TA002 "(製令單別)+(製令單號)",MOCTA.TA009 "預計生產完成日",COPTD.TD201 "客戶圖號"
        $sql = "SELECT TOP 1000 MOCTA.TA001
                ,MOCTA.TA001,MOCTA.TA002,SFCTA.TA003,SFCTA.TA004
                ,MOCTA.TA015
                
                FROM [MIL].[dbo].[MOCTA]
                    RIGHT OUTER JOIN MIL.dbo.COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                    LEFT JOIN MIL.dbo.SFCTA ON MOCTA.TA001 = SFCTA.TA001 AND MOCTA.TA002 = SFCTA.TA002 
                WHERE
                (
                    MOCTA.TA001  =  {$data['TA001']}  
                    AND
                    MOCTA.TA002  =  {$data['TA002']}
                )
                ORDER BY MOCTA.TA003 ASC
        ";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => $sql]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        return $result;
    }
    public function readCurrentOriginMaterialSupplier($params)
    {
        $sql = "SELECT origin_material_supplier.supplier_id, supplier.supplier_name,
                    origin_material_handler.receiver_user_id, \"user\".name receiver_name,
                    origin_material_supplier.origin_material_id
                /* require select more in origin_material_supplier */
                FROM origin_material_supplier
                LEFT JOIN origin_material_handler
                    ON origin_material_handler.origin_material_supplier_id = origin_material_supplier.origin_material_supplier_id
                LEFT JOIN supplier ON supplier.supplier_id = origin_material_supplier.supplier_id
                LEFT JOIN system.\"user\" ON \"user\".id = origin_material_handler.receiver_user_id
                /* LEFT JOIN purchase_order ? */
                WHERE origin_material_handler.receiver_user_id IN (
                    SELECT DISTINCT ON (user_id) user_id
                    FROM system.user_rfid_tag
                    LEFT JOIN system.user_modal ON user_modal.uid = user_rfid_tag.user_id
                    LEFT JOIN setting.module ON module.id = user_modal.module_id
                    WHERE rfid_tag IN
                    (
                        SELECT \"cTagID\"
                        FROM public.\"RFID_TABLE_Log\"
                        WHERE \"iAntennaID\" = 3
                            AND ('2022-12-31 00:00:00'::TIMESTAMP - INTERVAL '5 SECONDS') <= \"dTime\" AND \"dTime\" <= '2022-12-31 00:00:00'::TIMESTAMP  /* change timestamp to now(), fixed last 5 secs */
                        GROUP BY \"cTagID\"
                        ORDER BY \"dTime\" DESC, \"cTagID\" ASC
                    )
                    /* OR origin_material_supplier.supplier_id IN ({same as receiver}) */
                        AND user_modal.module_id IN (12, 13)  /* fixed (供應商, 收貨人) */
                    ORDER BY user_id, rfid_tag
                )
        ";
        $stmt = $this->db->prepare($sql);
        // $stmt->bindValue(':antenna_id', $params['antenna_id'], PDO::PARAM_INT);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function get_line_machine_outer($data){
        $sql = "SELECT RTRIM(LTRIM(CMSMD.MD001)) [line_code],RTRIM(LTRIM(CMSMD.MD002)) [line_name],
            Stuff((
                SELECT RTRIM(LTRIM(t.MX001)) [machine_code],RTRIM(LTRIM(t.MX003)) [machine_name]
                FROM [MIL].[dbo].CMSMX t
                WHERE t.MX002 = CMSMD.MD001
                FOR XML PATH),1,0,''
            )[machines]
            FROM [MIL].[dbo].CMSMD
            WHERE CMSMD.MD001 NOT IN ('C', 'E')
            GROUP BY CMSMD.MD001,CMSMD.MD002
        ";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => $sql]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        if (isset($result)) {
            foreach ($result as $key_result => $value) {
                $tmpvalue = $value['machines'];
                $tmpArrs = [];
                $xml = simplexml_load_string("<a>$tmpvalue</a>");
                if ($tmpvalue == "") {
                    $result[$key_result]['machines'] = $tmpArrs;
                    goto Endquotation;
                }
                foreach ($xml as $t) {
                    $tmpArr = [];
                    foreach ($t as $a => $b) {
                        $tmpArr[$a] = '';
                        foreach ((array)$b as $c => $d) {
                            $tmpArr[$a] = $d;
                        }
                    }
                    $tmpArrs[] = $tmpArr;
                }
                $result[$key_result]['machines'] = $tmpArrs;
                Endquotation:
            }
        }
        return $result;
    }
    public function get_machines_outer($data){
        $sql = "SELECT MX001 [machine_code],MX003 [machine_name],CMSMD.MD001 [line_code],CMSMD.MD002 [line_name]
        -- , MW001 [processes_id]
                FROM [MIL].[dbo].CMSMX
                LEFT JOIN [MIL].[dbo].CMSMD ON CMSMX.MX002 = CMSMD.MD001
                -- LEFT JOIN [MIL].[dbo].CMSMW ON CMSMW.MW005 = CMSMD.MD001
        ";
        // $stmt = $this->db_sqlsrv->prepare($sql);
        // if ($stmt->execute()) {
        //     $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //     return $result;
        // } else {
        //     return [
        //         'status' => 'failure',
        //         'error_info' => $stmt->errorInfo()
        //     ];
        // }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://172.25.25.33/sql");
        curl_setopt($ch, CURLOPT_POST, 1);
        // In real life you should use something like:
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                ['sql' => $sql]
            )
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $result = json_decode($head, true);
        return $result;
    }
    public function get_machine_process($data){
        $sql = "SELECT MX001 [machine_code],MX003 [machine_name], MW001 [processes_id]
                FROM [MIL].[dbo].CMSMX
                LEFT JOIN [MIL].[dbo].CMSMD ON CMSMX.MX002 = CMSMD.MD001
                LEFT JOIN [MIL].[dbo].CMSMW ON CMSMW.MW005 = CMSMD.MD001
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
        // $sql = "SELECT MX001 \"machine_code\",MX003 \"machine_name\",
        //         Stuff((
        //             SELECT MW001 \"processes_id\",MW002 \"processes_name\"
        //             FROM [MIL].[dbo].CMSMW
        //             WHERE CMSMW.MW005 = CMSMD.MD001
        //             FOR XML PATH),1,0,''
        //         )\"processes\"
        //     FROM [MIL].[dbo].CMSMX
        //     LEFT JOIN [MIL].[dbo].CMSMD ON CMSMX.MX002 = CMSMD.MD001
        // ";
        // $stmt = $this->db_sqlsrv->prepare($sql);
        // if ($stmt->execute()) {
        //     $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //     if (isset($result)) {
        //         foreach ($result as $key_result => $value) {
        //             $tmpvalue = $value['processes'];
        //             $tmpArrs = [];
        //             $xml = simplexml_load_string("<a>$tmpvalue</a>");
        //             if ($tmpvalue == "") {
        //                 $result[$key_result]['processes'] = $tmpArrs;
        //                 goto Endquotation;
        //             }
        //             foreach ($xml as $t) {
        //                 $tmpArr = [];
        //                 foreach ($t as $a => $b) {
        //                     $tmpArr[$a] = '';
        //                     foreach ((array)$b as $c => $d) {
        //                         $tmpArr[$a] = $d;
        //                     }
        //                 }
        //                 $tmpArrs[] = $tmpArr;
        //             }
        //             $result[$key_result]['processes'] = $tmpArrs;
        //             Endquotation:
        //         }
        //     }
        //     return $result;
        // } else {
        //     return [
        //         'status' => 'failure',
        //         'error_info' => $stmt->errorInfo()
        //     ];
        // }
    }
    public function get_rfid_order_processes_machine_area($data){
        $sql = "SELECT COPTD.TD001,COPTD.TD002,COPTD.TD003
        FROM [MIL].[dbo].[MOCTA]
        LEFT JOIN MIL.dbo.COPTD dt ON (dt.TD001=MOCTA.TA026 and dt.TD002=MOCTA.TA027 and dt.TD003=MOCTA.TA028)
        WHERE MOCTA.TA011 NOT IN ( 'Y' )
            AND
            (
                MOCTA.TA001  Is Null
                OR
                MOCTA.TA001  NOT IN  ( '5202','5205','5198','5199','5207','5203','5204'  )
            )
        ";
        $values = [
            "floor_id" => 0
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $sql = "SELECT machines_area_id, machines_area_name, machines_area_floor_serial
            FROM rfid.machines_area
            WHERE floor_id=:floor_id
            ORDER BY machines_area_floor_serial
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)) return["status"=>"failed"];
        
    }
    public function get_rfid_order_processes_machine($data){
        $machines = $this->get_machines_outer($data);
        $machines = json_encode($machines);
        $sort = "ORDER BY dt.\"TA001\" ASC, dt.\"TA002\" ASC";

        $sql = "WITH \"New\" AS (
                SELECT \"cTagID\",\"cReaderName\", \"cIP\", \"iAntennaID\",\"dTime\" \"New\",LEAD(\"dTime\",1) OVER (
                    PARTITION BY \"cTagID\",\"cReaderName\", \"cIP\", \"iAntennaID\"
                    ORDER BY \"cTagID\",\"dTime\"
                ) \"Next\"
                FROM public.\"RFID_TABLE_Log\"
                WHERE \"cTagEvent\" = 'New'
                ORDER BY \"cTagID\",\"dTime\"
            )
            
            SELECT *,ROW_NUMBER() OVER() \"key\"
            FROM(
                SELECT dt.\"TA001\" || '-' || dt.\"TA002\" number,STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.machine_name END,',') current_machine,STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.machine_code END,',') current_machine_code,STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.status END,',') current_machine_status,
                    JSON_AGG(JSON_BUILD_OBJECT('machine_code',dt.machine_code,'machine_name',dt.machine_name,'in_time',to_char(dt.\"New\"::timestamp, 'YYYY-MM-DD HH24:MI:SS'::text),'out_time',to_char(dt.\"Gone\"::timestamp, 'YYYY-MM-DD HH24:MI:SS'::text),'status',dt.status))history
                FROM(
                    SELECT ROW_NUMBER() OVER (PARTITION BY rfid_tag.\"TA001\", rfid_tag.\"TA002\" ORDER BY \"dt\".\"New\" DESC ),
                        rfid_tag.\"TA001\", rfid_tag.\"TA002\",machine_outer.machine_code,machine_outer.machine_name,dt.\"New\",dt.\"Gone\",rfid_antenna_machine.status
                    FROM (
                        SELECT fk->>'TA001' \"TA001\",fk->>'TA002' \"TA002\",rfid_tag.rfid_tag
                        FROM public.order_processes
                        LEFT JOIN order_processes_tag ON order_processes.order_processes_id = order_processes_tag.order_processes_id
                        LEFT JOIN rfid_tag ON rfid_tag.rfid_tag_id = order_processes_tag.rfid_tag_id
                        GROUP BY fk->>'TA001',fk->>'TA002',rfid_tag.rfid_tag
                    )rfid_tag
                    LEFT jOIN (
                        SELECT \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\".\"New\",\"New\".\"Next\",\"Back\",\"Gone\"
                        FROM(
                            SELECT \"cTagID\",\"cReaderName\", \"cIP\", \"iAntennaID\",\"New\",\"Next\"
                            FROM \"New\"
                        )\"New\"
                        LEFT JOIN(
                            SELECT \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\",MAX(\"dTime\") \"Back\"
                            FROM \"New\"
                            LEFT JOIN public.\"RFID_TABLE_Log\" ON \"New\".\"cTagID\" = \"RFID_TABLE_Log\".\"cTagID\" AND \"New\".\"cIP\" = \"RFID_TABLE_Log\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"RFID_TABLE_Log\".\"iAntennaID\"
                                AND \"New\".\"New\" < \"RFID_TABLE_Log\".\"dTime\" AND COALESCE(\"New\".\"Next\",NOW()) > \"RFID_TABLE_Log\".\"dTime\" 
                            WHERE \"cTagEvent\" = 'Back'
                            GROUP BY \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\"
                        )\"Back\" ON \"New\".\"cTagID\" = \"Back\".\"cTagID\" AND \"New\".\"cIP\" = \"Back\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"Back\".\"iAntennaID\" AND \"New\".\"New\" = \"Back\".\"New\" AND COALESCE(\"New\".\"Next\"::text,'null') = COALESCE(\"Back\".\"Next\" ::text,'null')
                        LEFT JOIN(
                            SELECT \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\",MAX(\"dTime\") \"Gone\"
                            FROM \"New\"
                            LEFT JOIN public.\"RFID_TABLE_Log\" ON \"New\".\"cTagID\" = \"RFID_TABLE_Log\".\"cTagID\" AND \"New\".\"cIP\" = \"RFID_TABLE_Log\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"RFID_TABLE_Log\".\"iAntennaID\"
                                AND \"New\".\"New\" < \"RFID_TABLE_Log\".\"dTime\" AND COALESCE(\"New\".\"Next\",NOW()) > \"RFID_TABLE_Log\".\"dTime\" 
                            WHERE \"cTagEvent\" = 'Gone'
                            GROUP BY \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\"
                        )\"Gone\" ON \"New\".\"cTagID\" = \"Gone\".\"cTagID\" AND \"New\".\"cIP\" = \"Gone\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"Gone\".\"iAntennaID\" AND \"New\".\"New\" = \"Gone\".\"New\" AND COALESCE(\"New\".\"Next\"::text,'null') = COALESCE(\"Gone\".\"Next\" ::text,'null')
                    )dt ON dt.\"cTagID\" = rfid_tag.rfid_tag
                    INNER JOIN rfid_address ON rfid_address.\"tAddress\" = dt.\"cIP\"
                    INNER JOIN rfid_antenna ON rfid_antenna.address_id = rfid_address.id AND dt.\"iAntennaID\" = rfid_antenna.\"iAntennaID\"
                    INNER JOIN rfid_antenna_machine ON rfid_antenna_machine.antenna_id = rfid_antenna.id
                    INNER JOIN machine ON machine.machine_id = rfid_antenna_machine.machine_id
                    LEFT JOIN json_to_recordset('$machines')
                        as machine_outer(machine_code text,machine_name text,line_code text,line_name text) ON trim(machine.machine_code) = trim(machine_outer.machine_code)
                )dt
                GROUP BY dt.\"TA001\",dt.\"TA002\",dt.machine_name,dt.machine_code
                {$sort}
            )dt
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $index => $row) {
                foreach ($row as $key => $value) {
                    if($this->isJson($value)) $result[$index][$key] = json_decode($value);
                }
            }
            return $result;
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function get_machine_set($data){
        $machines = $this->get_machines_outer($data);
        $machines = json_encode($machines);
        $sql = "SELECT machine.machine_id,COALESCE(TRIM(machine_outer.machine_code),machine.machine_code) machine_code,TRIM(machine_outer.machine_name)machine_name
                FROM machine
                LEFT JOIN json_to_recordset('$machines')
                as machine_outer(machine_code text,machine_name text,line_code text,line_name text) ON trim(machine.machine_code) = trim(machine_outer.machine_code)
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }        
    }
    public function get_rfid_order_processes($data){
        foreach ($data as $key => $value) {
            in_array($key,['process_id','line_id','machine_id'])&&$data[$key]=urldecode($value);
            $this->isJson($value)&&$data[$key]=json_decode($value,true);
        }
        $machines = $this->get_machines_outer($data);
        $machines = json_encode($machines);
        $data['processes_id'] = $this->get_processes_filter($data);
        
        $result = $this->get_order_processes_outer_detail($data);
        $orderprocesses = json_encode($result['data']);
        $values = [
            "order"=>[]
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $order = [
            "name" => null,
            "sort" => null
        ];
        foreach ($order as $key => $value) {
            array_key_exists($key,$values["order"])&&$order[$key]=$values["order"][$key];
        }
        $sort = " ORDER BY dt.\"TA001\" || '-' || dt.\"TA002\" ";
        if(!is_null($order['name'])&&!is_null($order['sort'])){
            switch ($order['name']) {
                case 'order_serial':
                    $sort = " ORDER BY dt.\"TD001\" || '-' || dt.\"TD002\" || '-' || dt.\"TD003\" ";
                    break;
                case 'order_processes_serial':
                    $sort = " ORDER BY dt.\"TA001\" || '-' || dt.\"TA002\" ";
                    break;
                case 'date':
                    $sort = " ORDER BY dt.\"TA009\" ";
                    break;
            }
            if(strtolower($order['sort'])==='descend') $sort.=" desc ";
        }
        $sql = "WITH \"New\" AS (
                SELECT \"cTagID\",\"cReaderName\", \"cIP\", \"iAntennaID\",\"dTime\" \"New\",LEAD(\"dTime\",1) OVER (
                    PARTITION BY \"cTagID\",\"cReaderName\", \"cIP\", \"iAntennaID\"
                    ORDER BY \"cTagID\",\"dTime\"
                ) \"Next\"
                FROM public.\"RFID_TABLE_Log\"
                WHERE \"cTagEvent\" = 'New'
                ORDER BY \"cTagID\",\"dTime\"
            )
            SELECT '/3DConvert/PhaseGallery/order_image/' || COALESCE(coptd_file.file_id,0) img,ROW_NUMBER() OVER() \"key\",*
            FROM(
                SELECT dt.\"TD001\",dt.\"TD002\",dt.\"TD003\",dt.order_amount,TRIM(dt.\"TD001\") || '-' || TRIM(dt.\"TD002\") || '-' || TRIM(dt.\"TD003\") order_serial,
                    dt.\"TA001\",dt.\"TA002\",dt.\"TA001\" || '-' || dt.\"TA002\" order_processes_serial,TO_CHAR(dt.\"TA009\"::timestamp, 'YYYY-MM-DD') date,dt.\"TA009\",
                    JSON_AGG(JSON_BUILD_OBJECT(
                        'order_processes_serial',dt.\"TA001\" || '-' || dt.\"TA002\",
                        'order_processes_order',dt.order_processes_order,
                        'preset_time',dt.preset_time,
                        'preset_in_time',order_processes_outer_detail_preset_in_time,
                        'preset_out_time',order_processes_outer_detail_preset_out_time,
                        'MW002',dt.\"MW002\",'line_name',dt.\"MD002\",'machine_code',dt.machine_code,
                        'machine_name',dt.machine_name,'in_time',to_char(dt.\"New\"::timestamp, 'YYYY-MM-DD HH24:MI:SS'::text),
                        'out_time',to_char(dt.\"Gone\"::timestamp, 'YYYY-MM-DD HH24:MI:SS'::text),
                        'status',dt.status) ORDER BY regexp_replace(dt.order_processes_order, '[^0-9]', '', 'g')::numeric ASC
                    ) history,
                    dt.preset_count,
                    STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.machine_name END,',') current_machine,
                    STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.machine_code END,',') current_machine_code,
                    STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.line_name END,',') current_line_name,
                    STRING_AGG(CASE WHEN dt.row_num = 1 AND dt.status IS NOT NULL THEN dt.\"MW002\" END,',') current_procsses,
                    STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.status END,',') current_machine_status,
                    (STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.\"Gone\"::text END,'')::timestamp)-(STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.\"New\"::text END,'')::timestamp) work_time,
                    STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.\"Gone\"::text END,'')::timestamp current_actual_out_time,
                    STRING_AGG(CASE WHEN dt.row_num = 1 THEN dt.\"New\"::text END,'')::timestamp current_actual_in_time,
                    dt.order_processes_outer_actual_in_time actual_in_time,
                    dt.order_processes_outer_actual_out_time actual_out_time,
                    dt.production_name,
                    dt.\"TA009\" preset_in_time,
                    dt.order_processes_outer_preset_time preset_out_time,
                    dt.\"TD004\" itemno,
                    dt.\"TD006\" spec,
                    dt.\"XB002\" material
                FROM (
                    SELECT order_processes_outer.\"TA012\" order_processes_outer_actual_in_time,order_processes_outer.\"TA014\" order_processes_outer_actual_out_time,order_processes_outer.preset_time order_processes_outer_preset_time,order_processes_outer.preset_count,order_processes_outer.production_name,order_processes_outer.\"TA009\",order_processes_outer.order->>'TD001' \"TD001\",order_processes_outer.order->>'TD002' \"TD002\",order_processes_outer.order->>'TD003' \"TD003\",order_processes_outer.order->>'TD008' order_amount,TRIM(order_processes_outer.order->>'TD001') || '-' || TRIM(order_processes_outer.order->>'TD002') || '-' || TRIM(order_processes_outer.order->>'TD003') order_serial,dt.current_machine,dt.current_machine_code,dt.current_machine_status,dt.work_time,dt.actual_in_time,dt.actual_out_time,dt.preset_in_time,dt.preset_out_time,
                        order_processes_outer_detail.\"TA001\",order_processes_outer_detail.\"TA002\",order_processes_outer_detail.order_processes_order,order_processes_outer_detail.\"MW002\",dt.line_name,dt.machine_code,dt.machine_name,dt.\"New\",dt.\"Gone\",dt.status,ROW_NUMBER() OVER (PARTITION BY order_processes_outer_detail.\"TA001\",order_processes_outer_detail.\"TA002\" ORDER BY CASE WHEN dt.status IS NOT NULL THEN 0 ELSE 1 END ASC,order_processes_outer_detail.order_processes_order DESC) row_num,order_processes_outer_detail.preset_time,
                        order_processes_outer_detail.\"MD002\",order_processes_outer.\"TD004\",order_processes_outer.\"TD006\",order_processes_outer.\"XB002\",order_processes_outer_detail.\"TA008\" order_processes_outer_detail_preset_in_time,order_processes_outer_detail.\"TA009\" order_processes_outer_detail_preset_out_time
                    FROM json_to_recordset('{$orderprocesses}')
                        AS order_processes_outer(\"key\" integer,\"TA001\" text,\"TA002\" text,\"TA009\" text,\"TA012\" text,\"TA014\" text,\"TD004\" text, \"TD006\" text, \"XB002\" text,preset_count text,production_name text,number text,preset_time text, \"order\" jsonb, \"order_processes\" jsonb)
                    LEFT JOIN jsonb_to_recordset(order_processes_outer.order_processes) order_processes_outer_detail(\"TA001\" text,\"TA002\" text,\"TA003\" text,\"TA006\" text,\"TA008\" text,\"TA009\" text,\"MD002\" text,preset_time text,\"order_processes_order\" text, \"MW002\" text) ON true
                    LEFT JOIN(
                        SELECT dt.\"TA001\", dt.\"TA002\",dt.\"TA001\" || '-' || dt.\"TA002\" number,
                            dt.line_code,dt.line_name,dt.machine_code,dt.machine_name,dt.\"New\",dt.\"Gone\",dt.status,
                            STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.machine_name END,',') current_machine,
                            STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.machine_code END,',') current_machine_code,
                            STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.status END,',') current_machine_status,
                            (STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.\"Gone\"::text END,'')::timestamp)-(STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.\"New\"::text END,'')::timestamp) work_time,
                            STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.\"Gone\"::text END,'')::timestamp actual_out_time,
                            STRING_AGG(CASE WHEN dt.row_number = 1 THEN dt.\"New\"::text END,'')::timestamp actual_in_time,
                            null preset_in_time,
                            null preset_out_time
                        FROM(
                            SELECT ROW_NUMBER() OVER (PARTITION BY rfid_tag.\"TA001\", rfid_tag.\"TA002\" ORDER BY \"dt\".\"New\" DESC ),
                                rfid_tag.\"TA001\", rfid_tag.\"TA002\",machine_outer.line_code,machine_outer.line_name,machine_outer.machine_code,machine_outer.machine_name,dt.\"New\",dt.\"Gone\",rfid_antenna_machine.status
                            FROM (
                                SELECT fk->>'TA001' \"TA001\",fk->>'TA002' \"TA002\",rfid_tag.rfid_tag
                                FROM public.order_processes
                                LEFT JOIN order_processes_tag ON order_processes.order_processes_id = order_processes_tag.order_processes_id
                                LEFT JOIN rfid_tag ON rfid_tag.rfid_tag_id = order_processes_tag.rfid_tag_id
                                GROUP BY fk->>'TA001',fk->>'TA002',rfid_tag.rfid_tag
                            )rfid_tag
                            LEFT jOIN (
                                SELECT \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\".\"New\",\"New\".\"Next\",\"Back\",\"Gone\"
                                FROM(
                                    SELECT \"cTagID\",\"cReaderName\", \"cIP\", \"iAntennaID\",\"New\",\"Next\"
                                    FROM \"New\"
                                )\"New\"
                                LEFT JOIN(
                                    SELECT \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\",MAX(\"dTime\") \"Back\"
                                    FROM \"New\"
                                    LEFT JOIN public.\"RFID_TABLE_Log\" ON \"New\".\"cTagID\" = \"RFID_TABLE_Log\".\"cTagID\" AND \"New\".\"cIP\" = \"RFID_TABLE_Log\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"RFID_TABLE_Log\".\"iAntennaID\"
                                        AND \"New\".\"New\" < \"RFID_TABLE_Log\".\"dTime\" AND COALESCE(\"New\".\"Next\",NOW()) > \"RFID_TABLE_Log\".\"dTime\" 
                                    WHERE \"cTagEvent\" = 'Back'
                                    GROUP BY \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\"
                                )\"Back\" ON \"New\".\"cTagID\" = \"Back\".\"cTagID\" AND \"New\".\"cIP\" = \"Back\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"Back\".\"iAntennaID\" AND \"New\".\"New\" = \"Back\".\"New\" AND COALESCE(\"New\".\"Next\"::text,'null') = COALESCE(\"Back\".\"Next\" ::text,'null')
                                LEFT JOIN(
                                    SELECT \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\",MAX(\"dTime\") \"Gone\"
                                    FROM \"New\"
                                    LEFT JOIN public.\"RFID_TABLE_Log\" ON \"New\".\"cTagID\" = \"RFID_TABLE_Log\".\"cTagID\" AND \"New\".\"cIP\" = \"RFID_TABLE_Log\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"RFID_TABLE_Log\".\"iAntennaID\"
                                        AND \"New\".\"New\" < \"RFID_TABLE_Log\".\"dTime\" AND COALESCE(\"New\".\"Next\",NOW()) > \"RFID_TABLE_Log\".\"dTime\" 
                                    WHERE \"cTagEvent\" = 'Gone'
                                    GROUP BY \"New\".\"cTagID\",\"New\".\"cReaderName\", \"New\".\"cIP\", \"New\".\"iAntennaID\",\"New\",\"Next\"
                                )\"Gone\" ON \"New\".\"cTagID\" = \"Gone\".\"cTagID\" AND \"New\".\"cIP\" = \"Gone\".\"cIP\"  AND \"New\".\"iAntennaID\" = \"Gone\".\"iAntennaID\" AND \"New\".\"New\" = \"Gone\".\"New\" AND COALESCE(\"New\".\"Next\"::text,'null') = COALESCE(\"Gone\".\"Next\" ::text,'null')
                            )dt ON dt.\"cTagID\" = rfid_tag.rfid_tag
                            INNER JOIN rfid_address ON rfid_address.\"tAddress\" = dt.\"cIP\"
                            INNER JOIN rfid_antenna ON rfid_antenna.address_id = rfid_address.id AND dt.\"iAntennaID\" = rfid_antenna.\"iAntennaID\"
                            INNER JOIN rfid_antenna_machine ON rfid_antenna_machine.antenna_id = rfid_antenna.id
                            INNER JOIN machine ON machine.machine_id = rfid_antenna_machine.machine_id
                            LEFT JOIN json_to_recordset('$machines')
                                as machine_outer(machine_code text,machine_name text,line_code text,line_name text) ON trim(machine.machine_code) = trim(machine_outer.machine_code)
                        )dt
                        GROUP BY dt.\"TA001\",dt.\"TA002\",dt.machine_name,dt.machine_code,dt.line_code,dt.line_name,dt.machine_code,dt.machine_name,dt.\"New\",dt.\"Gone\",dt.status
                        ORDER BY dt.\"TA001\" ASC, dt.\"TA002\" ASC
                    )dt ON TRIM(dt.\"TA001\") = TRIM(order_processes_outer_detail.\"TA001\") AND TRIM(dt.\"TA002\") = TRIM(order_processes_outer_detail.\"TA002\") AND TRIM(dt.line_code) = TRIM(order_processes_outer_detail.\"TA006\")
                )dt
                GROUP BY dt.\"TD001\",dt.\"TD002\",dt.\"TD003\",dt.order_amount,dt.\"TA001\",dt.\"TA002\",dt.\"TA009\",dt.production_name,dt.preset_count,dt.order_processes_outer_preset_time,dt.order_processes_outer_actual_in_time,dt.order_processes_outer_actual_out_time,dt.\"TD004\",dt.\"TD006\",dt.\"XB002\"
            )dt
            LEFT JOIN phasegallery.coptd_file ON TRIM(coptd_file.coptd_td001) = TRIM(dt.\"TD001\") AND TRIM(coptd_file.coptd_td002) = TRIM(dt.\"TD002\") AND TRIM(coptd_file.coptd_td003) = TRIM(dt.\"TD003\")
            {$sort}
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute([])) {
            return [
                "status" => "failure",
                "info"=>$stmt->errorInfo()
            ];
        }
        $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result['data'] as $row_key => $row) {
            foreach ($row as $key => $value) {
                if ($this->isJson($value)) {
                    $result['data'][$row_key][$key] = json_decode($value, true);
                }
            }
        }
        return $result;
    }
    public function get_order_processes_outer_detail($data){
        $values = [
            'keyword' => '',
            'date_begin' => date("Ymd"),
            'date_end' => date("Ymd"),
            'processes_id' => [],
            'cur_page'=>1,
            'size'=>10,
            'done'=>'false'
        ];
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $data)) {
                $values[$key] = $data[$key];
            }
        }
        $stmt_string = [];
        $stmt_array = [];
        $stmt_string['done'] = "AND MOCTA.TA011 NOT IN  ( 'Y'  )";
        if(strtolower($values['done'])==='true'){
            $stmt_string['done'] = "AND MOCTA.TA011 IN  ( 'Y'  )";
        }
        $stmt_string['processes_id'] = "";
        if(!empty($values['processes_id'])){
            $stmt_string['processes_id'] = implode(',',array_map(function($prefix,$postfix){return 'LTRIM(RTRIM(:'.$prefix.'_'.$postfix.'))';},array_fill(0,count($values['processes_id']),'processes_id'),array_keys($values['processes_id'])));
            $stmt_array = array_merge($stmt_array,array_reduce($values['processes_id'],function($all,$tmp){$all['processes_id_'.count($all)]=$tmp;return $all;},[]));
            $stmt_string['processes_id'] = "
                INNER JOIN (
                    SELECT [SFCTA].TA001,[SFCTA].TA002
                    FROM MIL.dbo.[SFCTA]
                    WHERE LTRIM(RTRIM(TA004)) IN ({$stmt_string['processes_id']})
                    GROUP BY [SFCTA].TA001,[SFCTA].TA002
                )[SFCTA] ON [MOCTA].TA001 = [SFCTA].[TA001] AND [MOCTA].TA002 = [SFCTA].[TA002] 
            ";
        }else{
            return [
                'data'=>[],
                'total' => 0
            ];
        }
        $stmt_array +=[
            "date_begin" => $values['date_begin'],
            "date_end" => $values['date_end'],
        ];
        if(!empty($values['keyword'])){
            $stmt_string['keyword'] = " AND ( RTRIM(LTRIM(MOCTA.TA001)) + '-' + RTRIM(LTRIM(MOCTA.TA002)) LIKE '%' + :keyword_1 + '%'  OR RTRIM(LTRIM(COPTD.TD001)) + '-' + RTRIM(LTRIM(COPTD.TD002)) + '-' + RTRIM(LTRIM(COPTD.TD003)) LIKE '%' + :keyword_2 + '%' ) ";
            $stmt_array['keyword_1'] = $values['keyword'];
            $stmt_array['keyword_2'] = $values['keyword'];
        }else{
            $stmt_string['keyword'] = "";
            unset($values['keyword']);
        }
        $sort = [
            "order"=>[]
        ];
        foreach ($sort as $key => $value) {
            array_key_exists($key,$data)&&$sort[$key]=$data[$key];
        }
        $order = [
            "name" => null,
            "sort" => null
        ];
        foreach ($order as $key => $value) {
            array_key_exists($key,$sort["order"])&&$order[$key]=$sort["order"][$key];
        }
        $sort = "ORDER BY \"with\".TA002 ";
        if(!is_null($order['name'])&&!is_null($order['sort'])){
            switch ($order['name']) {
                case 'order_serial':
                    $sort = " ORDER BY \"with\".\"TD001\" + '-' + \"with\".\"TD002\" + '-' + \"with\".\"TD003\" ";
                    break;
                case 'order_processes_serial':
                    $sort = " ORDER BY \"with\".\"TA001\" + '-' + \"with\".\"TA002\" ";
                    break;
                case 'date':
                    $sort = " ORDER BY \"with\".\"TA009\" ";
                    break;
            }
            if(strtolower($order['sort'])==='descend') $sort.=" desc ";
        }
        $with = "WITH \"with\" AS (
            SELECT *
            FROM(
                SELECT MOCTA.TA001,MOCTA.TA002,MOCTA.TA015 [preset_count],MOCTA.TA034 [production_name],MOCTA.TA001 +'-' +MOCTA.TA002 [number],
                    MOCTA.TA010 [preset_time],MOCTA.TA004 [delivery_date],MOCTA.TA009,MOCTA.TA012,MOCTA.TA014,dt.TD001,dt.TD002,dt.TD003,dt.TD004,dt.TD006,[CMSXB].XB002,
                    STUFF((
                        SELECT COPTD.TD001, COPTD.TD002, COPTD.TD003, COPTD.TD008
                        FROM MIL.dbo.COPTD
                        WHERE (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                            {$stmt_string['keyword']}
                    FOR XML PATH),1,0,''
                    )[order],
                    STUFF((
                        SELECT SFCTA.TA001,SFCTA.TA002,SFCTA.TA003,SFCTA.TA004,SFCTA.TA006,SFCTA.TA008,SFCTA.TA009,SFCTA.TA010,CMSMW.MW002,ROW_NUMBER() OVER (PARTITION BY SFCTA.TA001,SFCTA.TA002 ORDER BY SFCTA.TA003 ASC) order_processes_order,
                            DATEDIFF(DAY,CAST([TA008] AS DATETIME ),CAST([TA009] AS DATETIME )) preset_time,CMSMD.MD002
                        FROM [SFCTA]
                        LEFT JOIN CMSMW ON CMSMW.MW001 = SFCTA.TA004
                        LEFT JOIN [MIL].[dbo].CMSMD ON CMSMW.MW005 = CMSMD.MD001
                        WHERE SFCTA.TA001 = MOCTA.TA001 AND SFCTA.TA002 = MOCTA.TA002
                        ORDER BY SFCTA.TA001,SFCTA.TA002,SFCTA.TA003
                    FOR XML PATH),1,0,''
                    )[order_processes]
                FROM [MIL].[dbo].[MOCTA]
                LEFT JOIN MIL.dbo.COPTD dt ON (dt.TD001=MOCTA.TA026 and dt.TD002=MOCTA.TA027 and dt.TD003=MOCTA.TA028)
                LEFT JOIN [MIL].[dbo].[CMSXB] ON [CMSXB].XB001 = dt.TD205
                {$stmt_string['processes_id']}
                WHERE MOCTA.TA009 BETWEEN :date_begin AND :date_end
                    {$stmt_string['done']}
                    AND
                    (
                        MOCTA.TA001  Is Null
                        OR
                        MOCTA.TA001  NOT IN  ( '5202','5205','5198','5199','5207','5203','5204'  )
                    )
            )dt
            WHERE  [order] IS NOT NULL
        )";
        $length = $values['cur_page']*$values['size'];
        $start = $length-$values['size'];
        $sql = $with;
        $sql .= "SELECT *
            FROM(
                SELECT TOP {$length} *,ROW_NUMBER() OVER ({$sort}) \"key\"
                FROM \"with\"
            )dt
            WHERE \"key\" > {$start}
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if (!$stmt->execute($stmt_array)) {
            return [
                "status" => "failure",
            ];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $key_result => $value) {
            $tmpvalue = $value['order'];
            $tmpArrs = [];
            $xml = simplexml_load_string("<a>$tmpvalue</a>");
            if ($tmpvalue == "") {
                $result[$key_result]['order'] = $tmpArrs;
                goto Endquotation;
            }
            foreach ($xml as $t) {
                $tmpArr = [];
                foreach ($t as $a => $b) {
                    $tmpArr[$a] = '';
                    foreach ((array)$b as $c => $d) {
                        $tmpArr[$a] = $d;
                    }
                }
                $tmpArrs = $tmpArr;
            }
            $result[$key_result]['order'] = $tmpArrs;
            Endquotation:

            $tmpvalue = $value['order_processes'];
            $tmpArrs = [];
            $xml = simplexml_load_string("<a>$tmpvalue</a>");
            if ($tmpvalue == "") {
                $result[$key_result]['order_processes'] = $tmpArrs;
                goto Endquotation2;
            }
            foreach ($xml as $t) {
                $tmpArr = [];
                foreach ($t as $a => $b) {
                    $tmpArr[$a] = '';
                    foreach ((array)$b as $c => $d) {
                        $tmpArr[$a] = $d;
                    }
                }
                $tmpArrs[] = $tmpArr;
            }
            $result[$key_result]['order_processes'] = $tmpArrs;
            Endquotation2:
        }
        $result = [
            "data"=> $result
        ];

        $sql = $with;
        $sql .= "SELECT COUNT(*) count
            FROM \"with\"
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if (!$stmt->execute($stmt_array)) {
        }
        $result['total'] = current($stmt->fetchAll(PDO::FETCH_ASSOC))['count'];
        return $result;
    }
    public function get_rfid_status()
    {
        $sql = "SELECT rfid_status_name, rfid_status_color
                FROM setting.rfid_status
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function post_rfid_status($datas)
    {
        $values = [
            "rfid_status_name" => '',
            "rfid_status_color" => null,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$datas)){
                $values[$key] = $datas[$key];
            }
        }

        $sql = "INSERT INTO setting.rfid_status(rfid_status_name, rfid_status_color)
                VALUES (:rfid_status_name, :rfid_status_color)
                ON CONFLICT(rfid_status_name)
                DO UPDATE SET rfid_status_color = :rfid_status_color
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                'status' => 'success'
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function patch_rfid_status($datas)
    {
        $values = [
            "rfid_status_name" => '',
            "rfid_status_color" => null,
        ];

        foreach ($values as $key => $value) {
            if(array_key_exists($key,$datas)){
                $values[$key] = $datas[$key];
            }
        }

        $sql = "UPDATE setting.rfid_status
                SET rfid_status_color=:rfid_status_color
                WHERE rfid_status_name = :rfid_status_name
        ";
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                'status' => 'success'
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }
    public function delete_rfid_status($data)
    {
        $condition = "";
        $values = [];
        foreach ($data as $key => $value) {
            $condition .= ":name_{$key}, ";
            $values["name_{$key}"] = $value;
        }
        $condition = rtrim($condition, ", ");
        
        $sql = "DELETE FROM setting.rfid_status
                WHERE rfid_status_name IN ({$condition})
                ";
                
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($values)) {
            return [
                'status' => 'success'
            ];
        } else {
            return [
                'status' => 'failure',
                'error_info' => $stmt->errorInfo()
            ];
        }
    }


    public function get_lines($data){
        $values = [
            'line_id' => null
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=trim($data[$key]);
        }
        $stmt_string = '';
        $stmt_array = [];
        if(!is_null($values['line_id'])){
            $stmt_string = ' WHERE CMSMD.MD001 = :line_id ';
            $stmt_array = $values;
        }
        $sql = "SELECT TOP 1000 MD001 AS line_id , MD002 AS line_name
            FROM MIL.[dbo].CMSMD
            {$stmt_string}
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute($stmt_array)){
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
   
    public function get_machines($data){
        $values = [
            'line_id' => null
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=trim($data[$key]);
        }
        $stmt_string = '';
        $stmt_array = [];
        if(!is_null($values['line_id'])){
            $stmt_string = ' WHERE CMSMD.MD001 = :line_id ';
            $stmt_array = $values;
        }
        $sql = "SELECT TOP 1000 MX001 AS machine_id , MX003 AS machine_name
            FROM MIL.[dbo].CMSMX
            LEFT JOIN MIL.[dbo].CMSMD ON CMSMX.MX002 = CMSMD.MD001
            {$stmt_string}
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute($stmt_array)){
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function get_processes($data){
        $values = [
            'line_id' => null
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=trim($data[$key]);
        }
        $stmt_string = '';
        $stmt_array = [];
        if(!is_null($values['line_id'])){
            $stmt_string = ' AND CMSMD.MD001 = :line_id ';
            $stmt_array = $values;
        }
        $sql = "SELECT MW001 AS process_id , MW002 AS process_name
            FROM MIL.[dbo].CMSMW
            INNER JOIN [MIL].[dbo].CMSMD ON CMSMW.MW005 = CMSMD.MD001
            WHERE CMSMD.MD001 NOT IN ('C', 'E')
            {$stmt_string}
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute($stmt_array)){
            return [];
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function get_processes_filter($data){
        // var_dump( $data);

        $values = [
            "process_id" => null,
            "line_id" => null,
            "machine_id" => null,
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }


        

        
        // var_dump($machineArr);
        
        $stmt_string = '';
        $stmt_array = [];
        if(!is_null($values['line_id'])){
            $stmt_string .= ' AND RTRIM(LTRIM(CMSMD.MD001)) = RTRIM(LTRIM(:line_id)) ';
            $stmt_array['line_id'] = $values['line_id'];
        }
        if(!is_null($values['machine_id'])){
            $stmt_string .= ' AND RTRIM(LTRIM(CMSMX.MX001)) = RTRIM(LTRIM(:machine_id)) ';
            $stmt_array['machine_id'] = $values['machine_id'];
        }else if(array_key_exists('machines_area_id',$data) ){
            $machineArr = $this->getmachinebyArea(["machines_area_id"=>$data["machines_area_id"]]);
            $machineArr = explode(",", $machineArr);

            if(count($machineArr) > 0){
                $tmpStr = "(";
                foreach($machineArr AS $key => $value){
                    $tmpStr  .= " RTRIM(LTRIM(:machine_id_{$key})),";
                    $stmt_array["machine_id_{$key}"] = $value;
                }
                $tmpStr = substr_replace($tmpStr, ")", -1);

                $stmt_string .= ' AND RTRIM(LTRIM(CMSMX.MX001)) IN ';
                $stmt_string .=$tmpStr;
                
            }
                


        }
        if(!is_null($values['process_id'])){
            $stmt_string .= ' AND RTRIM(LTRIM(CMSMW.MW001)) = RTRIM(LTRIM(:process_id)) ';
            $stmt_array['process_id'] = $values['process_id'];
        }
        // var_dump($stmt_string);
        // var_dump($stmt_array);

        $sql = "SELECT CMSMW.MW001
            FROM [MIL].[dbo].CMSMW
            LEFT JOIN [MIL].[dbo].CMSMD ON CMSMW.MW005 = CMSMD.MD001
            LEFT JOIN [MIL].[dbo].CMSMX ON CMSMX.MX002 = CMSMD.MD001
            WHERE CMSMD.MD001 NOT IN ('C', 'E') 
            {$stmt_string}
            GROUP BY CMSMW.MW001
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute($stmt_array)){
            return [
                "status" => "failure"
            ];
        }
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    public function getmachinebyArea($data){
        $values  = [
            "machines_area_id" => 0,
        ];
        foreach ($values as $key => $value) {
            if(array_key_exists($key,$data)){
                $values[$key] = intval($data[$key]);
            }
        }

        $sql = "SELECT 
            machines_area_id,
            STRING_AGG (machine_code 
            ,
                ','
            ORDER BY
                machine_id
            )  machine
        
        FROM public.machine
        WHERE machines_area_id = :machines_area_id
        GROUP BY machines_area_id";
        $stmt = $this->db->prepare($sql);
        if( $stmt->execute($values)){
            $result = $stmt->fetchColumn(1);
            return $result;
        }

    }
    public function get_machines_area_floor($data){
/*  */
        $sql = "WITH [MOCTA] AS (
                SELECT *
                FROM [MIL].[dbo].[MOCTA]
                WHERE MOCTA.TA011 NOT IN ( 'Y', 'y' )
                    AND
                    (
                        MOCTA.TA001  Is Null
                        OR
                        MOCTA.TA001  NOT IN  ( '5202','5205','5198','5199','5207','5203','5204'  )
                    )
                    AND
                        DATEDIFF(MONTH,CONVERT(NVARCHAR,LEFT(MOCTA.TA002,3)+1911)+RIGHT(LEFT(MOCTA.TA002,7),4),GETDATE())<6
            ),[SFCTA_DETAIL] AS (
                SELECT SFCTA.TA001,SFCTA.TA002,
                    MAX(CASE WHEN SFCTA.TA030!='' THEN SFCTA.TA003 END)TA030,
                    MAX(CASE WHEN SFCTA.TA031!='' THEN SFCTA.TA003 END)TA031,
                    MAX(SFCTA.TA003)TA003
                FROM [MIL].[dbo].[SFCTA]
                INNER JOIN [MOCTA] ON [SFCTA].[TA001] = [MOCTA].[TA001] AND [SFCTA].[TA002] = [MOCTA].[TA002]
                GROUP BY SFCTA.TA001,SFCTA.TA002
            ), SFCTA_STATUS AS (
                SELECT COPTD_SFCTA.TD001,COPTD_SFCTA.TD002,COPTD_SFCTA.TD003,COPTD_SFCTA.TA001,COPTD_SFCTA.TA002,SFCTA.TA004,COPTD_SFCTA.status
                FROM( 
                    SELECT COPTD.TD001,COPTD.TD002,COPTD.TD003,SFCTA.TA001,SFCTA.TA002,
                        CASE 
                            WHEN SFCTA.TA030>SFCTA.TA031
                            THEN SFCTA.TA030
                            WHEN SFCTA.TA030=SFCTA.TA031
                            THEN SFCTA.SFCTA_UNDO
                            ELSE
                                SFCTA.TA030
                        END TA003,
                        CASE 
                            WHEN SFCTA.TA030>SFCTA.TA031
                            THEN 'running'
                            WHEN SFCTA.TA030=SFCTA.TA031
                            THEN 'waiting'
                            ELSE
                                'running'
                        END status
                    FROM [MOCTA]
                    INNER JOIN MIL.dbo.COPTD ON (COPTD.TD001=MOCTA.TA026 and COPTD.TD002=MOCTA.TA027 and COPTD.TD003=MOCTA.TA028)
                    LEFT JOIN (
                        SELECT [SFCTA_DETAIL].TA001,[SFCTA_DETAIL].TA002,[SFCTA_DETAIL].TA030,[SFCTA_DETAIL].TA031,[SFCTA_DETAIL].TA003,MIN(SFCTA.TA003) SFCTA_UNDO
                        FROM[SFCTA_DETAIL]
                        LEFT JOIN (
                            SELECT SFCTA.TA001,SFCTA.TA002,SFCTA.TA003
                            FROM [MIL].[dbo].[SFCTA]
                            INNER JOIN [MOCTA] ON [SFCTA].[TA001] = [MOCTA].[TA001] AND [SFCTA].[TA002] = [MOCTA].[TA002]
                        )SFCTA ON [SFCTA_DETAIL].TA001 = SFCTA.TA001 AND [SFCTA_DETAIL].TA002 = SFCTA.TA002 AND SFCTA.TA003 > [SFCTA_DETAIL].TA031
                        GROUP BY [SFCTA_DETAIL].TA001,[SFCTA_DETAIL].TA002,[SFCTA_DETAIL].TA030,[SFCTA_DETAIL].TA031,[SFCTA_DETAIL].TA003
                    )[SFCTA] ON [SFCTA].[TA001] = [MOCTA].[TA001] AND [SFCTA].[TA002] = [MOCTA].[TA002]
                    WHERE ((SFCTA.TA003 != [SFCTA].[TA031])AND SFCTA.TA003 IS NOT NULL)
                )COPTD_SFCTA
                LEFT JOIN [MIL].[dbo].[SFCTA] ON SFCTA.TA001 = COPTD_SFCTA.TA001 AND SFCTA.TA002 = COPTD_SFCTA.TA002 AND SFCTA.TA003 = COPTD_SFCTA.TA003
            )
            SELECT *,
                STUFF((
                    SELECT MX001
                    FROM MIL.dbo.CMSMW
                    LEFT JOIN [MIL].[dbo].[CMSMX] ON LTRIM(RTRIM(CMSMW.MW005)) = LTRIM(RTRIM(CMSMX.MX002))
                    WHERE MW003 NOT LIKE '%停用%' AND LTRIM(RTRIM(CMSMW.MW001)) = LTRIM(RTRIM(SFCTA_STATUS.TA004))
                    FOR XML PATH),1,0,''
                )sfcta_machines
            FROM SFCTA_STATUS
            ORDER BY SFCTA_STATUS.TD002
        ";
        $stmt = $this->db_sqlsrv->prepare($sql);
        if(!$stmt->execute())
            return ["status"=>"failure"];
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $key_result => $value) {
            $tmpvalue = $value['sfcta_machines'];
            $tmpArrs = [];
            $xml = simplexml_load_string("<a>$tmpvalue</a>");
            if ($tmpvalue == "") {
                $result[$key_result]['sfcta_machines'] = $tmpArrs;
                goto Endquotation;
            }
            foreach ($xml as $t) {
                $tmpArr = [];
                foreach ($t as $a => $b) {
                    $tmpArr[$a] = '';
                    foreach ((array)$b as $c => $d) {
                        $tmpArr[$a] = $d;
                    }
                }
                count($tmpArr)!==0&&$tmpArrs[] = $tmpArr;
            }
            $result[$key_result]['sfcta_machines'] = $tmpArrs;
            Endquotation:
        }
        $SFCTA_STATUS = json_encode($result);
/*  */
        $values = [
            "floor_id"=>0
        ];
        foreach (array_keys($values) as $key) {
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $sql = "SELECT
            dt.machines_area_id,
            dt.machines_area_name,
            SUM( CASE WHEN dt.status = 'running' THEN dt.count END) \"processing\",
            SUM( CASE WHEN dt.status = 'waiting' THEN dt.count END) \"waiting\",
            0 incoming
            FROM(
                SELECT
                    machines_area.machines_area_id,
                    machines_area.machines_area_name,
                    tmp_status.status,
                    tmp_status.count
                FROM
                    rfid.machines_area
                LEFT JOIN public.machine ON machine.machines_area_id = machines_area.machines_area_id
                CROSS JOIN (
                    SELECT 'waiting' status,0 count
                    UNION ALL(
                        SELECT 'running' status,0 count
                    )
                    UNION ALL(
                        SELECT 'ready' status,0 count
                    )
                )tmp_status
                UNION ALL(
                    SELECT
                        machines_area.machines_area_id,
                        machines_area.machines_area_name,
                        sfcta_status.status,
                        SUM(sfcta_status.count) count
                    FROM
                        rfid.machines_area  
                    LEFT JOIN public.machine ON machine.machines_area_id = machines_area.machines_area_id
                    LEFT JOIN (
                        SELECT sfcta_status.status,sfcta_status.\"MX001\",COUNT(*) count
                        FROM(
                            SELECT sfcta_status.\"TD001\",sfcta_status.\"TD002\",sfcta_status.\"TD003\",sfcta_status.status,STRING_AGG(sfcta_status_detail.\"MX001\",',')\"MX001\"
                            FROM json_to_recordset('{$SFCTA_STATUS}')
                            as sfcta_status(\"TD001\" text,\"TD002\" text,\"TD003\" text,status text,sfcta_machines jsonb,\"TA004\" text)
                            LEFT JOIN jsonb_to_recordset(sfcta_status.sfcta_machines) as sfcta_status_detail(\"MX001\" text) ON TRUE
                            GROUP BY sfcta_status.\"TD001\",sfcta_status.\"TD002\",sfcta_status.\"TD003\",sfcta_status.status
                        )sfcta_status
                        GROUP BY sfcta_status.status,sfcta_status.\"MX001\"
                    )sfcta_status ON TRIM(sfcta_status.\"MX001\") LIKE '%' || TRIM(machine.machine_code) || '%'
                    WHERE
                        machines_area.floor_id=:floor_id AND sfcta_status.status IS NOT NULL
                    GROUP BY     machines_area.machines_area_id,machines_area.machines_area_name,sfcta_status.status
                )
            )dt
            GROUP BY 
                dt.machines_area_id,
                dt.machines_area_name
            ORDER BY
                dt.machines_area_id,
                dt.machines_area_name
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)){
            return ["status"=>"failure"];
        }
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    public function get_rfid_printer($data){
        $values = [];
        $printer_outer = $this->callApiByArray(["apiName" => "setting", "action" => "get", "labelPrinter" => true]);
        $printer_outer = json_encode($printer_outer);
        $sql = "INSERT INTO rfid.printer(printer_ip, printer_port,printer_name)
            SELECT printer_detail.\"cIP\",printer_detail.\"iPort\",printer.key
            FROM json_each('{$printer_outer}') printer
            LEFT JOIN json_to_record(printer.value) as printer_detail(\"cIP\" text,\"iPort\" text) ON TRUE
            ON CONFLICT (printer_ip, printer_port)
            DO UPDATE SET printer_ip = EXCLUDED.printer_ip
            RETURNING printer_id,printer_name
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)) return ["status"=>"failure"];
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_rfid_printer_outer($data){
        $values = [
            "printer_id" => 0
        ];
        foreach ($values as $key => $value) {
            array_key_exists($key,$data)&&$values[$key]=$data[$key];
        }
        $printer_outer = $this->callApiByArray(["apiName" => "setting", "action" => "get", "labelPrinter" => true]);
        $printer_outer = json_encode($printer_outer);
        $sql = "SELECT printer_outer.\"cPrinterName\"
            FROM rfid.printer
            LEFT JOIN (
                SELECT printer.key \"cPrinterName\",printer_detail.\"cIP\" printer_ip,printer_detail.\"iPort\" printer_port
                FROM json_each('{$printer_outer}') printer
                LEFT JOIN json_to_record(printer.value) as printer_detail(\"cIP\" text,\"iPort\" text) ON TRUE
            ) printer_outer ON printer.printer_ip = printer_outer.printer_ip AND printer.printer_port = printer_outer.printer_port
            WHERE printer.printer_id = :printer_id
        ";
        $stmt = $this->db->prepare($sql);
        if(!$stmt->execute($values)) return ["status"=>"failure"];
        return $stmt->fetchColumn(0);
    }
}
