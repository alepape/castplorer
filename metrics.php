<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("Chromecast.php");
require_once("common.php");

// KPIS
$nb_live_devices = 0;
$device_reponse_times = [];


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
    logMe("lite check for ".$entity['friendlyname']." started");
    $start = microtime(true);
    $liteJson = getLiteStatus($entity['ip']);
    $time_elapsed_secs = microtime(true) - $start;
    //echo $liteJson;
    $entity['status'] = json_decode($liteJson, true);
    //clean up name and type for AirServer
    $extractedType = get_string_between($entity['friendlyname'], "md=", "nf=");
    if ($extractedType != "") {
        $entity['type'] = $extractedType;
    }
    if (isset($entity['status']['name']) && ($entity['friendlyname'] != $entity['status']['name'])) {
        $entity['friendlyname'] = $entity['status']['name'];
    }
    logMe("lite check for ".$entity['friendlyname']." done");
    return Array($entity['friendlyname'], $time_elapsed_secs);
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function generate_metrics($name, $desc, $type, $values) {
    echo("# HELP ".$name." ".$desc."\n");
    echo("# TYPE ".$name." ".$type."\n");
    foreach ($values as $key => $value) {
        echo($name.$key." ".$value."\n");
    }
}

// LOGIC
header('Content-Type: text/plain; charset=utf-8');

logMe("metrics started");

// TODO: load from config file instead
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

if (!$live) {
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

// scan 
logMe("chromecast scan started on ".$domain);
$castEntities = Chromecast::scan($wait, $domain); 
// TODO: deal w/ AirServer entries (ex: C02FM1JBQ05N (602)#id=263c8132f2065d05e6c15e7cee9e14c3md=Chromecast Ultranf=1rm=rs	)

// cleaning bad keys (mainly spaces)
foreach($castEntities as $key => $value) {
    $goodkey = str_replace(" ", "_", $key);
    $goodkey = str_replace("(", "_", $goodkey);
    $goodkey = str_replace(")", "_", $goodkey);
    if ($goodkey != $key) {
        unset($castEntities[$key]);
    }
    $castEntities[$goodkey] = $value;
}

foreach($castEntities as $key => $value) {
    if (!strpos($key, $domain)) {
        unset($castEntities[$key]);
        logMe("removing ".$key);
    } else {
        logMe("live: [".$value['friendlyname']."] - ".$key); // TODO: clean up friendly names and types (see above)
    }
}
logMe("chromecast scan done - ".count($castEntities)." results");
generate_metrics("nb_devices", "Number of devices found via mDNS", "gauge", array("{domain=\"".$domain."\", wait=\"".($wait/1000)."\"}" => count($castEntities)));

if (($json == "") || ($json == NULL)) {
    $storedCastEntities = $castEntities;
} else {
    $storedCastEntities = array_merge($storedCastEntities, $castEntities);
}
foreach($storedCastEntities as $key => &$value) {
    $value['live'] = array_key_exists($key, $castEntities);
}
// fill in status
$statuses = array();
foreach($storedCastEntities as &$entity) {
    if ($entity['port'] == 8009) { // TODO: or list them as well???
        $result = fillInStatus($entity);
        if ($result[0] != "") {
            $statuses["{id=\"".$result[0]."\"}"] = ($result[1] * 1000);
        }
    } else {
        // $entity['status'] = array();
        // ignore groups
    }
}
generate_metrics("response_time", "Response time for eureka_info API call", "gauge", $statuses);

logMe("clean and sort");
uasort($storedCastEntities, "cmp");

$newJson = json_encode($storedCastEntities);

logMe("all done");
logMe("------------------------------------------------------------");
?>
