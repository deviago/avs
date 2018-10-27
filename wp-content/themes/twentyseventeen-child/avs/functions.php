<?php
/**
 * Include styles and scripts
 */
function avs_enqueue_styles_n_scripts() {
  wp_enqueue_style( 'avs-parent-style', get_template_directory_uri() . '/style.css' );

  wp_enqueue_script( 'wp-util' );  
  wp_enqueue_script( 'avs-uiwidgetjs', get_theme_file_uri('avs/assets/js/jquery.ui.widget.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-iframtransportjs', get_theme_file_uri('avs/assets/js/jquery.iframe-transport.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-fileuploadjs', get_theme_file_uri('avs/assets/js/jquery.fileupload.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-fusejs', get_theme_file_uri('avs/assets/js/fuse.min.js'), array('jquery'), '1.0', false );
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

/**
 * Process document for validation
 */
function avs_process_document(){
  if( isset($_REQUEST['file_name']) ){
    $file_name = $_REQUEST['file_name'];
    $file_path = WP_UPLOAD_DIR.'/articles-docs/files/'.$file_name;
    
    $images = avs_doc_image_count($file_path);
    $title = avs_doc_file_title_opertion($file_name, $file_path);
    $length = avs_read_doc_length($file_path);

    $results = array_merge($images, $title, $length);
    echo json_encode($results);
  }
  exit();
}
add_action( 'wp_ajax_avs_process_document', 'avs_process_document' );

/*
 * Get number of image count 
 * @param string $file_path File path
 * @return array
 */
function avs_doc_image_count($file_path) {
  $zip = new ZipArchive;
  $flag = 0; // set flag to get count of image
  $response = array();

  if (true === $zip->open($file_path)) { // Open the received archive file
    for ($i=0; $i<$zip->numFiles;$i++) {
      $zip_element = $zip->statIndex($i); // Loop via all the files to check for image files
      if(preg_match("([^\s]+(\.(?i)(jpg|jpeg|png|gif|bmp))$)",$zip_element['name'])) { // Check for images
        $flag += 1;
      }
    }
  }

  if ($flag <= $zip->numFiles) {
    $response['images'] = 'Number of images: '.$flag;
  }else{
    $response['images'] = 'The number of images in the .doc doesn’t match the column';
  }

  return $response;
}

/*
 * Get file title operation of doc 
 * @param string $filename File name
 * @param string $file_path File path
 * @return array
 */
function avs_doc_file_title_opertion($filename, $file_path){
  $response = array();
    
  $file_name = explode('.', $filename);
  $file_title = explode(' - ', $file_name[0]);
  $splitdata = str_split($file_name[0],12);
    
  $response['title'] = 'File Title: '.$file_title[1];
  $response['uid'] = 'UID: '.$splitdata[0];
  
  if (strlen($file_name[0] > 55)) { // get error when len of char more then 55
    $response['title_length_exceed'] = 'The title should be less than 55 characters';
    $response['title_length'] = 'Article length is: '.strlen($file_name[0]);
  }
 
  $UID =$splitdata[0];  // check UID is exist of not in file name
  if( strpos( $file_name, $UID ) == false) {
    $response['uid_not_present'] = 'UID is not exist in file name';
  }

  return $response;
}
/*
 * To get the length of doc file 
 * @param string $File path File name
 * @return array
 */
function avs_read_doc_length($file_path) {
  $response = array( );
  $response['characters_length'] = 'Length of characters: '.filesize($file_path); 
  $response['filename_length'] = 'Length of File Name: '.strlen($file_name[0]);
  return $response;
}