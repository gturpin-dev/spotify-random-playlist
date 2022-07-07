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

add_action( 'acf/init', function() {
	acf_add_options_page( [
		'page_title' => __( 'Spotify Credentials', 'aipals' ),
		'menu_title' => __( 'Spotify Credentials', 'aipals' ),
		'menu_slug'  => 'spotify-credentials',
		'capability' => 'administrator',
		'redirect'   => false
	] );
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

add_action( 'show_user_profile', 'extra_user_profile_fields' );
add_action( 'edit_user_profile', 'extra_user_profile_fields' );

function extra_user_profile_fields( $user ) { 
	echo "<h2>". __( 'Spotify auth', 'spotify' ) . "</h2>";
	
	$spotify_access_token = get_field( 'spotify_access_token', 'user_' . $user->ID );

	if ( $spotify_access_token ) {
		echo '<p>' . __( 'account already linked', 'yetix_development' ) . '</p>';
	} else {
		$client_id     = get_field( 'spotify_client_id', 'user_' . $user->ID );
		$client_secret = get_field( 'spotify_client_secret', 'user_' . $user->ID );
		$redirect_uri  = get_field( 'spotify_redirect_uri', 'user_' . $user->ID );

		// TODO : gérer les cas ou y'a des erreurs dans les credentials ou si c'est vide etc

		$session = new SpotifyWebAPI\Session(
			$client_id,
			$client_secret,
			$redirect_uri
		);
	
		// $state = $session->generateState();
		$state = wp_generate_uuid4();

		set_transient( 'spotify_oauth_link_' . $state, [
			'user_id'  => $user->ID,
			'redirect' => admin_url( sprintf( basename( $_SERVER['REQUEST_URI'] ) ) )
		], HOUR_IN_SECONDS );

		$options = [
			'scope' => [
				'playlist-read-private',
				'playlist-modify-public',
				'playlist-modify-private',
				'user-read-private',
			],
			'state' => $state,
		];

		$oauth_url = $session->getAuthorizeUrl( $options );

		echo '<a href="' . esc_url( $oauth_url ) . '">link my spotify account</a>';
	}
}

function gt_spotify_auth_callback( \WP_REST_Request $rest_request ) {
	$code  = $rest_request->get_param( 'code' );
	$state = $rest_request->get_param( 'state' );

	// Getting user infos from state
	$transient           = get_transient( 'spotify_oauth_link_' . $state );
	$user_id             = $transient['user_id'];
	$redirect_after_auth = $transient['redirect'];
	
	// Making session
	$client_id     = get_field( 'spotify_client_id', 'user_' . $user_id );
	$client_secret = get_field( 'spotify_client_secret', 'user_' . $user_id );
	$redirect_uri  = get_field( 'spotify_redirect_uri', 'user_' . $user_id );
	
	$session = new SpotifyWebAPI\Session(
		$client_id,
		$client_secret,
		$redirect_uri
	);
	
	// Request a access token using the code from Spotify and store them
	$session->requestAccessToken( esc_html( $code ) );
	
	$access_token  = $session->getAccessToken();
	$refresh_token = $session->getRefreshToken();
	
	update_field( 'spotify_access_token', $access_token, 'user_' . $user_id );
	update_field( 'spotify_refresh_token', $refresh_token, 'user_' . $user_id );

	wp_safe_redirect( $redirect_after_auth );
	exit;
}

add_action( 'rest_api_init', function() {
	register_rest_route( 
		'spotify/v1', 
		'/auth_callback', 
		[
			'methods'  => 'GET',
			'callback' => 'gt_spotify_auth_callback'
		] 
	);
} );