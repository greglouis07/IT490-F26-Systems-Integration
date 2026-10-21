<?php
 
header("Content-Type: application/json");
 
session_start();
 
function respond($status, $payload)
{
   http_response_code($status);
   echo json_encode($payload);
   exit;
}
 
if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
   header("Allow: POST");
   respond(405, ["success" => false, "message" => "Only POST requests are allowed."]);
}
 
// Signed-in users only.
if (empty($_SESSION["sessionId"]))
{
   respond(401, ["success" => false, "message" => "Please log in to use the discussion board."]);
}
 
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input))
{
   respond(400, ["success" => false, "message" => "Invalid JSON request."]);
}
 
$action = $input["action"] ?? "";
 
// Map the browser's action to the RabbitMQ message type + only the fields it needs.
$request = ["sessionId" => $_SESSION["sessionId"]];
 
switch ($action)
{
   case "list_posts":
       $request["type"] = "list_posts";
       break;
 
   case "get_post":
       $request["type"]   = "get_post";
       $request["postId"] = (int) ($input["postId"] ?? 0);
       break;
 
   case "create_post":
       $request["type"]  = "create_post";
       $request["title"] = (string) ($input["title"] ?? "");
       $request["body"]  = (string) ($input["body"] ?? "");
       break;
 
   case "create_reply":
       $request["type"]   = "create_reply";
       $request["postId"] = (int) ($input["postId"] ?? 0);
       $request["body"]   = (string) ($input["body"] ?? "");
       break;
 
   default:
       respond(400, ["success" => false, "message" => "Unsupported discussion request."]);
}
 
$projectRoot = dirname(__DIR__);
set_include_path(get_include_path() . PATH_SEPARATOR . $projectRoot . '/shared');
 
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
 
$configFile = $projectRoot . "/shared/githappensRabbitMQ.ini";
 
if (!is_file($configFile))
{
   respond(503, ["success" => false, "message" => "RabbitMQ client configuration is missing."]);
}
 
try
{
   $client   = new rabbitMQClient($configFile, "testServer");
   $response = $client->send_request($request);
 
   if (!is_array($response) || !isset($response["success"]))
   {
       throw new Exception("Invalid discussion response.");
   }
 
   // Backend says the session expired (code 5): clear it so the page sends them to login.
   if (!$response["success"] && ($response["code"] ?? null) === 5)
   {
       $_SESSION = [];
       session_destroy();
       respond(401, ["success" => false, "message" => "Your session has expired. Please log in again."]);
   }
 
   unset($response["sessionId"]);
   echo json_encode($response);
}
catch (Throwable $error)
{
   error_log("Discussion API error: " . $error->getMessage());
   respond(503, ["success" => false, "message" => "Discussion service is currently unavailable."]);
}
?>

