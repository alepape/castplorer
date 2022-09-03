<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("Chromecast.php");
require_once("common.php");

// FUNCTIONS

function cmp($a, $b) {
    $portdiff = $a['port'] - $b['port'];
    if ($portdiff == 0) {
        return strcmp($a['friendlyname'], $b['friendlyname']);
    } else {
        return $portdiff;
    }
}

function getStatus($entities) {
    $statuses = array();
    foreach ($entities as $node) {
        $status = getSingleStatus($node['ip']);
        $statuses[] = $status;
    }
    return $statuses;
}

function getSingleStatus($ip) {
    $curl = curl_init();

    $url = 'https://'.$ip.':8443/setup/eureka_info?params=version,audio,name,build_info,detail,device_info,net,wifi,setup,settings,opt_in,opencast,multizone,proxy,night_mode_params,user_eq,room_equalizer,sign,aogh,ultrasound,mesh';
    //echo $url;

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

function getLiteStatus($ip) {
    $curllite = curl_init();

    $url = 'http://'.$ip.':8008/setup/eureka_info';
    //echo $url;

    curl_setopt_array($curllite, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ));

    $response = curl_exec($curllite);

    curl_close($curllite);
    return $response;
}

function fillInStatus(&$entity) {
    logMe("details check for ".$entity['friendlyname']." started");
    $entity['status'] = json_decode(getSingleStatus($entity['ip']), true);
    if ($entity['status'] == NULL) {
        logMe("lite check for ".$entity['friendlyname']." started");
        $liteJson = getLiteStatus($entity['ip']);
        //echo $liteJson;
        $entity['status'] = json_decode($liteJson, true);
        logMe("lite check for ".$entity['friendlyname']." done");
    }
    logMe("details check for ".$entity['friendlyname']." done");
}

// LOGIC

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
$single = $_GET["single"]; // ignores live (need the cache to get the ip from the ID) and wait and domain

// Save configuration
$config = __DIR__ .'/config.json';
$configdata = [];
$configdata['domain'] = $domain;
$configdata['nosave'] = $nosave;
$configdata['live'] = $live;
$configdata['wait'] = $wait;
file_put_contents($config, json_encode($configdata, JSON_PRETTY_PRINT)); // TODO: have a permission check for config and cast jsons...

// TODO: add a "last seen" timestamp to show in the UI
// TODO: create a home assistant output mode (YAML?)
// TODO: add a configuration json to select UI options (which columns, etc.)
if (!$live || ($single != "")) {
    logMe("check local cache");
    $cache = __DIR__ .'/casts.json';
    $json = file_get_contents($cache);
    $storedCastEntities = json_decode($json, true);
    foreach($storedCastEntities as &$entity) {
        if (isset($entity['live'])) {unset($entity['live']);}
    }
    logMe("local cache decoded");
} else {
    $json = "";
    $storedCastEntities = [];
}

if ($single == "") {
    // scan 
    logMe("chromecast scan started on ".$domain);
    $castEntities = Chromecast::scan($wait, $domain); 

    foreach($castEntities as $key => $value) {
        if (!strpos($key, $domain)) {
            unset($castEntities[$key]);
            logMe("removing ".$key);
        } else {
            logMe("live: [".$value['friendlyname']."] - ".$key);
        }
    }
    logMe("chromecast scan done - ".count($castEntities)." results");

    if (($json == "") || ($json == NULL)) {
        $storedCastEntities = $castEntities;
    } else {
        $storedCastEntities = array_merge($storedCastEntities, $castEntities);
    }
    foreach($storedCastEntities as $key => &$value) {
        $value['live'] = array_key_exists($key, $castEntities);
    }
    // fill in status
    foreach($storedCastEntities as &$entity) {
        if ($entity['port'] == 8009) {
            fillInStatus($entity);
        } else {
            $entity['status'] = array();
        }
    }

    logMe("clean and sort");
    uasort($storedCastEntities, "cmp");
} else { // single mode - scanning skipped
    logMe("chromecast scan skipped - single mode for ".$single);
    //$singleEntity = $storedCastEntities[$single];
    if (!isset($storedCastEntities[$single])) {
        $newJson = "";
        logMe("no cast found based on single ID");
    } else {
        fillInStatus($storedCastEntities[$single]);
        $newJson = json_encode($storedCastEntities[$single]);
    }
}

if (!$nosave) {
    // prepare the save file (no groups)
    $saveCastEntities = [];
    foreach($storedCastEntities as $key => &$entity) {
        if ($entity['port'] == 8009) {
            $saveCastEntities[$key] = $entity;
        }
    }

    logMe("save cache to file");
    // save cache + display result
    file_put_contents($cache, json_encode($saveCastEntities, JSON_PRETTY_PRINT));
}

if ($single == "") {
    $newJson = json_encode($storedCastEntities);
} else {
    // use newJson from single mode above
}

logMe("all done");
logMe("------------------------------------------------------------");
header('Content-Type: application/json; charset=utf-8');
echo $newJson;
?>
