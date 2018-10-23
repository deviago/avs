<?php
/**
 * Read google sheet
 * https://developers.google.com/api-client-library/php/auth/web-app
 * https://console.developers.google.com/apis/credentials?project=articles-validat-1538990606174&organizationId=1024736607261
 */
header('Content-Type: application/json');

require_once __DIR__.'/vendor/autoload.php';
$client = new Google_Client();
$client->setAuthConfig('client_secrets.json');
$client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);

// Config variables
$tokenPath = 'token.json';
$response = ['error' => false];

if (file_exists($tokenPath)) { // Check if token exist
  $accessToken = json_decode(file_get_contents($tokenPath), true);
  $client->setAccessToken($accessToken);

  // If previous token expired
  if ($client->isAccessTokenExpired()) {
    $authUrl = filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL);
    $response['authUrl'] = $authUrl;
    $response['error'] = 'Token has expired.';
  }else{
    $service = new Google_Service_Sheets($client);
    $spreadsheetId = '1K02_5cn9TtjhybnBQYYVRmzjl0sYPnIu_Ry0PSEKsOk'; // https://docs.google.com/spreadsheets/d/1K02_5cn9TtjhybnBQYYVRmzjl0sYPnIu_Ry0PSEKsOk
    $range = 'Orders!A2:R';
    $gResponse = $service->spreadsheets_values->get($spreadsheetId, $range);
    $response['listings'] = json_encode($gResponse->getValues());

    // echo '<pre>';
    // print_r($gResponse->getValues());
    // echo '</pre>';
    // exit();

    /*$firstImplode = array_map( function($arg) {
      return '("'.implode('", "', $arg).'")';
    }, $gResponse->getValues());
    
    $secondImplode = implode(', ', $firstImplode);

    $con= mysqli_connect("localhost","sunmax_kostaav","lFz9Tmpp8c","sunmax_kostaav");

    echo $query2 = "INSERT INTO doc_record VALUES $secondImplode";
    exit();
    

    //$query= "INSERT INTO doc_record values('ahfaz','101','141518','pending','validate','keyword','mainurl','titlelen','length','image')";
    mysqli_query($con,$query);
    mysqli_close($con);
    exit();
    */
  }
} else {
  $authUrl = filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL);
  $response['authUrl'] = $authUrl;
  $response['error'] = 'Token doesn\'t exist.';
}

echo json_encode($response);
exit();