<?php
//glb9 look for files in shared (ini's)
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


$client = new rabbitMQClient("githappensRabbitMQ.ini","databaseServer");
if (isset($argv[1]))
{
  $msg = $argv[1];
}
else
{
  $msg = "test message";
}

$request = array();
$request['type'] = "register";
$request['username'] = "greg";
$request['email'] = "greg@test.com";
$request['password'] = "testpass123";
$request['message'] = $msg;
$response = $client->send_request($request);
//$response = $client->publish($request);

$payload = json_encode($response);
echo $payload . PHP_EOL;
