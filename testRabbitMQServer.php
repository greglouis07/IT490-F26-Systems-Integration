#!/usr/bin/php
<?php

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// grab .env
// .env needs to be in the same directory as this file
$env = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);

if ($env === false) {
    error_log("Could not load .env file");
    exit(1);
}

$DB_HOST = $env['DB_HOST'];
$DB_USER = $env['DB_USER'];
$DB_PASSWORD = $env['DB_PASSWORD'];
$DB_NAME = $env['DB_NAME'];
$DB_PORT = (int) $env['DB_PORT'];


// connect to db
function connectDB()
{
    global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $mysqli = new mysqli(
        $DB_HOST,
        $DB_USER,
        $DB_PASSWORD,
        $DB_NAME,
        $DB_PORT
    );

    $mysqli->set_charset("utf8mb4");

    return $mysqli;
}


// user registration
function doRegister($username, $email, $password)
{
    if (
        !is_string($username) ||
        !is_string($email) ||
        !is_string($password)
    ) {
        return [
            "success" => false,
            "message" => "Invalid registration information"
        ];
    }

    $username = trim($username);
    $email = trim($email);

    if (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $username)) {
        return [
            "success" => false,
            "message" => "Username must be 3-30 characters"
        ];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 255) {
        return [
            "success" => false,
            "message" => "Invalid email address"
        ];
    }

    if (strlen($password) < 8 || strlen($password) > 4096) {
        return [
            "success" => false,
            "message" => "Password must be at least 8 characters"
        ];
    }

    // hashing password here
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $mysqli = connectDB();

        $stmt = $mysqli->prepare(
            "INSERT INTO users (username, email, password)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "sss",
            $username,
            $email,
            $passwordHash
        );

        $stmt->execute();

        $stmt->close();
        $mysqli->close();

        return [
            "success" => true,
            "message" => "Registration successful"
        ];

    } catch (mysqli_sql_exception $e) {
        if (isset($mysqli) && $mysqli instanceof mysqli) {
            $mysqli->close();
        }

        // checks duplicate usernames or other db errors
        if ($e->getCode() == 1062) {
            return [
                "success" => false,
                "message" => "Username or email already exists"
            ];
        }

        error_log("Registration database error");

        return [
            "success" => false,
            "message" => "Registration failed"
        ];
    }
}


// login for existing user
function doLogin($username, $password)
{
    if (!is_string($username) || !is_string($password)) {
        return [
            "success" => false,
            "message" => "Invalid username or password"
        ];
    }

    if ($username === "" || strlen($username) > 30 ||
        $password === "" || strlen($password) > 4096) {
        return [
            "success" => false,
            "message" => "Invalid username or password"
        ];
    }

    try {
        $mysqli = connectDB();

        $stmt = $mysqli->prepare(
            "SELECT id, username, email, password
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $stmt->bind_result($id, $dbUsername, $email, $passwordHash);

        $userFound = $stmt->fetch();

        $stmt->close();
        $mysqli->close();

        // check the password against the stored hash
        if (!$userFound || !password_verify($password, $passwordHash)) {
            return [
                "success" => false,
                "message" => "Invalid username or password"
            ];
        }

        return [
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $id,
                "username" => $dbUsername,
                "email" => $email
            ]
        ];

    } catch (mysqli_sql_exception $e) {
        if (isset($mysqli) && $mysqli instanceof mysqli) {
            $mysqli->close();
        }

        error_log("Login database error");

        return [
            "success" => false,
            "message" => "Login failed"
        ];
    }
}


// process requests received through rabbitmq
function requestProcessor($request)
{
    if (!is_array($request) || !isset($request['type'])) {
        return [
            "success" => false,
            "message" => "Invalid request"
        ];
    }

    switch ($request['type']) {

        case "register":
            if (!isset(
                $request['username'],
                $request['email'],
                $request['password']
            )) {
                return [
                    "success" => false,
                    "message" => "Missing registration information"
                ];
            }

            return doRegister(
                $request['username'],
                $request['email'],
                $request['password']
            );

        case "login":
            if (!isset($request['username'], $request['password'])) {
                return [
                    "success" => false,
                    "message" => "Missing login information"
                ];
            }

            return doLogin(
                $request['username'],
                $request['password']
            );

        default:
            return [
                "success" => false,
                "message" => "Unsupported request type"
            ];
    }
}

$server = new rabbitMQServer("githappensRabbitMQ.ini","testServer");

echo "RabbitMQ database worker started" . PHP_EOL;

$server->process_requests('requestProcessor');

exit();
?>
