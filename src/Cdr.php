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
     * */
    private $conn = NULL;
    public $didnumbers = array();
    public $calls = array();
    public $allnumbers = array();
    public $internals = array();

    public function __construct($mdsn, $user, $pass){
        try {
            $this->conn = new PDO($mdsn, $user, $pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->getdids();
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
        if ($this->conn == NULL) return false;
        $wheresql = $external ? " AND (did=$external OR src=$external)" : "";
        $wheresql .= $internal ? " AND src=$internal" : "";
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

    private function getdids()
    {
        $sql = "SELECT DISTINCT did FROM cdr WHERE did != '' AND lastapp='Dial'";
        foreach ($this->conn->query($sql, PDO::FETCH_ASSOC) as $record){
            $this->didnumbers[] = $record['did'];
        }
    }

    public function run(){
        $from = (isset($_GET['from'])) ? $_GET['from'] : date('Y-m-d');
        $to = (isset($_GET['to'])) ? $_GET['to'] : date('Y-m-d');
        $internal = (isset($_GET['internal'])) ? $_GET['internal'] : '';
        $external = (isset($_GET['external'])) ? $_GET['external'] : '';
        $this->loadCalls($from, $to, $external, $internal);
        $callGroups = array();
        foreach ($this->allnumbers as $key => $val) {
            if (!$key) continue;
            $group = new CallGroup();
            $group->setNumber($key);
            $group->loadCalls($this->calls);
            $callGroups[] = $group;
        }
        include($_SERVER['DOCUMENT_ROOT'] . "/templates/index.phtml");
    }

}