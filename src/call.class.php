<?php
/**
 * @package
*/
class Call {
    /*
     * @var PDO
     * */
    public $db = NULL;
    public $calldate = NULL;
    public $srcNumber = 0;
    /**
     * dstlist array("dst1", "dst2")
    */
    public $dstlist = array();
    public $status = false;
    public $dstNumber = '';
    public $recordingfile = '';
    public $duration = '';
    public $did = '';
    public $code = '';
    public $minlength = 0;

    /** @param dbconn PDO */
    public function __construct($dbconn, $code='8843')
    {
        $this->code = $code;
        $this->minlength = 11 - strlen($code);
        $this->db = $dbconn;
    }

    /**
     * @param array
    */
    public function loadFromArray($records)
    {
        foreach ($records as $record) {
            $this->calldate = (isset($record['calldate'])) ? $record['calldate'] : NULL;
            $this->srcNumber = (isset($record['src'])) ? $this->__getNumber($record['src']) : '';
            $this->status = ($record['billsec'] > 2) ? true : false;
            if ($this->status) {
                $this->recordingfile = (isset($record['recordingfile'])) ? $record['recordingfile'] : '';
                $this->dstNumber = (isset($record['dst'])) ? $this->__getNumber($record['dst']) : '';
                $this->duration = (isset($record['billsec'])) ? $record['billsec'] : 0;
            } else {
                $this->dstlist[] = $this->__getNumber($record['dst']);
            }
        }
    }

    public function loadByID($id){
        $ids = $this->db->query("SELECT linkedid FROM cel WHERE cel.uniqueid='$id'")->fetchAll(PDO::FETCH_COLUMN);
        $idsstr = implode(',' , $ids);
        $sql = "SELECT cdr.* FROM cdr 
                WHERE (uniqueid='$id' 
                OR uniqueid IN ($idsstr))
                AND lastapp='Dial'";
        $this->loadFromArray($this->db->query($sql, PDO::FETCH_ASSOC));
        return $ids;
    }

    private function __getNumber($text)
    {
        $length = strlen($text);
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
        if (strlen($this->srcNumber) > 4) return $this->srcNumber;
        return$this->dstNumber;
    }

    public function  __toString()
    {
        // TODO: Implement __toString() method.
        return "date: $this->calldate  src: $this->srcNumber dst: $this->dstNumber billsec: $this->duration";
    }
}
