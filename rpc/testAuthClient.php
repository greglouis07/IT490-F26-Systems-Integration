#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

//give up after 10 seconds with no listener
ini_set("amqp.read_timeout", "10");

//new user every run
$user = "test" . time();
$pass = "testpass123";

$tests = [
	['type' => 'register', 'username' => $user, 'email' => $user . '@example.com', 'password' => $pass],
	['type' => 'register', 'username' => $user, 'email' => $user . '@example.com', 'password' => $pass],
	['type' => 'login', 'username' => $user, 'password' => 'wrongpass1'],
	['type' => 'login', 'username' => $user, 'password' => 'pass1'],
	['type' => 'validate_session', 'sessionId' => ''],
	['type' => 'logout', 'sessionId' => ''],
	['type' => 'validate_session', 'sessionId' => ''],
	['type' => 'login', 'username' => $user],
	['type' => 'nonsense'],
	];

foreach ($tests as $request) {
	if (isset($request['sessionId'])) {
		$request['sessionId'] = $sessionId;
	}

	$client = new rabbitMQClient("githappensRabbitMQ.ini", "databaseServer");
	$response = $client->send_request($request);
	if (!empty($response['sessionId'])) {
		$sessionId = $response['sessionId'];
	}
	echo "Sent: " . json_encode($request).  PHP_EOL;
	echo "Got: " .json_encode($response) . PHP_EOL . PHP_EOL;

	unset($client);
	gc_collect_cycles();
}
