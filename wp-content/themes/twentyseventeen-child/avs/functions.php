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

  wp_enqueue_script( 'avs-b-popup', get_theme_file_uri('avs/assets/js/jquery.bpopup.min.js'), array('jquery'), '1.0', false );
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
  $client->addScope('https://www.googleapis.com/auth/drive');

  $response = ['error' => false];
  $tokenPath = WP_THEME_PATH.'/avs/google-api-php-client/token-'.get_current_user_id().'.json';

  if ( file_exists($tokenPath) ) { // Check if token exist
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    // If previous token expired
    if ($client->isAccessTokenExpired()) {
      header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
      exit();
    }else{
      $service = new Google_Service_Sheets($client);

      $user = wp_get_current_user();
      $roles = (array) $user->roles;

      $tmpListings = $listings = array();
      $range = 'Orders!A2:R';

      if( in_array('shop_manager', $roles) || in_array('administrator', $roles) ){
				if( isset($_REQUEST['writer']) ) {
					$spreadsheetId = get_user_meta($_REQUEST['writer'], 'avs_googlesheet_id', true);
	        if($spreadsheetId){
	          $gResponse = $service->spreadsheets_values->get($spreadsheetId, $range);
	          $tmpListings[] = $gResponse->getValues();
	        }
				}else{
					$avs_writers = get_users('role=avs_writer');
	        foreach ( $avs_writers as $avs_writer ) {
	          $spreadsheetId = get_user_meta($avs_writer->ID, 'avs_googlesheet_id', true);
	          if($spreadsheetId){
	            $gResponse = $service->spreadsheets_values->get($spreadsheetId, $range);
	            $tmpListings[] = $gResponse->getValues();
	          }
	        }	
				}
      }else{
        $spreadsheetId = get_user_meta(get_current_user_id(), 'avs_googlesheet_id', true);
        if($spreadsheetId){
          $gResponse = $service->spreadsheets_values->get($spreadsheetId, $range);
          $tmpListings[] = $gResponse->getValues();
        }
      }

      foreach($tmpListings as $key => $value){
        $listings = array_merge($listings, $value);
      }

      $response['listings'] = json_encode($listings);
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
  
  $tokenPath = WP_THEME_PATH.'/avs/google-api-php-client/token-'.get_current_user_id().'.json';

  $client = new Google_Client();
  $client->setAuthConfigFile(WP_THEME_PATH.'/avs/google-api-php-client/client_secrets.json');
  $client->setRedirectUri(WP_SITE_URL.'/wp-admin/admin-ajax.php?action=avs_google_sheet_access_token');
  $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
  $client->addScope('https://www.googleapis.com/auth/drive');

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
  
}
add_action( 'wp_ajax_avs_google_sheet_access_token', 'avs_update_google_sheet_access_token' );

/**
 * Process document for validation
 */
function avs_save_file_to_g_drive() {
  require_once WP_THEME_PATH.'/avs/google-api-php-client/vendor/autoload.php';

  $client = new Google_Client();
  $client->setAuthConfig(WP_THEME_PATH.'/avs/google-api-php-client/client_secrets.json');
  $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
  $client->addScope('https://www.googleapis.com/auth/drive');

  $response = ['error' => false];
  $tokenPath = WP_THEME_PATH.'/avs/google-api-php-client/token-'.get_current_user_id().'.json';

  if ( file_exists($tokenPath) ) { // Check if token exist
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    // If previous token expired
    if ($client->isAccessTokenExpired()) {
      header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
      exit();
    }else{
      $driveService = new Google_Service_Drive($client);
      $fileMetadata = new Google_Service_Drive_DriveFile(array('name' => 'Death_Note_L_ident.jpg'));
      $content = file_get_contents(WP_THEME_PATH.'/avs/google-api-php-client/Death_Note_L_ident.jpg');      
      $file = $driveService->files->create($fileMetadata, array(
        'data' => $content,
        'mimeType' => 'image/jpeg',
        'uploadType' => 'multipart',
        'fields' => 'id'
      ));

      printf("File ID: %s\n", $file->id);
    }
  } else {
    header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
    exit();
  }
}
add_action( 'wp_ajax_avs_save_file_to_g_drive', 'avs_save_file_to_g_drive' );

/**
 * Process file validation
 */
function avs_process_document(){

  if( isset($_REQUEST['file_name']) && isset($_REQUEST['article_data']) ){
    $file_name = $_REQUEST['file_name'];
    $article_data = json_decode(stripslashes($_REQUEST['article_data']));
    $file_path = WP_UPLOAD_DIR.'/server/files/'.$file_name;

    /* avs_save_file_to_g_drive($file_name, $file_path); */
    $images = avs_doc_image_count($file_path, $article_data);
    $title = avs_doc_file_title_opertion($file_name, $file_path, $article_data);
    $length = avs_read_doc_length($file_path, $article_data);
/*
        echo $images;
    print_r($title);
    echo $length;
    die;*/
    //print_r($images);die;
    $results =array();
    if($images){ $results[] = $images; }
    if($length){ $results[] = $length; }
    
    $results = array_merge( $title,$results);
   
     if(empty($results)){
      $results = null;
     }

    echo json_encode($results);
    exit(); 
  } 
}
add_action( 'wp_ajax_avs_process_document', 'avs_process_document' );

/*
 * Get number of image count 
 * @param string $file_path File path
 * @return array
 */
function avs_doc_image_count($file_path, $article_data) {
  $response = array();
  $zip = new ZipArchive;
  $flag = 0; // set flag to get count of image

  if (true === $zip->open($file_path)) { // Open the received archive file
    for ($i=0; $i<$zip->numFiles;$i++) {
      $zip_element = $zip->statIndex($i); // Loop via all the files to check for image files
      if(preg_match("([^\s]+(\.(?i)(jpg|jpeg|png|gif|bmp))$)",$zip_element['name'])) { // Check for images
        $flag += 1;
      }
    }
  }

  //echo $flag;echo $article_data[13];die;
  if ($flag < $article_data[13]) {
    return 'Expected number of Images:'.$article_data[13].' Number of images in article: '.$flag;
  }
  return false;
}

/*
 * Get file title operation of doc 
 * @param string $filename File name
 * @param string $file_path File path
 * @return array
 */
function avs_doc_file_title_opertion($file_name, $file_path, $article_data){
  $response = array();
  $file_name_ext = explode('.', $file_name);
  $file_title = explode(' - ', $file_name_ext[0]);
  $splitdata = str_split($file_name_ext[0],11);
  $length = strlen($file_title[0]);
 
  if ($length < 55) {  //$file_title[1]
   // do nothing
  } else {
      $response[] = 'Max title length: 55 characters. Article title length: '.strlen($file_title[0]); 
    }
    
  // check UID is exist of not in file name
 /* if (strpos($file_name_ext[0], $article_data[2]) == 0) {echo "1";die;}else{ echo "2";die;
    $response[] = 'UID not included in filename. Example of a valid filename: "2986-1527NG - Bitcoin strategies and tips for beginners.docx"';  //strpos($file_name_ext[0], $splitdata[0]) !== false
  }*/
  
  if(strpos($file_name_ext[0], $article_data[2]) === false) {
     $response[] = 'UID not included in filename. Example of a valid filename: "2986-1527NG - Bitcoin strategies and tips for beginners.docx"';
   }else if(strpos($file_name_ext[0], $article_data[2]) == '0'){
    //Do nothing
   }
 
  return $response;
}

/*
 * To get the length of doc file 
 * @param string $File path File name
 * @return array
 */
function avs_read_doc_length($file_path, $article_data) {
  if (filesize($file_path) <= $article_data[12]) {
    return 'Expected article minimum length: '.$article_data[12].' Actual article length: '.filesize($file_path);
  }
  return false;
}

/**
 * Add custom field to user edit profile
 * @param $user
 */
function avs_user_profile_custom_fields($user){
  $avs_googlesheet_id = (get_user_meta($user->ID, 'avs_googlesheet_id', true)) ? get_user_meta($user->ID, 'avs_googlesheet_id', true) : '';
  ?>
  <table class="form-table">
    <tr>
      <th><label for="contact">Google Sheet ID</label></th>
      <td><input type="text" class="input-text form-control" name="avs_googlesheet_id" id="avs_googlesheet_id" value="<?php echo $avs_googlesheet_id; ?>" /></td>
    </tr>
  </table>
  <?php
}
add_action( 'show_user_profile', 'avs_user_profile_custom_fields' );
add_action( 'edit_user_profile', 'avs_user_profile_custom_fields' );

/**
 * Save user custom fields
 * @param User Id $user_id
 */
function avs_save_custom_user_profile_fields($user_id){
  update_user_meta( $user_id, 'avs_googlesheet_id', $_POST['avs_googlesheet_id'] );
}
add_action( 'personal_options_update', 'avs_save_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'avs_save_custom_user_profile_fields' );