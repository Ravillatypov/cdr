<?php
/**
 * Created by PhpStorm.
 * User: rk
 * Date: 17.06.18
 * Time: 21:37
 */

class Cdr
{
    /*
     * @var PDO $conn
     * @var PDO $freepbx
     * */
    private $conn = NULL;
    private $freepbx = NULL;
    public $didnumbers = array();
    public $calls = array();
    public $allnumbers = array();
    public $internals = array();

    public function __construct($mdsn, $user, $pass){
        try {
            $this->conn = new PDO($mdsn, $user, $pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->freepbx = new PDO("mysql:host=192.168.20.102;dbname=asterisk", $user, $pass);
            $this->freepbx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->loadsipusers();
        }
        catch(PDOException $e)
        {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    /**
     * @param $from string
     * @param $to string
     * @param string $external
     * @param string $internal
     * @return bool
     */
    private function loadCalls($from, $to, $external='', $internal=''){
        require_once ("call.class.php");
        require_once ("callgroup.class.php");
        $wheresql = "";
        if ($this->conn == NULL) return false;
        $extsql = $external ? " (channel LIKE 'SIP/$external%' OR dstchannel LIKE 'SIP/$external%') " : "";
        $intsql = $internal ? " (src=$internal OR dst=$internal) " : "";
        if ($internal && $external) {
            $wheresql = " AND ($extsql OR $intsql )";
        } elseif ($internal){
            $wheresql = " AND $intsql";
        } elseif ($external) {
            $wheresql = " AND $extsql";
        }
        $sql = "SELECT DISTINCT uniqueid FROM cdr WHERE 
        calldate >= '{$from} 00:00:00' 
        AND calldate <= '{$to} 23:59:59' $wheresql";
        $loadedIDs = array();
        foreach ($this->conn->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $record){
            if (in_array($record, $loadedIDs)) continue;
            $call = new Call($this->conn);
            $loadedIDs[] = $call->loadByID($record);
            $this->allnumbers[$call->getExternalNumber()] = "ext";
            $this->calls[] = $call;
        }
        return true;
    }

    private function loadsipusers()
    {
        $sql = "SELECT data FROM sip WHERE keyword='account' AND id IN 
			(SELECT id FROM sip WHERE keyword='context' AND data LIKE 'from-intern%')";
            $this->internals = $this->freepbx->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        $sql = "SELECT data FROM sip WHERE keyword='account' 
                  AND id IN (SELECT id FROM sip WHERE keyword='fromdomain' AND data != '')";
        $this->didnumbers = $this->freepbx->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function run(){
        $from = (isset($_GET['from'])) ? $_GET['from'] : date('Y-m-d');
        $to = (isset($_GET['to'])) ? $_GET['to'] : date('Y-m-d');
        $internal = (isset($_GET['internal'])) ? $_GET['internal'] : '';
        $external = (isset($_GET['external'])) ? $_GET['external'] : '';
        $internal = ($internal == '0') ? '' : $internal;
        $external = ($external == '0') ? '' : $external;
        $this->loadCalls($from, $to, $external, $internal);
        $callGroups = array();
        foreach ($this->allnumbers as $key => $val) {
            if (!$key) continue;
            $group = new CallGroup();
            $group->setNumber($key);
            $group->loadCalls($this->calls);
            $callGroups[] = $group;
        }
        $intertnals = $this->internals;
        $dids = $this->didnumbers;
        include($_SERVER['DOCUMENT_ROOT'] . "/templates/index.phtml");
    }

}
