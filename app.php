<?php

$path_WP = dirname( __DIR__, 3 );
require( $path_WP . '/wp-load.php' );

require_once __DIR__ . '/vendor/autoload.php';

// Checker ça pour faire un try catch générique avant chaque début de call https://github.com/jwilsson/spotify-web-api-php/blob/main/docs/examples/refreshing-access-tokens.md#with-an-existing-refresh-token
// Créer un CRON qui passe toutes les 5min pour indexer en BD les données de chaque artiste spotify, pour éviter le sleep et éviter la rate limit
// Auth et Auth_callback doivent être appelés une seule fois par user ( parceque c'est juste pour la connexion spotify a ton app )
// Regrouper ces fonctions dans un objet pour l'app, qui sera appelé que si l'user est co, et n'a pas encore d'access token
// Créer une route rest pour cet Auth + une pour le callback de spotify
// Déplacer les tokens dans le compte user et linker les get_field
// Créer un bouton dans une page option pour simuler le cron et donc mettre en base l'indexation des artistes
// Permettre pour chaque artiste de refresh l'indexation ( exemple si il a un nouvel album )


$access_token = get_field( 'spotify_access_token', 'option' );
$api          = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken( $access_token );

$current_user_id    = get_current_user_id();
$custom_playlist_id = get_field( 'spotify_custom_playlist_id', 'user_' . $current_user_id );
$tracks_per_artist  = (int) get_field( 'spotify_tracks_per_artist', 'user_' . $current_user_id );
$artist_ids         = get_field( 'spotify_artists', 'user_' . $current_user_id );

$API_CALLS = 0;

function check_api_rate_limit() {
	global $API_CALLS;
	$API_CALLS++;
	
	$max_calls         = 100;
	$refresh_rate_time = 30;

	if ( $API_CALLS >= ( $max_calls - 1 ) ) {
		sleep( $refresh_rate_time );
		$API_CALLS = 0;
	}
}

$artist_ids = array_map( function( $artist ) {
	return $artist['spotify_artist_id'];
}, $artist_ids );

/**
 * Get random tracks from artists
 *
 * @param SpotifyWebAPI\SpotifyWebAPI $api Spotify API object
 * @param array $artist_ids 			   Artist IDs
 * @param integer $tracks_per_artist 	   Number of tracks per artist
 *
 * @return array Array of track ids
 */
function get_random_tracks_artists( SpotifyWebAPI\SpotifyWebAPI $api, array $artist_ids, int $tracks_per_artist = 5 ) {
	$playlist_tracks = [];

	$artist_index = 0;
	foreach ( $artist_ids as $artist_id ) {
		$track_ids = [];

		// Get all artist's albums
		$artist_albums = $api->getArtistAlbums( $artist_id, [ 
			'limit'   => 50,
			'country' => 'FR'
		] );
		check_api_rate_limit();
	
		// go to next artist if no albums
		if ( $artist_albums->total == 0 ) continue;
		
		// Get all artist's albums' tracks
		$artist_tracks = [];
		foreach ( $artist_albums->items as $album ) {
			$tracks        = $api->getAlbumTracks( $album->id, [
				'limit' => 50,
			] );
			check_api_rate_limit();

			$tracks        = $tracks->items;
			$artist_tracks = array_merge( $artist_tracks, $tracks );
		}
		
		$track_ids = array_map( function ( $track ) {
			return $track->id;
		}, $artist_tracks );
	
		$track_ids = array_unique( $track_ids );
		
		// get random tracks from the array
		shuffle( $track_ids );
		$track_ids = array_slice( $track_ids, 0, $tracks_per_artist );
		
		// add tracks to the playlist
		$playlist_tracks = array_merge( $playlist_tracks, $track_ids );

		$artist_index++;
	}

	shuffle( $playlist_tracks );

	return $playlist_tracks;
}

/**
 * Delete all tracks from a playlist
 *
 * @param SpotifyWebAPI\SpotifyWebAPI $api Spotify API object
 * @param string $playlist_id Playlist ID
 *
 * @return void
 */
function delete_playlist_tracks( SpotifyWebAPI\SpotifyWebAPI $api, string $playlist_id ) {
	$playlist = $api->getPlaylist( $playlist_id );
	check_api_rate_limit();
	
	$snapshot = $playlist->snapshot_id;

	$tracks = $api->getPlaylistTracks( $playlist_id, [
		'limit' => 50,
	] );
	check_api_rate_limit();

	if ( $tracks->total == 0 ) return;

	$tracks = $tracks->items;

	// match the needed format for the deletePlaylistTracks method
	$tracks_to_delete = [];
	foreach ( $tracks as $track ) {
		$tracks_to_delete[ 'tracks' ][] = [ 'uri' => $track->track->uri ];
	}
	
	$api->deletePlaylistTracks( $playlist_id, $tracks_to_delete, $snapshot );
	check_api_rate_limit();
}

delete_playlist_tracks( $api, $custom_playlist_id ); // TODO: to replace with replacePlaylistTracks( $playlist_id, $tracks_ids )
$random_tracks = get_random_tracks_artists( $api, $artist_ids, $tracks_per_artist );
$playlist_id   = $api->addPlaylistTracks( $custom_playlist_id, $random_tracks );

// var_dump( $playlist_id );