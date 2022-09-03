<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once("common.php");

logMe("change my ip");

$key = $_GET["key"]; // exit if empty
$ip = $_GET["ip"]; // exit if empty

if (($key != "") && ($ip != "")) {

    logMe("check local config");
    $config = __DIR__ .'/casts.json';
    $json = file_get_contents($config);
    $storedCastEntities = json_decode($json, true);
    logMe("local config decoded");
    
    // looking for my key
    logMe("changing ".$storedCastEntities[$key]['friendlyname']." IP address from ".$storedCastEntities[$key]['ip']." to ".$ip);
    //logMe(json_encode($storedCastEntities[$key], JSON_PRETTY_PRINT));
    $storedCastEntities[$key]['ip'] = $ip;

    logMe("save config to file");
    // save config + display result
    file_put_contents($config, json_encode($storedCastEntities, JSON_PRETTY_PRINT));

    $newJson = '{"status": "ok"}';    
    logMe("all done");
    
} else {
    logMe("missing arguments");
    $newJson = '{"status": "ko", "error": "missing argument"}';
}

logMe("------------------------------------------------------------");
header('Content-Type: application/json; charset=utf-8');
echo $newJson;
?>
