
<?php
/*
Git Happens - Frontend Authentication API

Receives login and registration requests from JavaScript
and forwards them to the RabbitMQ authentication backend.
*/

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

// Read the JSON sent by JavaScript.
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);
    exit;
}

$type = $input["type"] ?? "";
$username = trim($input["username"] ?? "");
$password = $input["password"] ?? "";

if (!in_array($type, ["login", "register"], true)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Unsupported authentication request."
    ]);
    exit;
}

if (!is_string($username) || !is_string($password) ||
    $username === "" || $password === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Username and password are required."
    ]);
    exit;
}

// Aleen's current backend has a login handler,
// but registration is not implemented yet.
if ($type === "register") {
    http_response_code(501);
    echo json_encode([
        "success" => false,
        "message" => "Registration backend is not available yet."
    ]);
    exit;
}

// Load RabbitMQ client libraries from the project root.
$projectRoot = dirname(__DIR__);

require_once $projectRoot . "/path.inc";
require_once $projectRoot . "/get_host_info.inc";
require_once $projectRoot . "/rabbitMQLib.inc";

// This configuration will be created separately.
// It must point to Aleen's broker over Tailscale.
$configFile = $projectRoot . "/frontendRabbitMQ.ini";

if (!is_file($configFile)) {
    http_response_code(503);
    echo json_encode([
        "success" => false,
        "message" => "RabbitMQ client configuration is missing."
    ]);
    exit;
}

$request = [
    "type" => "login",
    "username" => $username,
    "password" => $password
];

try {
    $client = new rabbitMQClient($configFile, "testServer");
    $response = $client->send_request($request);

    if (!is_array($response) || !isset($response["success"])) {
        throw new Exception("Invalid authentication response.");
    }

    // Do not return sensitive fields to the browser.
    echo json_encode([
        "success" => (bool) $response["success"],
        "message" => $response["message"] ?? "Authentication completed.",
        "user" => $response["success"]
            ? [
                "id" => $response["user"]["id"] ?? null,
                "username" => $response["user"]["username"] ?? null,
                "email" => $response["user"]["email"] ?? null
            ]
            : null
    ]);

} catch (Throwable $error) {
    error_log("Authentication API error: " . $error->getMessage());

    http_response_code(503);

    echo json_encode([
        "success" => false,
        "message" => "Authentication service is currently unavailable."
    ]);
}
?>
