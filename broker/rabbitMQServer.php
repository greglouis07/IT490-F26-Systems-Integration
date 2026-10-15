#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

//glb9 moved database login to shared/githappensDatabase.ini
$db = parse_ini_file(__DIR__ . '/../shared/githappensDatabase.ini', true);
if ($db == false || !isset($db['database']))
    {
        die("Missing or invalid githappensDatabase.ini" . PHP_EOL);
    }
    $DB_HOST = $db['database']["DB_HOST"];
    $DB_USER = $db['database']['DB_USER'];
    $DB_PASSWORD = $db['database']['DB_PASSWORD'];
    $DB_NAME = $db['database']['DB_NAME'];
    $DB_PORT = (int) $db['database']['DB_PORT'];


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

    //glb9 removing this if statement because this error will catch further down its unneeded
    //if ($mysqli->connect_error)
    //{
    //    return array(
    //        "success" => false,
    //        "message" => "Database connection failed: " . $mysqli->connect_error
    //    );
    //}

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
            "message" => "Invalid username or password",
            "code" => 3
        );
    }

    $user = $result->fetch_assoc();

    $stmt->close();
    $mysqli->close();

    if ($password !== $user['password'])
    {
        return array(
            "success" => false,
            "message" => "Invalid username or password",
            "code" => 3
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
//glb9 removed password from view 
//avoid errors from strings using array 
    if (!is_array($request) || !isset($request['type']))
    {
        return array(
            "success" => false,
            "message" => "Missing message type",
            "code" => 1
        );
    }

//glb echo test for client response
    echo "processing type: " . $request['type'] . PHP_EOL;

    try
    
    {

    switch ($request['type'])
    {
        case "login":
            //glb9 
            if (empty($request['username']) || empty($request['password']))
                {
                    return array(
                       "success" => false,
                       "message" => "Username and password are required",
                       "code" => 2 
                    );
                }
            $response = doLogin(
                $request['username'],
                $request['password']
            );
            echo "sending reply:" . PHP_EOL;
            var_dump($response);
            return $response;

        case "validate_session":
            //glb9
            if (empty($request['sessionId']))
            {
                return array(
                    "success" => false,
                    "message" => "sessionId is required",
                    "code" => 2
                );
            }
            return doValidate($request['sessionId']);
        
        //glb9
        default:
            return array(
                "success" =>false,
                "message" => "Unknown request type",
                "code" => 1
            );
    }
}
catch (Throwable $e)
{
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    return array(
        "success" => false,
        "message" => "Server error",
        "code" => 9
    );
}
//end edit
//glb9 this never gets called old logic
//    return array(
//        "returnCode" => '0',
//        "message" => "Server received request and processed"
//    );
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
