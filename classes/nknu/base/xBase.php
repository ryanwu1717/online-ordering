<?php
namespace nknu\base;

class xBase extends xEvent {
    public bool $bErrorOn = false;
    public string $cMessage = "";
    public function SetOK(string $cOKMessage = "") {
        $this->bErrorOn = false;
        $this->cMessage = $cOKMessage;
    }
    public function SetError(string $cErrorMessage) {
        $this->bErrorOn = true;
        $this->cMessage = $cErrorMessage;
        $this->Invoke("ErrorOn");
    }
    public function MakeResponse($response, $oData) {
        $result["status"] = $this->bErrorOn ? "failed" : "success";
        $result["message"] = $this->cMessage;
        if (!$this->bErrorOn) { $result["data"] = $oData; }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }
}
class xEvent {
    protected $aListens  =  array();

    public function On(string $cEventName, $mFunction, $once = false) {
        if(!is_callable($mFunction)) return false;
        $this->aListens[$cEventName][]   =  array('mFunction'=>$mFunction, 'bOnce'=>$once);
        return true;
    }

    public function One(string $cEventName, $mFunction) {
        return $this->On($cEventName, $mFunction, true);
    }

    public function Remove(string $cEventName, $index=null) {
        if(is_null($index))
            unset($this->aListens[$cEventName]);
        else
            unset($this->aListens[$cEventName][$index]);
    }

    public function Invoke() {
        if(!func_num_args()) return;
        $args = func_get_args();
        $cEventName = array_shift($args);
        if(!isset($this->aListens[$cEventName])) return false;
        foreach((array) $this->aListens[$cEventName] as $index=>$listen) {
            $mFunction = $listen['mFunction'];
            $listen['bOnce'] && $this->Remove($cEventName, $index);
            call_user_func_array($mFunction, $args);
        }
    }
}
?>