<?php

$didnumbers = array('123', '1234');
$user = array(
    "100" => "Змей Горыныч",
    "101" => "Добрыня"
);
$code = '8843';
$numberLenght = 7;

function init(){
    try {
        $conn = new PDO("mysql:host=fs.loc;dbname=asteriskcdrdb", "rk", "123123");
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
    $conn = init();
    $calls = array();
    $allnumbers = array();
    $sql = "SELECT * FROM cdr WHERE 
        calldate >= '{$from} 00:00:00' 
        AND calldate <= '{$to} 23:59:59'
        AND lastapp='Dial' GROUP BY calldate";
    foreach ($conn->query($sql, PDO::FETCH_ASSOC) as $record){
        $call = new Call();
        $call->loadFromArray($record);
        $allnumbers[$call->getExternalNumber()] = "  ";
        $calls[] = $call;
    }
    return array($calls, $allnumbers);
}


function run(){
    $from = (isset($_GET['from']) && count($_GET['from']) > 5) ? $_GET['from'] : date('Y-m-d');
    $to = (isset($_GET['to']) && count($_GET['to']) > 5) ? $_GET['to'] : date('Y-m-d');
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
