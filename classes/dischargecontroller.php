<?php

use \Slim\Views\PhpRenderer;

class DischargeController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function renderDischarge($request, $response, $args){
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/Discharge/discharge.html');
    }
    public function renderDischargeOrigin($request, $response, $args){
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/Discharge/discharge_origin.html');
    }
    public function getEDMRecord($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Discharge($this->container->db);
        $result = $business->getEDMRecord($data);
        foreach ($result as $key => $value) {
            if($key=='status'&&$value=='failed'){
                $response = $response->withStatus(500);
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
    public function zDataSave($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Discharge();
        $aRows = $business->zDataSave($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function zDataLoad($request, $response, $args){
        $data = $request->getQueryParams();
        $business = new Discharge();
        $aRows = $business->zDataLoad($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function zPictureLoad($request, $response, $args){
        $data = $request->getQueryParams();
        $db = $this->container->db;
        $business = new Discharge($db);
        $cImageBase64 = $business->zPictureLoad($data);
        $response = $business->MakeResponse($response, $cImageBase64);
        return $response;
    }
    public function sparkDataSave($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Discharge();
        $aRows = $business->sparkDataSave($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function sparkDataLoad($request, $response, $args){
        $data = $request->getQueryParams();
        $db = $this->container->db;
        $business = new Discharge($db);
        $aRows = $business->sparkDataLoad($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function sparkPictureLoad($request, $response, $args){
        $data = $request->getQueryParams();
        $db = $this->container->db;
        $business = new Discharge($db);
        $cImageBase64 = $business->sparkPictureLoad($data);
        $response = $business->MakeResponse($response, $cImageBase64);
        return $response;
    }
    public function audioDataLoad($request, $response, $args){
        $data = $request->getQueryParams();
        $db = $this->container->db;
        $business = new Discharge($db);
        $aRows = $business->audioDataLoad($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function audioDataSave($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Discharge();
        $aRows = $business->audioDataSave($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function vibrationDataLoad($request, $response, $args){
        $data = $request->getQueryParams();
        $db = $this->container->db;
        $business = new Discharge($db);
        $aRows = $business->vibrationDataLoad($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function vibrationDataSave($request, $response, $args){
        $data = $request->getParsedBody();
        $business = new Discharge();
        $aRows = $business->vibrationDataSave($data);
        $response = $business->MakeResponse($response, $aRows);
        return $response;
    }
    public function settingDataLoad($request, $response, $args){
        $db = $this->container->db;
        $business = new Discharge($db);
        $oSetting = $business->settingDataLoad();
        $response = $business->MakeResponse($response, $oSetting);
        return $response;
    }
    public function settingDataSave($request, $response, $args){
        $data = $request->getParsedBody();
        $db = $this->container->db;
        $business = new Discharge($db);
        $result = $business->settingDataSave($data);
        $response = $business->MakeResponse($response, $result);
        return $response;
    }
    public function statusDataLoad($request, $response, $args){
        $db = $this->container->db;
        $business = new Discharge($db);
        $oStatus = $business->statusDataLoad();
        $response = $business->MakeResponse($response, $oStatus);
        return $response;
    }
    public function callFireDetectionApi($request, $response, $args){
        $data = $request->getParsedBody();
        $db = $this->container->db;
        $business = new Discharge($db);
        $result = $business->callApiByArray($data);
        $response = $business->MakeResponse($response, $result);
        return $response;
    }
}