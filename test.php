<?php
if (file_exists(__DIR__ . "/main.php"))
    require_once __DIR__ . "/main.php";

$arRetSlaveStatus = array(
    "Slave_IO_State" => "Waiting for master to send event",
    "Slave_IO_Running" => "No",
    "Slave_SQL_Running" => "No",
    "Seconds_Behind_Master" => 5,
    "Master_Host" => "localhost",
    "Master_User" => "repl",
    "Master_Port" => 13000,
    "Connect_Retry" => 60,
    "Master_Log_File" => "master-bin.000002",
    "Read_Master_Log_Pos" => 1307
);

getShowSlaveStatus($arRetSlaveStatus);
