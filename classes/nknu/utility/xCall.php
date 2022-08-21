<?php
namespace nknu\utility;
use nknu\base\xBase;
class xCall extends xBase {
    public string $cWebFormApiUrl = "http://192.168.2.43/app.aspx";
    public string $cWindowFormApiUrl = "http://192.168.2.43:8080";
    public function WebFormApi($cJsonData) { return $this->ServiceApi($this->cWebFormApiUrl, $cJsonData); }
    public function WindowFormApi($cJsonData) { return $this->ServiceApi($this->cWindowFormApiUrl, $cJsonData); }
    private function ServiceApi($cUrl, $cJsonData) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $cUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $cJsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $cResponse = curl_exec($ch);
        $iErrorNo = curl_errno($ch);
        if ($iErrorNo == 0) {
            $oResponse = xStatic::ToClass($cResponse);
        } else {
            $this->SetError(curl_error($ch));
        }
        curl_close($ch); if ($iErrorNo > 0) { return null; }

        if ($oResponse->bErrOn == true) {
            $this->SetError($oResponse->cMessage); return null;
        } else {
            $this->SetOK(); return $oResponse->oData;
        }
	}
}
