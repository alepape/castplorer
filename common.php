<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("Chromecast.php");
define("LOGPATH",__DIR__ . "/log/log.txt");

function logMe($txt){
    $datetime = new DateTime();
	//file_put_contents(LOGPATH, $datetime->format(DateTime::ATOM)." - ".$txt."\n", FILE_APPEND);
} // TODO: have several levels of logs
// TODO: expose logs in the UI
// TODO: fix folder permissions so config.json and log.txt can be created


?>
