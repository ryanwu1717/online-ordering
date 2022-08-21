<?php

use \Psr\Container\ContainerInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use nknu\base\xBaseWithDbop;
//use nknu\database\xDatabaseOperator;

class Discharge extends xBaseWithDbop
{
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct()
    {
        parent::__construct();
        global $container;
        $this->container = $container;
        $this->db = $container->db;
    }
    public function getEDMRecord($data)
    {
        if (array_key_exists('time', $data)) {
            $sql = "SELECT [TimeTag]
                        ,[ASF]
                        ,[ADCPD]
                        ,[APDC]
                        ,[ADE]
                        ,[AIDT]
                        ,[AGV]
                        ,[OCR]
                        ,row_number
                FROM (
                    SELECT TOP 60
                        [TimeTag]
                        ,[ASF]
                        ,[ADCPD]
                        ,[APDC]
                        ,[ADE]
                        ,[AIDT]
                        ,[AGV]
                        ,[OCR]
                        ,ROW_NUMBER() OVER ( ORDER BY ABS(DATEDIFF(second,[TimeTag],:time))) row_number
                    FROM [EDMFeatureDB].[dbo].[EDMFeature]
                    WHERE ABS(DATEDIFF(second,[TimeTag],:time1)) < 30
                    ORDER BY ABS(DATEDIFF(second,[TimeTag],:time2)) ASC
                )AS result
                ORDER BY [TimeTag] DESC
            ";
            $stmt = $this->container->db->prepare($sql);
            $stmt->bindValue(':time', $data['time']);
            $stmt->bindValue(':time1', $data['time']);
            $stmt->bindValue(':time2', $data['time']);
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

    #region Z軸
    public function zDataLoad($data) {
        $iQueryType = -1;
        $bCheckInput = false;
        if (array_key_exists('dTime', $data)) {
            $iQueryType = 0;
            $dTime = $data["dTime"];
            //if (is_string($dTime)) { $dTime = new DateTime($dTime); }
            //$dTime_Start = $dTime->modify("-30 second")->format('Y-m-d H:i:s');
            //$dTime_End = $dTime->modify("+60 second")->format('Y-m-d H:i:s');
            $cSql = "
                SELECT \"iAutoIndex\", \"fValue\", \"dTime\", \"bDisconnect\"
	            FROM public.\"Z_TABLE_Log\"
                WHERE ABS(extract(epoch from (:dTime - \"dTime\" ))) < 50
                ORDER BY ABS(extract(epoch from (:dTime - \"dTime\" ))) ASC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('dTime_Start', $data) && array_key_exists('dTime_End', $data)) {
            $iQueryType = 1;
            $dTime_Start = $data["dTime_Start"];
            $dTime_End = $data["dTime_End"];
            $cSql = "
                SELECT \"iAutoIndex\", \"fValue\", \"dTime\", \"bDisconnect\"
	            FROM public.\"Z_TABLE_Log\"
                WHERE \"dTime\" BETWEEN :dTime_Start AND :dTime_End
                ORDER BY \"iAutoIndex\" DESC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('cIndexList', $data)) {
            $iQueryType = 2;
            $cIndexList = $data["cIndexList"];
            $cSql = "
                SELECT \"iAutoIndex\", \"fValue\", \"dTime\", \"bDisconnect\"
	            FROM public.\"Z_TABLE_Log\"
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
    public function zDataSave($aRows) {
        $aFields = ["fValue", "bDisconnect", "dTime"];
        $aData_Insert = $this->oDbop->MakeInsertData("Z_TABLE_Log", $aFields, $aRows); if ($this->bErrorOn) { return; }

        $this->oDbop->Connect("db"); if ($this->oDbop->bErrorOn) { return; }
        $result = $this->oDbop->RunSql($aData_Insert["cSql"], $aData_Insert["htSql"]); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();

        $this->SetOK(); return "";
    }
    public function zPictureLoad($data)
    {
        $bCheckInput = false;
        if (array_key_exists('dTime', $data)) {
            $dTime = $data["dTime"];
            $bCheckInput = true;
        }
        if (!$bCheckInput) { $this->SetError("傳入參數不正確"); return; }
        $cImageBase64 = $this->loadImage("Z", $dTime);
        return $cImageBase64;
    }
    #endregion

    #region 火花偵測
    public function sparkDataLoad($data)
    {
        $iQueryType = -1;
        $bCheckInput = false;
        if (array_key_exists('dTime', $data)) {
            $iQueryType = 0;
            $dTime = $data["dTime"];
            $cSql = "
                SELECT \"iAutoIndex\", \"iCenterX\", \"iCenterY\", \"iRadius\", \"iBright\", \"dTime\"
                FROM public.\"Discharge_TABLE_Log\"
                WHERE ABS(extract(epoch from (:dTime - \"dTime\" ))) < 30
                ORDER BY ABS(extract(epoch from (:dTime - \"dTime\" ))) ASC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('dTime_Start', $data) && array_key_exists('dTime_End', $data)) {
            $iQueryType = 1;
            $dTime_Start = $data["dTime_Start"];
            $dTime_End = $data["dTime_End"];
            $cSql = "
                SELECT \"iAutoIndex\", \"iCenterX\", \"iCenterY\", \"iRadius\", \"iBright\", \"dTime\"
                FROM public.\"Discharge_TABLE_Log\"
                WHERE \"dTime\" BETWEEN :dTime_Start AND :dTime_End
                ORDER BY \"dTime\" ASC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('cIndexList', $data)) {
            $iQueryType = 2;
            $cIndexList = $data["cIndexList"];
            $cSql = "
                SELECT \"iAutoIndex\", \"iCenterX\", \"iCenterY\", \"iRadius\", \"iBright\", \"dTime\"
                FROM public.\"Discharge_TABLE_Log\"
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
        $dt = $this->oDbop->SelectSql($cSql, $htSql); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();

        if ($iQueryType == 0) {
            $iCenterX_Zero = 65535;
            $iDuration = 0.0;
            foreach ($dt as $key=>$dr) {
                if (abs($dr['iCenterX'] - $iCenterX_Zero) < 20 && abs($dr['iCenterY'] - $iCenterY_Zero) < 20 && $dr['iRadius'] > 80) {
                    $dNext = new DateTime($dr['dTime']);
                    $iDuration += abs($dNext->getTimestamp() - $dTime_Zero->getTimestamp());
                    $dt[$key]["iDuration"] = $iDuration;
                } else {
                    $iDuration = 0;
                    $iCenterX_Zero = $dr['iCenterX'];
                    $iCenterY_Zero = $dr['iCenterY'];
                    $dTime_Zero = new DateTime($dr['dTime']);
                    $dt[$key]["iDuration"] = $iDuration;
                }
            }
        }
        $this->SetOK(); return $dt;
    }
    public function sparkDataSave($aRows) {
        $aFields = ["iCenterX", "iCenterY", "iRadius", "iBright", "dTime"];
        $aData_Insert = $this->oDbop->MakeInsertData("Discharge_TABLE_Log", $aFields, $aRows); if ($this->bErrorOn) { return; }

        $this->oDbop->Connect("db"); if ($this->oDbop->bErrorOn) { return; }
        $result = $this->oDbop->RunSql($aData_Insert["cSql"], $aData_Insert["htSql"]); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();

        $this->SetOK(); return "";
    }
    public function sparkPictureLoad($data)
    {
        $bCheckInput = false;
        if (array_key_exists('dTime', $data)) {
            $dTime = $data["dTime"];
            $bCheckInput = true;
        }
        if (!$bCheckInput) { $this->SetError("傳入參數不正確"); return; }
        $cImageBase64 = $this->loadImage("Discharge", $dTime);
        return $cImageBase64;
    }
    #endregion

    //Z軸 及 火花 的記錄圖片下載
    private function loadImage($cType, $dTime) {
        $cJsonData = json_encode(["cType" => $cType, "dTime" => $dTime]);
        $oCall = new nknu\utility\xCall();
        $cJsonResult = $oCall->WebFormApi($cJsonData);
        if ($oCall->bErrorOn) {
            $path = __DIR__ .'\..\public\img\noImage.png';
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $cPngContent = @file_get_contents($path, true);
            $cImageBase64 = 'data:image/' . $type . ';base64,' . nknu\utility\xStatic::ToBase64($cPngContent);
            return $cImageBase64;
        }
        return $cJsonResult;
	}

    #region 馬達麥克風
    public function audioDataLoad($data) {
        $iQueryType = -1;
        $bCheckInput = false;
        if (array_key_exists('dTime', $data)) {
            $iQueryType = 0;
            $dTime = $data["dTime"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cAudioData\", \"dStart\", \"dEnd\"
	            FROM public.\"AudioCapture_TABLE_Log\"
                WHERE ABS(extract(epoch from (:dTime - \"dStart\" ))) < 30
                ORDER BY ABS(extract(epoch from (:dTime - \"dStart\" ))) ASC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('dTime_Start', $data) && array_key_exists('dTime_End', $data)) {
            $iQueryType = 1;
            $dTime_Start = $data["dTime_Start"];
            $dTime_End = $data["dTime_End"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cAudioData\", \"dStart\", \"dEnd\"
	            FROM public.\"AudioCapture_TABLE_Log\"
                WHERE \"dStart\" BETWEEN :dTime_Start AND :dTime_End OR \"dEnd\" BETWEEN :dTime_Start AND :dTime_End
                ORDER BY \"iAutoIndex\" DESC
            ";
            $bCheckInput = true;
        }
        if (!$bCheckInput) { $this->SetError("傳入參數不正確"); return; }

        if ($iQueryType == 0) {
            $htSql = [ 'dTime'=>$dTime ];;
        } else {    //$iQueryType == 1
            $htSql = [ 'dTime_Start'=>$dTime_Start, 'dTime_End'=>$dTime_End ];;
        }
        $this->oDbop->Connect("db"); if ($this->oDbop->bErrorOn) { return; }
        $dt = $this->oDbop->SelectSql($cSql, $htSql); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();
        foreach($dt as $key=>$dr) {
            $dStart = new DateTime($dr["dStart"]);
            $dEnd = new DateTime($dr["dEnd"]);
            $cAudioData = $dr["cAudioData"];
            $aBytes = nknu\utility\xStatic::Base64ToString($cAudioData);

            $cPath = dirname(__FILE__,2)."\\public\\resource";
            $cFile = $dStart->format("YmdHis")."_".$dEnd->format("YmdHis").".mp3";
            if (!file_exists($cPath."\\".$cFile)) {
                $mp3File = file_put_contents($cPath."\\".$cFile, $aBytes);
            }
            $dt[$key]["cAudioData"] = "/resource"."/".$cFile;
		}

        $this->SetOK(); return $dt;
    }
    public function audioDataSave($aRows) {
        $aFields = ["cAudioData", "dStart", "dEnd"];
        $aData_Insert = $this->oDbop->MakeInsertData("AudioCapture_TABLE_Log", $aFields, $aRows); if ($this->bErrorOn) { return; }

        $this->oDbop->Connect("db"); if ($this->oDbop->bErrorOn) { return; }
        $result = $this->oDbop->RunSql($aData_Insert["cSql"], $aData_Insert["htSql"]); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();

        $this->SetOK(); return "";
    }
    #endregion

    #region 震動規
    public function vibrationDataLoad($data) {
        $iQueryType = -1;
        $bCheckInput = false;
        if (array_key_exists('dTime', $data)) {
            $iQueryType = 0;
            $dTime = $data["dTime"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cValue\", \"dTime\"
	            FROM public.\"Vibration_TABLE_Log\"
                WHERE ABS(extract(epoch from (:dTime - \"dTime\" ))) < 30
                ORDER BY ABS(extract(epoch from (:dTime - \"dTime\" ))) ASC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('dTime_Start', $data) && array_key_exists('dTime_End', $data)) {
            $iQueryType = 1;
            $dTime_Start = $data["dTime_Start"];
            $dTime_End = $data["dTime_End"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cValue\", \"dTime\"
	            FROM public.\"Vibration_TABLE_Log\"
                WHERE \"dTime\" BETWEEN :dTime_Start AND :dTime_End
                ORDER BY \"iAutoIndex\" DESC
            ";
            $bCheckInput = true;
        } else if (array_key_exists('cIndexList', $data)) {
            $iQueryType = 2;
            $cIndexList = $data["cIndexList"];
            $cSql = "
                SELECT \"iAutoIndex\", \"cValue\", \"dTime\"
	            FROM public.\"Vibration_TABLE_Log\"
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
        $dt = $this->oDbop->SelectSql($cSql, $htSql); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();

        $this->SetOK(); return $dt;
    }
    public function vibrationDataSave($aRows) {
        $aFields = ["cValue", "dTime"];
        $aData_Insert = $this->oDbop->MakeInsertData("Vibration_TABLE_Log", $aFields, $aRows); if ($this->bErrorOn) { return; }

        $this->oDbop->Connect("db"); if ($this->oDbop->bErrorOn) { return; }
        $result = $this->oDbop->RunSql($aData_Insert["cSql"], $aData_Insert["htSql"]); if ($this->oDbop->bErrorOn) { return; }
        $this->oDbop->Disconnect();

        $this->SetOK(); return "";
    }
    #endregion

    #region 辨視 APP 設定值
    public function settingDataLoad() {
        return $this->callApi("setting", "get");
	}
    public function settingDataSave($data) {
        $cSetting = json_encode($data);
        return $this->callApiByArray(["apiName"=>"setting", "action"=>"set", "setting"=>$cSetting]);
	}
    public function statusDataLoad() {
        return $this->callApi("status", "get");
	}
    public function callApi($apiName, $action) {
        return $this->callApiByArray(["apiName"=>$apiName, "action"=>$action]);
    }
    public function callApiByArray($aData) {
        $cJsonData = json_encode($aData);
        return $this->callApiByJson($cJsonData);
    }
    public function callApiByJson($cJsonData) {
        $oCall = new nknu\utility\xCall();
        $cJsonResult = $oCall->WindowFormApi($cJsonData); if ($oCall->bErrorOn) { $this->SetError($oCall->cMessage); return null; }
        $oCallBack = $cJsonResult == null ? true : nknu\utility\xStatic::ToClass($cJsonResult);
        $this->SetOK();
        return $oCallBack;
	}
    #endregion
}
