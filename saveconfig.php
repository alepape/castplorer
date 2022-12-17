<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("common.php");

// LOGIC
logMe("saving config");

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
$config = __DIR__ .'/config.json';
$configdata = [];
$configdata['domain'] = $domain;
$configdata['nosave'] = $nosave;
$configdata['live'] = $live;
$configdata['wait'] = $wait;
file_put_contents($config, json_encode($configdata, JSON_PRETTY_PRINT)); // TODO: have a permission check for config and cast jsons...

logMe("all done");
logMe("------------------------------------------------------------");
header('Content-Type: application/json; charset=utf-8');
echo json_encode($configdata);
?>
