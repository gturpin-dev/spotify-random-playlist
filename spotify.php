<?php 

/**
* Plugin Name: Spotify Random Playlist
* Plugin URI: https://github.com/gturpin-dev/spotify-random-playlist
* Description: Generate a random playlist from a list of artists.
* Version: 1.0
* Author: Guillaume TURPIN
* Author URI: https://github.com/gturpin-dev
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

	if ( $_SERVER['REQUEST_URI'] === '/spotify-credentials/' ) {
		spotify_auth();
	}
} );


add_filter( 'acf/settings/save_json', function( $path ) {
	$path = plugin_dir_path( __FILE__ ) . 'acf-sync';

	return $path;
} );

add_filter( 'acf/settings/load_json', function( $paths ) {
	// remove original path
    unset( $paths[0] );
    
    $paths[] = plugin_dir_path( __FILE__ ) . 'acf-sync';
	
	return $paths;
} );
