<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("Chromecast.php");

$details = ($_GET["details"] == 1);
$update = ($_GET["update"] == 1);

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

logMe("check local config");
$config = __DIR__ .'/casts.json';
$json = file_get_contents($config);
$storedCastEntities = json_decode($json, true);
logMe("local config decoded");

if ($update) {
    // update config
    logMe("chromecast scan started");
    $castEntities = Chromecast::scan(1000);
    logMe("chromecast scan done - ".count($castEntities)." results");

    foreach($castEntities as $key => $value) {
        if (!strpos($key, "googlecast")) {
            unset($castEntities[$key]);
        }
    }

    if (($json == "") || ($json == NULL)) {
        $storedCastEntities = $castEntities;
    } else {
        $storedCastEntities = array_merge($storedCastEntities, $castEntities);
    }

    foreach($storedCastEntities as $key => &$value) {
        $value['live'] = array_key_exists($key, $castEntities);
    }
}

// fill in status
if ($details) {
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
}
logMe("clean and sort");

uasort($storedCastEntities, "cmp");

// icons
foreach($storedCastEntities as &$entity) {
    if ($entity['port'] == 8009) {
        // minis
        if ($entity['status']['device_info']['model_name'] == "Google Home Mini") {
            $entity['icon'] = "<img src='img/mini.png' height=51/>";
        } else if ($entity['status']['device_info']['model_name'] == "Google Nest Hub") { // hubs
            $entity['icon'] = "<img src='img/hub.png' height=51/>";
        } else if ($entity['status']['device_info']['model_name'] == "Chromecast Audio") { // audio
            $entity['icon'] = "<img src='img/audio.png' height=51/>";
        } else {
            if ($entity['status']['device_info']['capabilities']['display_supported']) { // tv
                $entity['icon'] = "<img src='img/screen.png' height=51/>";
            } else {
                $entity['icon'] = "<img src='img/max.png' height=51/>";
            }
        }
    } else {
        $entity['icon'] = "";
    }
}

logMe("save config to file");

// save config + display result
$newJson = json_encode($storedCastEntities, JSON_PRETTY_PRINT);
file_put_contents($config, $newJson);

logMe("all done");
logMe("------------------------------------------------------------");

// generate html
?>
<html>
    <head>
        <link rel="stylesheet" href="style.css">
        <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="json-formatter-js/json-formatter.umd.js"></script>
        <link rel="stylesheet" href="json-formatter-js/json-formatter.css"></link>
        <script type="text/javascript">
            var jsonsrc = <?php echo json_encode($storedCastEntities) ?>;
            var formatter;
            $('document').ready(function() {
                $('#devices').on('click', 'tr', function () {
                    json = jsonsrc[$(this)[0].id];
                    if (!json) return; // click in details
                    var sel = '#details-'+$(this)[0].id;
                    sel = sel.replaceAll(".", "\\.");
                    if (!$(sel).hasClass("collapsed")) { // close
                        $(sel).addClass("collapsed");
                        return;
                    }
                    formatter = new JSONFormatter(json);
                    $('td.details').addClass("collapsed");
                    document.querySelector(sel).innerHTML = "";
                    document.querySelector(sel).appendChild(formatter.render());
                    formatter.openAtDepth(2);
                    $(sel).removeClass("collapsed");
                });
            });
        </script>
    </head>
    <body>
<table cellspacing='0' id="devices"> <!-- cellspacing='0' is important, must stay -->

<!-- Table Header -->
<thead>
    <tr>
        <th><?=count($castEntities)?></th>
        <th>Device</th>
        <th>IP</th>
        <th>Port</th>
        <th>Type</th>
        <th>WiFi</th>
        <th>Version</th>
        <th>Groups #</th>
    </tr>
</thead>
<!-- Table Header -->

<!-- Table Body -->
<tbody>

<?php
    foreach ($storedCastEntities as $id => $cast) {
        if (isset($cast['status']['multizone'])) {
            $grps = count($cast['status']['multizone']['groups']);
        } else {
            $grps = 0;
        }
?>
    <tr <?=$cast['live'] ? "class='live'" : ""?> id="<?=$id?>">
        <td><?=$cast['icon']?></td>
        <td><?=$cast['friendlyname']?></td>
        <td><?=$cast['ip']?></td>
        <td><?=$cast['port']?></td>
<?php
 if (($cast['port'] == 8009) && (isset($cast['status']['device_info']))) {
?>
        <td><?=$cast['status']['device_info']['model_name']?></td>
        <td><?=$cast['status']['wifi']['ssid']." (".$cast['status']['wifi']['signal_level']."/".$cast['status']['wifi']['noise_level'].")"?></td>
        <td><?=$cast['status']['build_info']['cast_build_revision']?></td>
        <td><?=$grps?></td>
<?php
 } else if ($cast['port'] == 8009) {
?>
        <td><?=$cast['friendlyname']?></td>
        <td><?=$cast['status']['ssid']." (".$cast['status']['signal_level']."/".$cast['status']['noise_level'].")"?></td>
        <td><?=$cast['status']['cast_build_revision']?></td>
        <td><?=$grps?></td>
<?php
 }
?>
    </tr>
    <tr style="height: 0px"><td class="details collapsed" colspan="8" id="details-<?=$id?>">toto
    </td></tr>
<?php
    }
?>

</tbody>
<!-- Table Body -->

</table>

    </body>
</html>