<?php
    $configfile = __DIR__ .'/config.json';
    $configjson = file_get_contents($configfile);
    $configdata = json_decode($configjson, true);
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
            var nosave = <?=($configdata['nosave'])?1:0?>; // ($user['permissions'] == 'admin') ? true : false;
            var liveonly = <?=($configdata['live'])?1:0?>;
            var wait = <?=($configdata['wait']+0)?> / 1000;
            var domain = "<?=$configdata['domain']?>";
            $('document').ready(function() {
                fillUpConfigTable();
            });
            // TODO: config table below: live, save, domain and wait for UI
            // same for metrics
            // also - other metrics?
            // we could build the wifi signal/noise ;)
        </script>
    </head>
    <body>
<table cellspacing='0' id="devices"> <!-- cellspacing='0' is important, must stay &#xf0c9; -->



</table>

    </body>
</html>