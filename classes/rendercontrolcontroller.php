<?php
class RenderControlController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    public function renderTextbox($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\Textbox();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderTextArea($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\TextArea();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderDropDownList($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\DropDownList();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderButton($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\Button();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderDate($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\Date();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderTime($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\Time();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderDateTime($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\DateTime();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderTimeRange($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\TimeRange();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderDateRange($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\DateRange();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
    public function renderDateTimeRange($request, $response, $args) {
        $aPostData = $request->getParsedBody();
        $oControl = new \nknu\controls\DateTimeRange();
        $oControl->setProperty($aPostData);
        $aData = $oControl->render();
        $response = $oControl->MakeResponse($response, $aData);
        return $response;
    }
}