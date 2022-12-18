<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("common.php");

// LOGIC
logMe("saving config");

$full = $_GET["full"];
$configfile = __DIR__ .'/config.json';

if ($full != "") {
    logMe("full config");
    $full = urldecode($full);
    $configdata = json_decode($full, true);
} else {
    $scope = $_GET["scope"];
    if ($scope == "") {
        $scope = "ui"; // default config is 'ui', the other one is 'metrics'
    }
    $domain = $_GET["domain"];
    if ($domain == "") {
        $domain = "_googlecast._tcp.local"; // default domain
    }
    $nosave = ($_GET["nosave"] == 1);
    $live = ($_GET["live"] == 1);
    $wait = $_GET["wait"];
    if ($wait == "") {
        $wait = 10; // 10 sec default timeout
    }
    $wait = $wait*1000;
    
    // Save configuration
    $configjson = file_get_contents($configfile);
    $configdata = json_decode($configjson, true);
    
    // Save configuration
    $configdata[$scope]['domain'] = $domain;
    $configdata[$scope]['nosave'] = $nosave;
    $configdata[$scope]['live'] = $live;
    $configdata[$scope]['wait'] = $wait;    
}

file_put_contents($configfile, json_encode($configdata, JSON_PRETTY_PRINT)); // TODO: have a permission check for config and cast jsons...

logMe("all done");
logMe("------------------------------------------------------------");
header('Content-Type: application/json; charset=utf-8');
echo json_encode($configdata);
?>
