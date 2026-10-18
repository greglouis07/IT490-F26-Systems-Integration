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

    //aleens frontend commit 10/14
    if (!is_string($username) || !is_string($password) ||
        $username == "" || strlen($username) > 30 ||
        $password == "" || strlen($password) > 4096)
    {
        return array(
            "success" => false,
            "message" => "Invalid username or password",
            "code" => 3
        );
    }
    
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
            "message" => "Database query failed",
            "code" => 9
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

//glb9 hash checking
    if (!password_verify($password, $user['password']))
    {
        //glb9 avoid close after correct password
        $mysqli->close();

        return array(
            "success" => false,
            "message" => "Invalid username or password",
            "code" => 3
        );
    }

    //glb9 create backend session 1 hour expiration
    $sessionId = bin2hex(random_bytes(32));

    $stmt = $mysqli->prepare(
        "INSERT INTO sessions (sessionId, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
    );
    $stmt->bind_param("si", $sessionId, $user['id']);
    $stmt->execute();

    $stmt->close();
    $mysqli->close();

    return array(
        "success" => true,
        "message" => "Login successful",
        "sessionId" => $sessionId,
        "user" => array(
            "id" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email']
        )
    );
}

//glb9 registration, validation
function doRegister($username, $email, $password)
{
    global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT;

    if (!is_string($username) || !is_string($email) || !is_string($password))
    {
        return array(
            "success" => false,
            "message" => "Invalid registration information",
            "code" => 2
        );
    }
    
    $username = trim($username);
    $email = trim($email);

    if (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $username))
    {
        return array(
            "success" => false,
            "message" => "Username must be 3-30 letters, numbers, or _, -",
            "code" => 2
        );  
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) >255)
    {
        return array(
            "success" => false,
            "message" => "Invalid email address",
            "code" => 2
        );
    }

    if (strlen($password) < 8 || strlen($password) > 72)
    {
        return array(
            "success" => false,
            "message" => "Password must be atleast 8 characters",
            "code" => 2
        );
    }

    //glb9 never store the actual password ONLY the hash
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    echo "connecting to database at $DB_HOST..." . PHP_EOL;

    $mysqli = new mysqli(
        $DB_HOST,
        $DB_USER,
        $DB_PASSWORD,
        $DB_NAME,
        $DB_PORT
    );

    try
    {
        $stmt = $mysqli->prepare(
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sss", $username, $email, $passwordHash);
        $stmt->execute();
        $stmt->close();
    }
    catch (mysqli_sql_exception $e)
    {
        $mysqli->close();

        //glb9 1062 = duplicate username or email already taken
        if ($e->getCode() == 1062)
        {
            return array(
                "success" => false,
                "message" => "Username or email already exists",
                "code" => 4
            );
        }
        throw $e; // anything else falls into the requestProcessor catch
    }

    $mysqli->close();
    echo "Registered user: $username" . PHP_EOL;

    return array(
        "success" => true,
        "message" => "Registration successful"
    );
}

//glb9 check session expiration
function doValidate($sessionId)
{
    global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT;

    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);

    $stmt = $mysqli->prepare(
        "SELECT users.username
        FROM sessions
        JOIN users ON users.id = sessions.user_id
        WHERE sessions.sessionId = ? AND sessions.expires_at > NOW()"
    );
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();
    $mysqli->close();

    if (!$row)
    {
        return array(
            "success" => false,
            "message" => "Invalid or expired session",
            "code" => 5
        );
    }
    
    return array(
        "success" => true,
        "message" => "Session is valid",
        "username" => $row['username']
    );
}

//glb9 logout 
function doLogout($sessionId)
{
    global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT;

    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);
 
    $stmt = $mysqli->prepare("DELETE FROM sessions WHERE sessionId = ?");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();

    $deleted = $stmt->affected_rows;

    $stmt->close();
    $mysqli->close();

    if ($deleted === 0)
    {
        return array(
            "success" => false,
            "message" => "Invalid or expired session",
            "code" => 5
        );
    }

    return array(
        "success" => true,
        "message" => "Logged out"
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
        //glb9
        case "register":
            if (empty($request['username']) || empty($request['email']) || empty($request['password']))
            {
                return array(
                    "success" => false,
                    "message" => "Username, email and password are required",
                    "code" => 2 
                );
            }
            $response = doRegister(
                $request['username'],
                $request['email'],
                $request['password']
            );
            echo "sending reply" . PHP_EOL;
            var_dump($response);
            return $response;

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
        case "logout":
            if (empty($request['sessionId']))
            {
                return array(
                    "success" => false,
                    "message" => "sessionId is required",
                    "code" => 2
                );
            }
            return doLogout($request['sessionId']);
        
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
    "databaseServer"
);

echo "rabbitMQServer BEGIN" . PHP_EOL;

$server->process_requests('requestProcessor');

echo "rabbitMQServer END" . PHP_EOL;

exit();
?>
