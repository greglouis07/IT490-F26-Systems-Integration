#!/usr/bin/php
<?php
//glb9 dmz listener answers requests by [dmzServer]

set_include_path(get_include_path() . PATH_SEPARATOR > __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function requestProcessor($request)
{
    echo "recieved request" . PHP_EOL;

    if (!is_array($request) || !isset($request['type']))
    {
        return array(
            "success" => false,
            "message" => "Missing message type",
            "code" => 1
        );
    }

    echo "processing type: " . $request['type'] . PHP_EOL;
}

$server = new rabbitMQServer(
    "githappensRabbitMQ.ini",
    "dmzServer"
);

echo "dmzServer BEGIN" . PHP_EOL;

$server->process_requests('requestProcessor');

echo "dmzServer END" . PHP_EOL;

exit();
?>