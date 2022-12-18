<?php
    require_once("common.php");
    $configfile = __DIR__ .'/config.json';
    $configjson = file_get_contents($configfile);
    $configdata = json_decode($configjson, true);
    //logMe($configdata['wait']);
?>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="style.css">
        <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="json-formatter-js/json-formatter.umd.js"></script>
        <link rel="stylesheet" href="json-formatter-js/json-formatter.css"></link>
        <script type="text/javascript" src="script.js"></script>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        <script type="text/javascript">
            var nosave = <?=($configdata['ui']['nosave'])?1:0?>; // ($user['permissions'] == 'admin') ? true : false;
            var liveonly = <?=($configdata['ui']['live'])?1:0?>;
            var wait = <?=($configdata['ui']['wait']+0)?> / 1000;
            var domain = "<?=$configdata['ui']['domain']?>";
            $('document').ready(function() {
                refreshJson();
            });
        </script>
    </head>
    <body>
<table cellspacing='0' id="devices"> <!-- cellspacing='0' is important, must stay &#xf0c9; -->

<!-- Table Header -->
<thead>
    <tr>
        <th><a class="tooltip" onclick="config();" data-title="Edit the configuration"><i style="font-size:12px" class="fa">&#xf013;</i></a>&nbsp;&nbsp;<a class="tooltip" onclick="refreshJson();" data-title="Refresh all devices"><i style="font-size:12px" class="fa" id="refresh">&#xf021;</i></a></th>
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

</table>

    </body>
</html>