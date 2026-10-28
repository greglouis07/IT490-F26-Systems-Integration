<?php
//rahuliyr7 adapted glb9 test client: now instead of cards sends send_email to dmzServer.php
//tested with/for php rpc/emailRequest.php you@example.com ["subject"]

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("githappensRabbitMQ.ini", "dmzServer");

//request array
$request = array();
$request['type'] = "send_email";
$request['toEmail'] = isset($argv[1]) ? $argv[1] : "";
$request['subject'] = isset($argv[2]) ? $argv[2] : "DMZ send_email test";
$request['body']="If you can read this, the DMZ email sent it and is functional.";

$response = $client->send_request($request);

echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
?>