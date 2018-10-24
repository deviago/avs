<?php
/**
 * Include styles and scripts
 */
function avs_enqueue_styles_n_scripts() {
  wp_enqueue_style( 'avs-parent-style', get_template_directory_uri() . '/style.css' );

  wp_enqueue_script( 'wp-util' );
  wp_enqueue_script( 'avs-fusejs', get_theme_file_uri('avs/assets/js/fuse.min.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-uiwidgetjs', get_theme_file_uri('avs/assets/js/jquery.ui.widget.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-iframtransportjs', get_theme_file_uri('avs/assets/js/jquery.iframe-transport.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-fileuploadjs', get_theme_file_uri('avs/assets/js/jquery.fileupload.js'), array('jquery'), '1.0', false );
}
add_action( 'wp_enqueue_scripts', 'avs_enqueue_styles_n_scripts' );

/**
 * Get user display name
 */
function avs_get_display_name($user_id = null){
  $display_name = '';
  $user_id = ($user_id == null) ? get_current_user_id() : $user_id;
	$user_data = get_userdata($user_id);
  
	if( isset($user_data->display_name) ){
		$display_name = $user_data->display_name;
	}else if( $user_data->first_name || $user_data->last_name ){
		$display_name = $user_data->first_name.' '.$user_data->last_name;
	}else{
		$display_name = $user_data->user_login;
	}

	return htmlentities( ucwords( trim($display_name, ' ') ) );
}

/**
 * Read google sheet
 * https://developers.google.com/api-client-library/php/auth/web-app
 * https://console.developers.google.com/apis/credentials?project=articles-validat-1538990606174&organizationId=1024736607261
 */
function avs_get_google_sheet_data() {
  require_once WP_THEME_PATH.'/avs/google-api-php-client/vendor/autoload.php';

  $client = new Google_Client();
  $client->setAuthConfig(WP_THEME_PATH.'/avs/google-api-php-client/client_secrets.json');
  $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);

  $response = ['error' => false];
  $tokenPath = WP_THEME_PATH.'/avs/google-api-php-client/token.json';

  if ( file_exists($tokenPath) ) { // Check if token exist
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    // If previous token expired
    if ($client->isAccessTokenExpired()) {
      header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
      exit();
    }else{
      $service = new Google_Service_Sheets($client);
      $spreadsheetId = '1K02_5cn9TtjhybnBQYYVRmzjl0sYPnIu_Ry0PSEKsOk'; // https://docs.google.com/spreadsheets/d/1K02_5cn9TtjhybnBQYYVRmzjl0sYPnIu_Ry0PSEKsOk
      $range = 'Orders!A2:R';
      $gResponse = $service->spreadsheets_values->get($spreadsheetId, $range);
      $response['listings'] = json_encode($gResponse->getValues());
    }
  } else {
    header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
    exit();
  }

  return $response;
}

/**
 * Update google sheet access token
 */
function avs_update_google_sheet_access_token(){
  require_once WP_THEME_PATH.'/avs/google-api-php-client/vendor/autoload.php';
  
  $tokenPath = WP_THEME_PATH.'/avs/google-api-php-client/token.json';

  $client = new Google_Client();
  $client->setAuthConfigFile(WP_THEME_PATH.'/avs/google-api-php-client/client_secrets.json');
  $client->setRedirectUri(WP_SITE_URL.'/wp-admin/admin-ajax.php?action=avs_google_sheet_access_token');
  $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);

  if( isset($_GET['code']) ) {
    $client->authenticate($_GET['code']);
    mkdir(dirname($tokenPath), 0700, true);
    $client->getAccessToken();
    file_put_contents($tokenPath, json_encode($client->getAccessToken()));

    header('Location: ' . filter_var(WP_SITE_URL.'/articles-submission-system', FILTER_SANITIZE_URL));
  } else {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
  }
  exit();
}
add_action( 'wp_ajax_avs_google_sheet_access_token', 'avs_update_google_sheet_access_token' );