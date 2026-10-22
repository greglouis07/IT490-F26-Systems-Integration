<?php
//glb9 test client for dmz to send ping to dmzServer.php"

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("githappensRabbitMQ.ini", "dmzServer");

$request = array();
$request['type'] = "ping";

$response = $client->send_request($request);

echo json_encode($response) . PHP_EOL;
?>