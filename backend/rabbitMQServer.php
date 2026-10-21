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
 
function dbConnect()
{
   global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT;
   return new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);
}
 
function sessionUser($mysqli, $sessionId)
{
   if (!is_string($sessionId) || $sessionId === "") return null;
 
   $stmt = $mysqli->prepare(
       "SELECT users.id, users.username
        FROM sessions
        JOIN users ON users.id = sessions.user_id
        WHERE sessions.sessionId = ? AND sessions.expires_at > NOW()"
   );
   $stmt->bind_param("s", $sessionId);
   $stmt->execute();
   $row = $stmt->get_result()->fetch_assoc();
   $stmt->close();
   return $row ?: null;
}
 
function badSession()
{
   return array("success" => false, "message" => "Invalid or expired session", "code" => 5);
}
 
function doListPosts($sessionId)
{
   $mysqli = dbConnect();
   if (!sessionUser($mysqli, $sessionId)) { $mysqli->close(); return badSession(); }
 
   $result = $mysqli->query(
       "SELECT p.id, p.title, p.body, p.created_at, u.username,
               (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) AS reply_count
        FROM posts p
        JOIN users u ON u.id = p.user_id
        ORDER BY p.created_at DESC
        LIMIT 100"
   );
   $posts = $result->fetch_all(MYSQLI_ASSOC);
   $mysqli->close();
 
   return array("success" => true, "posts" => $posts);
}
 
function doGetPost($sessionId, $postId)
{
   $mysqli = dbConnect();
   if (!sessionUser($mysqli, $sessionId)) { $mysqli->close(); return badSession(); }
 
   $postId = (int) $postId;
 
   $stmt = $mysqli->prepare(
       "SELECT p.id, p.title, p.body, p.created_at, u.username
        FROM posts p JOIN users u ON u.id = p.user_id
        WHERE p.id = ?"
   );
   $stmt->bind_param("i", $postId);
   $stmt->execute();
   $post = $stmt->get_result()->fetch_assoc();
   $stmt->close();
 
   if (!$post)
   {
       $mysqli->close();
       return array("success" => false, "message" => "Post not found", "code" => 6);
   }
 
   $stmt = $mysqli->prepare(
       "SELECT r.id, r.body, r.created_at, u.username
        FROM replies r JOIN users u ON u.id = r.user_id
        WHERE r.post_id = ?
        ORDER BY r.created_at ASC"
   );
   $stmt->bind_param("i", $postId);
   $stmt->execute();
   $replies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
   $stmt->close();
   $mysqli->close();
 
   return array("success" => true, "post" => $post, "replies" => $replies);
}
 
function doCreatePost($sessionId, $title, $body)
{
   if (!is_string($title) || !is_string($body))
       return array("success" => false, "message" => "Invalid post", "code" => 2);
 
   $title = trim($title);
   $body  = trim($body);
 
   if ($title === "" || mb_strlen($title) > 150)
       return array("success" => false, "message" => "Title must be 1-150 characters", "code" => 2);
   if ($body === "" || mb_strlen($body) > 5000)
       return array("success" => false, "message" => "Description must be 1-5000 characters", "code" => 2);
 
   $mysqli = dbConnect();
   $user = sessionUser($mysqli, $sessionId);
   if (!$user) { $mysqli->close(); return badSession(); }
 
   $stmt = $mysqli->prepare("INSERT INTO posts (user_id, title, body) VALUES (?, ?, ?)");
   $stmt->bind_param("iss", $user['id'], $title, $body);
   $stmt->execute();
   $newId = $mysqli->insert_id;
   $stmt->close();
   $mysqli->close();
 
   return array("success" => true, "message" => "Post created", "postId" => $newId);
}
 
function doCreateReply($sessionId, $postId, $body)
{
   if (!is_string($body))
       return array("success" => false, "message" => "Invalid reply", "code" => 2);
 
   $body = trim($body);
   if ($body === "" || mb_strlen($body) > 5000)
       return array("success" => false, "message" => "Reply must be 1-5000 characters", "code" => 2);
 
   $mysqli = dbConnect();
   $user = sessionUser($mysqli, $sessionId);
   if (!$user) { $mysqli->close(); return badSession(); }
 
   $postId = (int) $postId;
 
   $stmt = $mysqli->prepare("SELECT id FROM posts WHERE id = ?");
   $stmt->bind_param("i", $postId);
   $stmt->execute();
   $exists = $stmt->get_result()->fetch_assoc();
   $stmt->close();
 
   if (!$exists)
   {
       $mysqli->close();
       return array("success" => false, "message" => "Post not found", "code" => 6);
   }
 
   $stmt = $mysqli->prepare("INSERT INTO replies (post_id, user_id, body) VALUES (?, ?, ?)");
   $stmt->bind_param("iis", $postId, $user['id'], $body);
   $stmt->execute();
   $stmt->close();
   $mysqli->close();
 
   return array("success" => true, "message" => "Reply posted");
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
 
       case "list_posts":
           return doListPosts($request['sessionId'] ?? "");
 
       case "get_post":
           return doGetPost($request['sessionId'] ?? "", $request['postId'] ?? 0);
 
       case "create_post":
           return doCreatePost(
               $request['sessionId'] ?? "",
               $request['title'] ?? "",
               $request['body'] ?? ""
           );
 
       case "create_reply":
           return doCreateReply(
               $request['sessionId'] ?? "",
               $request['postId'] ?? 0,
               $request['body'] ?? ""
           );
      
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
