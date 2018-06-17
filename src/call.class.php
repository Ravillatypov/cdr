<?php
/**
 * @package
*/
class Call {
    public $calldate = NULL;
    public $srcNumber = 0;
    public $dstNumber = 0;
    public $duration = 0;
    public $recordingfile = '';
    public $status = false;
    public $code = '';
    public $minlength = 0;

    public function __construct($code='8843')
    {
        $this->code = $code;
        $this->minlength = 11 - count($code);
    }

    /**
     * @param array
     * @return bool
    */
    public function loadFromArray($arr)
    {
        $this->calldate = (isset($arr['calldate'])) ? $arr['calldate'] : NULL;
        $this->duration = (isset($arr['billsec'])) ? $arr['billsec'] : '';
        $this->recordingfile = (isset($arr['recordingfile'])) ? $arr['recordingfile'] : '';
        $this->status = ($this->duration > 2) ? true : false;

        $this->srcNumber = (isset($arr['src'])) ? $this->__getNumber($arr['src']) : '';
        $this->dstNumber = (isset($arr['dst'])) ? $this->__getNumber($arr['dst']) : '';
    }

    private function __getNumber($text)
    {
        $length = count($text);
        if ($length < $this->minlength)
        {
            return $text;
        } elseif ($length > 9)
        {
            return "8" . substr($text, -10);
        } elseif ($length == $this->minlength)
        {
            return $this->code . $text;
        }
        return "";
    }
    public function getExternalNumber(){
        if (count($this->dstNumber) > count($this->srcNumber)) return $this->dstNumber;
        return$this->srcNumber;
    }
    public function  __toString()
    {
        // TODO: Implement __toString() method.
        return "date: $this->calldate  src: $this->srcNumber dst: $this->dstNumber billsec: $this->duration";
    }
}
