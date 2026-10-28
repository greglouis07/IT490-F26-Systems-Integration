<?php
header("Content-Type: application/json");
// Enable only when explicitly configured for local testing
$localTesting =
   
    getenv("CARD_SEARCH_LOCAL_TEST") === "1"
    && ($_SERVER["REMOTE_ADDR"] ?? "") === "127.0.0.1"
    && ($_SERVER["SERVER_PORT"] ?? "") === "8000"
    && (($_SERVER["HTTP_HOST"] ?? "") === "127.0.0.1:8000");


// 1. Read the raw body the browser sent: {"cardName":"pikachu"}
$raw = file_get_contents("php://input");

// 2. Turn the JSON text into a PHP array
$input = json_decode($raw, true);

// 3. Make sure cardName exists and isn't blank
if (!is_array($input) || !isset($input['cardName']) || !is_string($input['cardName']) || trim($input['cardName']) === ""){
    echo json_encode(["success" => false, "message" => "Missing cardName", "code" => 2]);
    exit();
}

$cardName = trim($input['cardName']);

 // Load RabbitMQ libraries
$projectRoot = dirname(__DIR__);

set_include_path(
    get_include_path() . PATH_SEPARATOR . $projectRoot . '/shared'
);

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// Locate RabbitMQ configuration
$configFile = $projectRoot . "/shared/githappensRabbitMQ.ini";

if (!is_file($configFile)) {
    http_response_code(503);

    echo json_encode([
        "success" => false,
        "message" => "RabbitMQ configuration is missing."
    ]);
    exit;
}

if (!$localTesting) {
 // Check whether the user has a session cookie
$sessionId = $_COOKIE["sessionId"] ?? "";

if ($sessionId === "") {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to search cards."
    ]);
    exit;
}

// Validate the session through the authentication backend
try {
    $authClient = new rabbitMQClient($configFile, "databaseServer");

    $authResponse = $authClient->send_request([
        "type" => "validate_session",
        "sessionId" => $sessionId
    ]);

    if (!is_array($authResponse) ||
        ($authResponse["success"] ?? false) !== true) {
        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Your session is invalid or expired."
        ]);
        exit;
    }

    //glb9 FIXING CLASH WITH CARD SEARCH close session client
    unset($authClient);

} catch (Throwable $error) {
    error_log("Card search session error: " . $error->getMessage());

    http_response_code(503);

    echo json_encode([
        "success" => false,
        "message" => "Authentication service is unavailable: " . $error->getMessage()
    ]);
    exit;
}

}

// Prepare Pokemon search request
$request = [
    "type" => "card_search",
    "cardName" => $cardName
];

// Limit RabbitMQ response waiting time
ini_set("amqp.read_timeout", "10");

try {
    // Connect to backend, ask DMZ, save cards
    $client = new rabbitMQClient($configFile, "databaseServer");

    // Send the search request
    $response = $client->send_request($request);

    if (!is_array($response) || !isset($response["success"])) {
        throw new Exception("Invalid DMZ response.");
    }

    // Return the DMZ response to JavaScript
    echo json_encode($response);

} catch (Throwable $error) {
    error_log("Card search error: " . $error->getMessage());

    http_response_code(503);

    echo json_encode([
        "success" => false,
        "message" => "Pokemon search service is currently unavailable: " . $error->getMessage()
    ]);
}

exit;
