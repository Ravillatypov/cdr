<?php
// "mysql:host=192.168.20.102;dbname=asteriskcdrdb"

require_once ("src/Cdr.php");
$report = new Cdr("mysql:host=192.168.20.102;dbname=asteriskcdrdb", "rk", "123123");
$report->run();
