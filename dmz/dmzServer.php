#!/usr/bin/php
<?php
//glb9 dmz listener answers requests by [dmzServer]

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once(__DIR__ . '/pokemonData.php');
require_once(__DIR__ . '/mailService.php'); //sendEmail() for send_email

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
                    "code" => 2
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


        //send_email sends an email via mailService.php **DMZ ONLY NEVER FROM FRONTEND**
        //fields required: toEmail, subject, body. Code 2: bad field, Code 9: send failure
        case "send_email":
            if (!isset($request['toEmail'], $request['subject'], $request['body'])
            || !is_string($request['toEmail'])
            || !is_string($request['subject'])
            || !is_string($request['body'])
            || !filter_var($request['toEmail'], FILTER_VALIDATE_EMAIL)
            || trim($request['subject']) === ""
            || trim($request['body']) === "")


            {
                return array(
                    "success" => false,
                    "message" => "Missing or invalid toEmail, subject and or body",
                    "code" => 2
                );
            }

            //sendEmail() returns contract array (success or code 9)
            return sendEmail($request['toEmail'], $request['subject'], $request['body']);

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