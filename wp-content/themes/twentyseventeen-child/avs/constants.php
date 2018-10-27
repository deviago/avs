<?php
define( 'WP_THEME_PATH', get_stylesheet_directory() );
define( 'WP_THEME_URI', get_stylesheet_directory_uri() );
define( 'WP_SITE_URL', get_site_url() );

$upload_dir = wp_upload_dir();
define( 'WP_UPLOAD_DIR', $upload_dir['basedir'] );