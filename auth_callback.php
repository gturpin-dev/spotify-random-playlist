<?php 

$path_WP = dirname( __DIR__, 3 );
require( $path_WP . '/wp-load.php' );

require_once __DIR__ . '/vendor/autoload.php';

function spotify_auth_callback() {
	$client_id     = get_field( 'spotify_client_id', 'option' );
	$client_secret = get_field( 'spotify_client_secret', 'option' );
	$redirect_uri  = get_field( 'spotify_redirect_uri', 'option' );
	
	$session = new SpotifyWebAPI\Session(
		$client_id,
		$client_secret,
		$redirect_uri
	);
	
	$current_state = get_field( 'spotify_state', 'option' );
	$state         = $_GET['state'];
	
	// The state returned isn't the same as the one we've stored, we shouldn't continue
	if ( $state !== $current_state ) {
		die( 'State mismatch' );
	}
	
	// Request a access token using the code from Spotify
	$session->requestAccessToken( esc_html( $_GET['code'] ) );
	
	$access_token  = $session->getAccessToken();
	$refresh_token = $session->getRefreshToken();
	
	update_field( 'spotify_access_token', $access_token, 'option' );
	update_field( 'spotify_refresh_token', $refresh_token, 'option' );
	
	// Send the user along and fetch some data!
	header( 'Location: app.php' );
	die();
}
spotify_auth_callback();