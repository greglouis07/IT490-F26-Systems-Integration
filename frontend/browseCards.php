<?php
header("Content-Type: application/json");

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

// Read request
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request"
    ]);
    exit;
}

// Validate sorting option
$allowedSorts = ["recent", "price_low", "price_high"];
$sort = $input["sort"] ?? "recent";

if (!is_string($sort) || !in_array($sort, $allowedSorts, true)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid sort option"
    ]);
    exit;
}

// Load RabbitMQ libraries
$projectRoot = dirname(__DIR__);

set_include_path(
    get_include_path() . PATH_SEPARATOR . $projectRoot . "/shared"
);

require_once("path.inc");
require_once("get_host_info.inc");
require_once("rabbitMQLib.inc");

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

// Check login session
$sessionId = $_COOKIE["sessionId"] ?? "";

if (!is_string($sessionId) || $sessionId === "") {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to browse cards."
    ]);
    exit;
}

// Validate session through the database server
try {
    ini_set("amqp.read_timeout", "10");

    $authClient = new rabbitMQClient($configFile, "databaseServer");

    $authResponse = $authClient->send_request([
        "type" => "validate_session",
        "sessionId" => $sessionId
    ]);

    unset($authClient);

    if (!is_array($authResponse) ||
        ($authResponse["success"] ?? false) !== true) {

        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Your session is invalid or expired."
        ]);
        exit;
    }

} catch (Throwable $error) {
    error_log("Browse authentication error: " . $error->getMessage());

    http_response_code(503);
    echo json_encode([
        "success" => false,
        "message" => "Authentication service unavailable."
    ]);
    exit;
}

// Retrieve saved cards through RabbitMQ
try {
    $client = new rabbitMQClient($configFile, "databaseServer");

    $response = $client->send_request([
        "type" => "browse_cards",
        "sort" => $sort
    ]);

    if (!is_array($response) || !isset($response["success"])) {
        throw new Exception("Invalid database server response.");
    }

    echo json_encode($response);

} catch (Throwable $error) {
    error_log("Browse cards error: " . $error->getMessage());

    http_response_code(503);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load browsing cards."
    ]);
}

exit;