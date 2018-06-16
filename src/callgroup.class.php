<?php
require_once("call.class.php");
class CallGroup {
    public $externalNumber = '';
    public $calls = array();
    public $status = false;

    public function setNumber($num){
        $this->externalNumber = $num;
    }

    public function loadCalls($allcalls) {
        if (!isset($this->externalNumber)) return false;
        foreach($allcalls as $call){
            if ($call->srcNumber == $this->externalNumber || $call->dstNumber == $this->externalNumber){
                $this->calls[] = $call;
                $this->status = $call->status;
            }
        }
        return true;
    }
}