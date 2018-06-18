<?php
/**
 * @package
*/
class Call {
    /*
     * @var PDO $db
     * */
    public $db = NULL;
    public $calldate = NULL;
    public $srcNumber = 0;
    /**
     * @var string[] $dstlist
    */
    public $dstlist = array();
    public $status = false;
    public $dstNumber = '';
    public $recordingfile = '';
    public $duration = 0;
    public $did = '';
    public $code = '';
    public $minlength = 0;

    /**
     * @param string $code
     * @param PDO $dbconn
     */
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
            if ($record['lastapp'] == 'Dial') {
                $this->recordingfile = (isset($record['recordingfile'])) ? $record['recordingfile'] : '';
                $this->srcNumber = (isset($record['src'])) ? $this->__getNumber($record['src']) : '';
                if ($record['dcontext'] == 'from-internal') {
                    $this->did = isset($record['src']) ? $record['src'] : '';
                    if (isset($record['channel']) ){
                        $intNumber = array();
                        if (preg_match('/\/[1-9][0-9]{2,4}-/', $record['channel'], $intNumber)){
                            $intNumber = str_replace('/','', $intNumber[0]);
                            $intNumber = str_replace('-','', $intNumber);
                            $this->srcNumber = $intNumber;
                        }
                    }
                }
                $this->calldate = (isset($record['calldate'])) ? $record['calldate'] : NULL;
                $this->status = ($record['billsec'] > 2) ? true : false;
                if ($this->status) {
                    $this->dstNumber = (isset($record['dst'])) ? $this->__getNumber($record['dst']) : '';
                    $this->duration = (isset($record['billsec'])) ? $record['billsec'] : 0;
                } else {
                    $this->dstlist[] = $this->__getNumber($record['dst']);
                }
            }
            else {
                $this->did = (isset($record['did'])) ? $record['did'] : '';
            }
        }
        $this->dstlist = array_unique($this->dstlist);
    }

    /*
     * @param string $id
     * @return string[]
     * */
    public function loadByID($id){
        $sql = "SELECT linkedid FROM cel WHERE cel.uniqueid='$id'";
        $ids = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        $sql = "SELECT uniqueid FROM cel WHERE cel.linkedid='$id'";
        $ids = array_merge($ids, $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN));
        $ids[] = $id;
        $ids = array_unique($ids);
        $idsstr = implode(',' , $ids);
        $sql = "SELECT * FROM cdr 
                WHERE uniqueid IN ($idsstr)";
        $this->loadFromArray($this->db->query($sql, PDO::FETCH_ASSOC));
        return $ids;
    }

    /*
     * @param string $text
     * @return string
     * */
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

    /*
     * @return string
     * */
    public function  __toString()
    {
        // TODO: Implement __toString() method.
        return "date: $this->calldate  src: $this->srcNumber";
    }

    /*
     * @param Call $other
     * @return Call
     */
    public function merge($other){
        if ($this->status){
            $this->dstlist = array_merge($this->dstlist, $other->dstlist);
            return $this;
        } else {
            $other->dstlist = array_merge($this->dstlist, $other->dstlist);
            return $other;
        }
    }
}
