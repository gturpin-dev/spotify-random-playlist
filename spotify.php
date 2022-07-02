<?php 

/**
* Plugin Name: Spotify
* Plugin URI: https://www.yourwebsiteurl.com/
* Description: spotify
* Version: 1.0
* Author: Your Name Here
* Author URI: http://yourwebsiteurl.com/
**/

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/auth.php';

add_action( 'acf/init', function() {
	acf_add_options_page( [
		'page_title' => __( 'Spotify Credentials', 'aipals' ),
		'menu_title' => __( 'Spotify Credentials', 'aipals' ),
		'menu_slug'  => 'spotify-credentials',
		'capability' => 'administrator',
		'redirect'   => false
	] );

	if ( strpos( $_SERVER['REQUEST_URI'], 'spotify-credentials' ) !== false ) {
		spotify_auth();
	}
} );