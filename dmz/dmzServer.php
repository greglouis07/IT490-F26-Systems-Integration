#!/usr/bin/php
<?php
//glb9 dmz listener answers requests by [dmzServer]

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once(__DIR__ . '/pokemonData.php');

function requestProcessor($request)
{
    echo "received request" . PHP_EOL;

    if (!is_array($request) || !isset($request['type']))
    {
        return array(
            "success" => false,
            "message" => "Missing message type",
            "code" => 1
        );
    }

    echo "processing type: " . $request['type'] . PHP_EOL;

    //Confirm dmz route works
    switch ($request['type'])
    {
        case "ping":
            return array(
                "success" => true,
                "message" => "dmz listener is up"
            );

        case "get_card_prices":
            if (!isset($request['cardId']))
            {
                return array(
                    "success" => false,
                    "message" => "Missing cardId",
                    "code" => 1
                );
            }
            return getCardPrices($request['cardId']);

        case "card_search":
            if (!isset($request['cardName']) || trim($request['cardName']) === "")
            {
                return array(
                    "success" => false,
                    "message" => "Missing cardName",
                    "code" => 2
                );
            }
            return cardSearch($request['cardName']);

        default:
            return array(
                "success" => false,
                "message" => "Unknown request type",
                "code" => 1
            );
    }
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