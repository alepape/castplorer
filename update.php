<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("Chromecast.php");

// $details = ($_GET["details"] == 1);
// $update = ($_GET["update"] == 1);
$details = true;
$update = true;

define("LOGPATH",__DIR__ . "/log/log.txt");

function logMe($txt){
    $datetime = new DateTime();
	file_put_contents(LOGPATH, $datetime->format(DateTime::ATOM)." - ".$txt."\n", FILE_APPEND);
}

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

// TODO: add a "last seen" timestamp to show in the UI
// TODO: create a home assistant output mode (YAML?)
// TODO: add a configuration json to select UI options (which columns, etc.)

logMe("check local config");
$config = __DIR__ .'/casts.json';
$json = file_get_contents($config);
$storedCastEntities = json_decode($json, true);
logMe("local config decoded");

// update config
logMe("chromecast scan started");
$castEntities = Chromecast::scan(10000); // TODO: add more traces in libraries

foreach($castEntities as $key => $value) {
    if (!strpos($key, "googlecast")) {
        unset($castEntities[$key]);
    } else {
        logMe("live: [".$value['friendlyname']."]");
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
    } else {
        $entity['status'] = array();
    }
}

logMe("clean and sort");
uasort($storedCastEntities, "cmp");

// prepare the save file (no groups)
$saveCastEntities = [];
foreach($storedCastEntities as $key => &$entity) {
    if ($entity['port'] == 8009) {
        $saveCastEntities[$key] = $entity;
    }
}

logMe("save config to file");

// save config + display result
file_put_contents($config, json_encode($saveCastEntities, JSON_PRETTY_PRINT));
$newJson = json_encode($storedCastEntities);

logMe("all done");
logMe("------------------------------------------------------------");
header('Content-Type: application/json; charset=utf-8');
echo $newJson;
?>
