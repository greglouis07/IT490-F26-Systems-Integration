#!/usr/bin/php
<?php
//glb9 price alerts, database runs this (aleen) choice of cron or on request
//asks DMZ for watched card's price, saves it to cards
//DMZ email the user when price <= target_price

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../shared');
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

$db = parse_ini_file(__DIR__ . '/../shared/githappensDatabase.ini', true);
if ($db == false || !isset($db['database']))
{
   die("Missing or invalid githappensDatabase.ini" . PHP_EOL);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(
   $db['database']['DB_HOST'],
   $db['database']['DB_USER'],
   $db['database']['DB_PASSWORD'],
   $db['database']['DB_NAME'],
   (int) $db['database']['DB_PORT']
);

//timeout seconds
ini_set("amqp.read_timeout", "10");

function askDmz($request)
{
   $client = new rabbitMQClient("githappensRabbitMQ.ini", "dmzServer");
   return $client->send_request($request);
}

//pull market_price
function pickPrice($data, $printing)
{
   if (!is_array($data)) return null;
   if (isset($data['market_price'])) return (float) $data['market_price'];

   $first = null;
   foreach ($data as $row)
   {
       if (!is_array($row) || !isset($row['market_price'])) continue;
       if ($first === null) $first = (float) $row['market_price'];
       if ($printing !== null && isset($row['printing']) && $row['printing'] === $printing)
           return (float) $row['market_price'];
   }
   return $first;
}

//untriggered alerts
$result = $mysqli->query(
   "SELECT w.id AS watchId, w.card_id, w.target_price, u.email, c.name, c.printing
    FROM watchlist w
    JOIN users u ON u.id = w.user_id
    JOIN cards c ON c.id = w.card_id
    WHERE w.alerted_at IS NULL"
);
$watches = $result->fetch_all(MYSQLI_ASSOC);
echo "watches to check: " . count($watches) . PHP_EOL;

//only one request per card across users
$updatePrice = $mysqli->prepare("UPDATE cards SET market_price = ?, market_price_as_of = CURDATE() WHERE id = ?");
$markAlerted = $mysqli->prepare("UPDATE watchlist SET alerted_at = NOW() WHERE id = ?");
$prices = array();

foreach ($watches as $watch)
{
   $cardId = (int) $watch['card_id'];

   if (!array_key_exists($cardId, $prices))
   {
       $response = askDmz(array("type" => "get_card_prices", "cardId" => $cardId));
       $price = (is_array($response) && !empty($response['success']))
           ? pickPrice($response['data'] ?? null, $watch['printing'])
           : null;
       $prices[$cardId] = $price;

       if ($price === null)
       {
           echo "card $cardId: no price from DMZ" . PHP_EOL;
           continue;
       }
       $updatePrice->bind_param("di", $price, $cardId);
       $updatePrice->execute();
   }

   $price = $prices[$cardId];
   if ($price === null) continue;

   $target = (float) $watch['target_price'];
   echo "card $cardId ({$watch['name']}): price $price, target $target" . PHP_EOL;
   if ($price > $target) continue;

   $email = askDmz(array(
       "type" => "send_email",
       "toEmail" => $watch['email'],
       "subject" => "Git Happens price alert: " . $watch['name'],
       "body" => $watch['name'] . " is now $" . number_format($price, 2)
           . ", at or below your target of $" . number_format($target, 2) . "."
   ));

   //confirm email alert was sent
   if (is_array($email) && !empty($email['success']))
   {
       $watchId = (int) $watch['watchId'];
       $markAlerted->bind_param("i", $watchId);
       $markAlerted->execute();
       echo "alert sent for watch $watchId" . PHP_EOL;
   }
   else
   {
       echo "email failed for watch {$watch['watchId']}: " . ($email['message'] ?? "no reply") . PHP_EOL;
   }
}

$updatePrice->close();
$markAlerted->close();
$mysqli->close();
echo "price alert job done" . PHP_EOL;
?>