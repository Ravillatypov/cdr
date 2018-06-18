<?php
require_once("call.class.php");
class CallGroup {
    public $externalNumber = '';
    public $calls = array();
    public $status = false;

    /*
     * @param string $num
     * */
    public function setNumber($num){
        $this->externalNumber = $num;
    }

    /**
     * @param array $allcalls
     * @return bool
     */
    public function loadCalls(array $allcalls) {
        if ($this->externalNumber == '') return false;
        $loaded = array();
        foreach($allcalls as $call){
            if ($call->srcNumber == $this->externalNumber || $call->dstNumber == $this->externalNumber){
                if (!in_array($call->__toString(), $loaded)){
                    $this->calls[$call->__toString()] = $call;
                    $loaded[] = $call->__toString();
                    $this->status = $call->status;
                } else {
                    $this->calls[$call->__toString()] = $this->calls[$call->__toString()]->merge($call);
                    $this->status = $call->status;
                }
            }
        }
        return true;
    }
}