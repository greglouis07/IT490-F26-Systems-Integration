<?php
//glb9 watchlist link. browser > web server (POST) > backend (RabbitMQ)

header("Content-Type: application/json");

$sessionId = $_COOKIE["sessionId"] ?? "";
if ($sessionId === "") {
   http_response_code(401);
   echo json_encode(["success" => false, "message" => "Please log in."]);
   exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $input["action"] ?? "";

//glb9 only the user-facing watchlist types, never send_email or the price job
if (!in_array($action, ["add_watch", "get_watchlist", "remove_watch"], true)) {
   http_response_code(400);
   echo json_encode(["success" => false, "message" => "Unsupported request."]);
   exit;
}

$root = dirname(__DIR__);
set_include_path(get_include_path() . PATH_SEPARATOR . $root . "/shared");
require_once("path.inc");
require_once("get_host_info.inc");
require_once("rabbitMQLib.inc");

$client = new rabbitMQClient($root . "/shared/githappensRabbitMQ.ini", "databaseServer");

echo json_encode($client->send_request([
   "type"        => $action,
   "sessionId"   => $sessionId,
   "cardId"      => (int) ($input["cardId"] ?? 0),
   "targetPrice" => $input["targetPrice"] ?? 0
]));