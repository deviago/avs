<?php
/**
 * Include library file
 */
include('lib/inc.php');

/**
 * Include styles and scripts
 */
function avs_enqueue_styles_n_scripts() {

  wp_enqueue_style( 'avs-parent-style', get_template_directory_uri() . '/style.css' );
  wp_enqueue_style( 'avs-parent-boot', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' );

  wp_enqueue_script( 'wp-util' );
  wp_enqueue_script( 'avs-widget-js', get_theme_file_uri('/assets/js/vendor/jquery.ui.widget.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-ifram-js', get_theme_file_uri('/assets/js/jquery.iframe-transport.js'), array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-fileupload-js', get_theme_file_uri('/assets/js/jquery.fileupload.js'), array('jquery'), '1.0', false );
   
  wp_enqueue_script( 'avs-fuse', 'https://cdnjs.cloudflare.com/ajax/libs/fuse.js/3.2.1/fuse.min.js', array('jquery'), '1.0', false );
  wp_enqueue_script( 'avs-js', get_theme_file_uri('/assets/js/custom.js'), array('jquery'), '1.0', false );
  wp_localize_script( 'avs-js', 'ajax_object', array( 
      'ajax_url' => admin_url( 'admin-ajax.php' ) ,
      'user_display_name' => avs_get_display_name()
    )
  );
}
add_action( 'wp_enqueue_scripts', 'avs_enqueue_styles_n_scripts' );

/**
 * Get user display name
 */
function avs_get_display_name($user_id = null){
  $user_id = ($user_id == null) ? get_current_user_id() : $user_id;
	$user_data = get_userdata($user_id);
  
  $display_name = '';
	if( isset($user_data->display_name) ){
		$display_name = $user_data->display_name;
	}else if( $user_data->first_name || $user_data->last_name ){
		$display_name = $user_data->first_name.' '.$user_data->last_name;
	}else{
		$display_name = $user_data->user_login;
	}

	return htmlentities( ucwords( trim($display_name, ' ') ) );
}