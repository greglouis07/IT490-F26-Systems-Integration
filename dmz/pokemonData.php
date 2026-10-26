<?php
//Function
function getCardPrices($cardId)
{
$ini_path = 
__DIR__ ."/pokemonApi.ini";
if(!file_exists($ini_path)) {
	return ['success' => false, 'message' => "No configuration file found", 'code' => 2];
}

$apiConfig = parse_ini_file($ini_path, true);

$apiUrl = $apiConfig['pokemon_api']['api_url'];
$apiKey = $apiConfig['pokemon_api']['api_key'];

//Initializing new cURL session
$ch = curl_init();

//Set  cURL URL 
curl_setopt($ch, CURLOPT_URL, $apiUrl . '/cards/' . $cardId . '/prices');

//Setting to have cURL return the response in string instead of a direct output.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//Passing custom headers as php array x auth token lives here
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	"X-API-Key: " . $apiKey
]);


//Set a timer so script doesnt hang 
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$apiResponse = curl_exec($ch);

//grabbing http code (200, 400, etc)
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


if($httpCode !==200)
{
	return ['success' => false, 'message' => "API request failed with HTTP code $httpCode", 'code' => 3];
}
//check if any error occured print error code and error message
if(curl_errno($ch))
{
	$errorCode = curl_errno($ch);
	$errorMsg = curl_error($ch);
	curl_close($ch);
	return ['success' => false, 'message' => "cURL Error [$errorCode]: $errorMsg", 'code' => 3];
}

//close

curl_close($ch);

//decode the json string

$data = json_decode($apiResponse, true);

if(json_last_error() !==JSON_ERROR_NONE) 
{
	return ['success' => false, 'message' => "JSON Decode Error: " . json_last_error_msg(), 'code' => 4];
}
//output success summary
return ['success' => true, 'message' => 'OK', 'code' => 0, 'data' => $data['data']];
}

//glb9 card_search checks card name with TCG API
function cardSearch($cardName)
{
	$ini_path = __DIR__ . "/pokemonApi.ini";
	if(!file_exists($ini_path)) {
		return ['success' => false,
		'message' => "No config file",
		'code' => 9
		];
	}

	$apiConfig = parse_ini_file($ini_path, true);

	$api_Url = $apiConfig['pokemon_api']['api_url'];
	$api_Key = $apiConfig['pokemon_api']['api_key'];

	//glb9 search URL /search?q=name&game=pokemon
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_Url . '/search?q=' . urlencode($cardName) . '&game=pokemon');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: " . $api_Key]);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	
	$apiResponse = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	//glb9 temporary test
	return ['success' => false, 'message' => "cardSearch HTTP $httpCode"];
	
}

?>
