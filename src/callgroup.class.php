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
                    $this->calls[] = $call;
                    $loaded[] = $call->__toString();
                } else {
                    $this->merge($call);
                }
            }
        }
        $last = count($this->calls)-1;
        if ($last > -1) {
            $this->status = $this->calls[$last]->status;
        }
        return true;
    }
    /*
     * @param Call $newcall
     * */
    private function merge($newcall){
        foreach ($this->calls as $key => $gcall) {
            if ($gcall->__toStrings() == $newcall->__toString()){
                $this->calls[$key] = $newcall->merge($gcall);
                    $this->status = $newcall->status;
            }
        }
    }
}