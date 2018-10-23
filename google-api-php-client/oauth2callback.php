<?php
/**
 * Read google sheet
 * https://developers.google.com/api-client-library/php/auth/web-app
 * https://console.developers.google.com/apis/credentials?project=articles-validat-1538990606174&organizationId=1024736607261
 */
require_once __DIR__.'/vendor/autoload.php';

// Config variables
$tokenPath = 'token.json';
$response = ['error' => false];
$baseUri = 'https://sunmax.ourgoogle.in/clients/kosta-hristov/avs/';

$client = new Google_Client();
$client->setAuthConfigFile('client_secrets.json');
$client->setRedirectUri($baseUri.'google-api-php-client/oauth2callback.php');
$client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);

if( isset($_GET['code']) ) {
  $client->authenticate($_GET['code']);
  mkdir(dirname($tokenPath), 0700, true);
  file_put_contents($tokenPath, json_encode($client->getAccessToken()));

  header('Location: ' . filter_var($baseUri.'articles-submission-system', FILTER_SANITIZE_URL));
} else {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
}