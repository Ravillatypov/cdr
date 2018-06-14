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
        $conn = new PDO("mysql:host=loca;dbname=asteriscdrdb", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected successfully"; 
        }
    catch(PDOException $e)
        {
        echo "Connection failed: " . $e->getMessage();
        }
        return $conn;
}

function loadCalls($from, $to){
    //loadlib("src/call.class.php");
    $conn = init();
    $calls = array();
    $allnumbers = array();
    $stm = $conn->query("SELECT * FROM cdr WHERE 
        calldate >= '{$from} 00:00:00' 
        AND calldate <= '{$to} 23:59:59'
         lastapp='Dial' GROUP BY calldate");
    $stm->execute();
    while ($record = $stm->fetch()){
        $call = new Call();
        $call->loadFromArray($record);
        $allnumbers[$call->getExternalNumber()] = 1;
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