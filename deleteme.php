<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require_once("common.php");

logMe("delete from cache");

$key = $_GET["key"]; // exit if empty

if ($key != "") {

    logMe("check local config");
    $config = __DIR__ .'/casts.json';
    $json = file_get_contents($config);
    $storedCastEntities = json_decode($json, true);
    logMe("local config decoded");
    
    // looking for my key
    logMe("removing ".$storedCastEntities[$key]['friendlyname']." from cache...");
    //logMe(json_encode($storedCastEntities[$key], JSON_PRETTY_PRINT));
    unset($storedCastEntities[$key]);

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
