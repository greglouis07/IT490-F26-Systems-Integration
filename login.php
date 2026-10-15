<?php
chdir(__DIR__);
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


ini_set('amqp.read_timeout', '5');
ini_set('display_errors', '0');


ini_set('session.use_strict_mode', 1);
session_set_cookie_params(array("httponly" => true, "samesite" => "Strict"));
session_start();


if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
    $msg = "NO POST MESSAGE SET";
    echo json_encode(array("message" => $msg));
    exit(0);
}


foreach ($_POST as $value)
{
    if (!is_string($value))
    {
        echo json_encode(array("message" => "bad request"));
        exit(0);
    }
}


function sendToWorker($message)
{
    $client = new rabbitMQClient("githappensRabbitMQ.ini", "testServer");
    $reply = $client->send_request($message);
    if (!is_array($reply) || !isset($reply["success"]))
    {
        return array("success" => false, "message" => "worker did not answer, try again");
    }
    return $reply;
}


$request = $_POST;
$response = array("message" => "unsupported request type");
$type = "";
if (isset($request["type"]))
{
    $type = $request["type"];
}


switch ($type)
{
    case "register":
        $message = array(
            "type" => "register",
            "username" => trim($request["username"] ?? ""),
            "email" => trim($request["email"] ?? ""),
            "password" => $request["password"] ?? ""
        );
        $reply = sendToWorker($message);
        $response = array("success" => ($reply["success"] === true), "message" => $reply["message"] ?? "Registration failed");
    break;


    case "login":
        $message = array(
            "type" => "login",
            "username" => trim($request["username"] ?? ""),
            "password" => $request["password"] ?? ""
        );
        $reply = sendToWorker($message);
        if ($reply["success"] === true && isset($reply["user"]["username"]))
        {
            // new session id after login
            session_regenerate_id(true);
            $_SESSION["username"] = $reply["user"]["username"];
            $response = array("success" => true, "message" => "Login successful, welcome " . $_SESSION["username"], "username" => $_SESSION["username"]);
        }
        else
        {
            $response = array("success" => false, "message" => $reply["message"] ?? "Login failed");
        }
    break;


    case "logout":
        $_SESSION = array();
        session_destroy();
        $response = array("success" => true, "message" => "Logged out");
    break;


    case "status":
        $response = array("success" => true, "loggedIn" => isset($_SESSION["username"]), "username" => $_SESSION["username"] ?? null);
    break;


    case "protected":
        if (isset($_SESSION["username"]))
        {
            $response = array("message" => "Members only: hello " . $_SESSION["username"] . ", you are logged in");
        }
        else
        {
            http_response_code(401);
            $response = array("message" => "Log in first");
        }
    break;
}


echo json_encode($response);
exit(0);


?>
