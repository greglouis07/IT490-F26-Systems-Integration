#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$DB_HOST = "100.67.127.28";
$DB_USER = "admin";
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
    
//glb9 echo add ons
    echo "connecting to database at $DB_HOST..." . PHP_EOL;
    
    $mysqli = new mysqli(
        $DB_HOST,
        $DB_USER,
        $DB_PASSWORD,
        $DB_NAME,
        $DB_PORT
    );

    echo  "connected" . PHP_EOL;

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

    echo "query complete" . PHP_EOL;

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

//glb echo test for client response
    echo "processing type: " . $request['type'] . PHP_EOL;

    try
    
    {

    switch ($request['type'])
    {
        case "login":
            $response = doLogin(
                $request['username'],
                $request['password']
            );
            echo "sending reply:" . PHP_EOL;
            var_dump($response);
            return $response;

        case "validate_session":
            return doValidate($request['sessionId']);
    }
}
catch (Throwable $e)
{
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    return array("success" => false, "message" => "Server error");
}
//end edit

    return array(
        "returnCode" => '0',
        "message" => "Server received request and processed"
    );
}


$server = new rabbitMQServer(
    "githappensRabbitMQ.ini",
    "testServer"
);

echo "rabbitMQServer BEGIN" . PHP_EOL;

$server->process_requests('requestProcessor');

echo "rabbitMQServer END" . PHP_EOL;

exit();
?>
