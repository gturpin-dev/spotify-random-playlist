<?php 

function spotify_auth() {
	$client_id     = get_field( 'spotify_client_id', 'option' );
	$client_secret = get_field( 'spotify_client_secret', 'option' );
	$redirect_uri  = get_field( 'spotify_redirect_uri', 'option' );
	
	$session = new SpotifyWebAPI\Session(
		$client_id,
		$client_secret,
		$redirect_uri
	);
	
	$state   = $session->generateState();
	$options = [
		'scope' => [
			'playlist-read-private',
			'playlist-modify-public',
			'playlist-modify-private',
			'user-read-private',
		],
		'state' => $state,
	];
	update_field( 'spotify_state', $state, 'option' );
	
	header( 'Location: ' . $session->getAuthorizeUrl( $options ) );
	die();
}