<?php

function init(){
    $conn = NULL;
    try {
        $conn = new PDO("mysql:host=192.168.20.102;dbname=asteriskcdrdb", "rk", "123123");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    catch(PDOException $e)
        {
        echo "Connection failed: " . $e->getMessage();
        }
        return $conn;
}

function loadCalls($from, $to){
    require_once ("src/call.class.php");
    require_once ("src/callgroup.class.php");
    $didnumbers = array('78432054455', '78432051228');
    $conn = init();
    if ($conn == NULL) return [[],[]];
    $calls = array();
    $allnumbers = array();
    $sql = "SELECT * FROM cdr WHERE 
        calldate >= '{$from} 00:00:00' 
        AND calldate <= '{$to} 23:59:59'
        AND lastapp='Dial' ORDER BY calldate";
    foreach ($conn->query($sql, PDO::FETCH_ASSOC) as $record){
        if (in_array($record['src'], $didnumbers) || in_array($record['dst'], $didnumbers)) continue;
        $call = new Call();
        $call->loadFromArray($record);
        error_log("record: " . implode(": ", $record), 0);
        error_log("call: $call", 0);
        $allnumbers[$call->getExternalNumber()] = "ext";
        $calls[] = $call;
    }
    return array($calls, $allnumbers);
}


function run(){
    $from = (isset($_GET['from'])) ? $_GET['from'] : date('Y-m-d');
    $to = (isset($_GET['to'])) ? $_GET['to'] : date('Y-m-d');
    $callsResult = loadCalls($from, $to);
    $callGroups = array();
    foreach ($callsResult[1] as $key => $val) {
        $group = new CallGroup();
        $group->setNumber($key);
        $group->loadCalls($callsResult[0]);
        $callGroups[] = $group;
    }
    include("templates/index.phtml");
}

run();
