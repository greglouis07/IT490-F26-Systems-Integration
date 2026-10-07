#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

//function doLogin($username,$password)
//{
    // lookup username in databas
    // check password
//    return true;
    //return false if not valid
//}

function requestProcessor($request)
{
  echo "received request: " . ($request['type'] ?? '(no type)') . PHP_EOL;
  
  if (!isset($request['type'])) {
  return ['success' => true, 'message' => 'missing request type', 'code' => 1];
}
switch ($request['type']) {
 case 'register':
   return ['success' => true, 'message' => 'stub: registered'];
 case 'login':
   return ['success' => true, 'message' => 'stub: logged in', 'sessionID' => 'test123'];
case 'validate_session':
  return ['success' => true, 'message' => 'stub: valid', 'username' => 'testuser'];
case 'logout':
  return['success' => true, 'message' => 'stub: logged out'];
default:
  return ['success' => false, 'message' => 'unknown request type', 'code' => 1];   
} 

$server = new rabbitMQServer("githappensRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>


