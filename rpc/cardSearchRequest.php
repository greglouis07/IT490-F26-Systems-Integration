<?php
//glb9 test client: sends card_search to dmzServer.php

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("githappensRabbitMQ.ini", "dmzServer");

$request = array();
$request['type'] = "card_search";
//example card charizard
$request['cardName'] = isset($argv[1]) ? $argv[1] : "charizard";

$response = $client->send_request($request);

echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
?>
