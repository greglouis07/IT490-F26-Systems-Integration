<?php
$ini_path = 
__DIR__ ."/pokemonApi.ini";
if(!file_exists($ini_path)) {
	die("ERROR , No Configuration file $ini_path found (.ini file).\n");
}

$apiConfig = parse_ini_file($ini_path, true);

$apiUrl = $apiConfig['pokemon_api']['api_url'];
$apiKey = $apiConfig['pokemon_api']['api_key'];

//Initializing new cURL session
$ch = curl_init();

//Set  cURL URL 
curl_setopt($ch, CURLOPT_URL, $apiUrl);

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
	die("API request failed with HTTP code $httpCode,\n $apiResponse\n");
}
//check if any error occured print error code and error message
if(curl_errno($ch))
{
	$errorCode = curl_errno($ch);
	$errorMsg = curl_error($ch);
	curl_close($ch);
	die("cURL Error [$errorCode]: " . $errorMsg . "\n");
}

//close

curl_close($ch);

//decode the json string

$data = json_decode($apiResponse, true);

if(json_last_error() !==JSON_ERROR_NONE) 
{
	die("JSON Decode Error: " . json_last_error_msg() . "\n");
}
//output success summary
echo "Succesfully fetched" . count($data['data'] ?? []) . "cards .\n";

?>
