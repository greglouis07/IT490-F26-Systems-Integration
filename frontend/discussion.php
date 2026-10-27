<?php
header("Content-Type: application/json");
 
$sessionId = $_COOKIE["sessionId"] ?? "";
if ($sessionId === "") {
   http_response_code(401);
   echo json_encode(["success" => false, "message" => "Please log in."]);
   exit;
}
 
$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $input["action"] ?? "";
 
if (!in_array($action, ["list_posts", "get_post", "create_post", "create_reply"], true)) {
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
   "type"      => $action,
   "sessionId" => $sessionId,
   "postId"    => (int) ($input["postId"] ?? 0),
   "title"     => $input["title"] ?? "",
   "body"      => $input["body"] ?? ""
]));

