#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("githappensRabbitMQ.ini", "testServer");

$tests = [
	['type' => 'register', 'username' => 'testuser', 'password' => 'pass123'],
	['type' => 'login', 'username' => 'testuser', 'password' => 'pass123'],
	['type' => 'validate_session', 'sessionID' => 'fake123'],
	['type' => 'logout', 'sessionID' => 'fake123'],
	['type' => 'login', 'username' => 'testuser'],
	['type' => 'nonsense'],
];

foreach ($tests as $request) {
	$response = $client->send_request($request);
	echo "Sent: " . json_encode($request).  PHP_EOL;
	echo "Got: " .json_encode($response) . PHP_EOL . PHP_EOL;
}
