#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$DB_HOST = "100.67.127.28";
$DB_USER = "db";
$DB_PASSWORD = "githappensdb";
$DB_NAME = "db";
$DB_PORT = 3306;


function doLogin($username, $password)
{
    // lookup username in database
    // check password
    // return true;
    //return false if not valid
    global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT;

    $mysqli = new mysqli(
        $DB_HOST,
        $DB_USER,
        $DB_PASSWORD,
        $DB_NAME,
        $DB_PORT
    );

    if ($mysqli->connect_error)
    {
        return array(
            "success" => false,
            "message" => "Database connection failed: " . $mysqli->connect_error
        );
    }

    $stmt = $mysqli->prepare(
        "SELECT id, username, email, password
         FROM users
         WHERE username = ?"
    );

    if (!$stmt)
    {
        $mysqli->close();

        return array(
            "success" => false,
            "message" => "Database query failed"
        );
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0)
    {
        $stmt->close();
        $mysqli->close();

        return array(
            "success" => false,
            "message" => "User not found"
        );
    }

    $user = $result->fetch_assoc();

    $stmt->close();
    $mysqli->close();

    if ($password !== $user['password'])
    {
        return array(
            "success" => false,
            "message" => "Invalid password"
        );
    }

    return array(
        "success" => true,
        "message" => "Login successful",
        "user" => array(
            "id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email']
        )
    );
}


function requestProcessor($request)
{
    echo "received request" . PHP_EOL;
    var_dump($request);

    if (!isset($request['type']))
    {
        return "ERROR: unsupported message type";
    }

    switch ($request['type'])
    {
        case "login":
            return doLogin(
                $request['username'],
                $request['password']
            );

        case "validate_session":
            return doValidate($request['sessionId']);
    }

    return array(
        "returnCode" => '0',
        "message" => "Server received request and processed"
    );
}


$server = new rabbitMQServer(
    "githappensRabbitMQ.ini",
    "testServer"
);

echo "testRabbitMQServer BEGIN" . PHP_EOL;

$server->process_requests('requestProcessor');

echo "testRabbitMQServer END" . PHP_EOL;

exit();
?>
