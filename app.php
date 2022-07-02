<?php

$path_WP = dirname( __DIR__, 3 );
require( $path_WP . '/wp-load.php' );

require_once __DIR__ . '/vendor/autoload.php';

$access_token = get_field( 'spotify_access_token', 'option' );
$api          = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken( $access_token );

$current_user_id    = get_current_user_id();
$custom_playlist_id = get_field( 'spotify_custom_playlist_id', 'user_' . $current_user_id );
$tracks_per_artist  = (int) get_field( 'spotify_tracks_per_artist', 'user_' . $current_user_id );
$artist_ids         = get_field( 'spotify_artists', 'user_' . $current_user_id );

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
	$playlist_tracks  = [];

	foreach ( $artist_ids as $artist_id ) {
		$track_ids = [];

		// Get all artist's albums
		$artist_albums = $api->getArtistAlbums( $artist_id, [ 
			'limit'   => 50,
			'country' => 'FR'
		] );
	
		// go to next artist if no albums
		if ( $artist_albums->total == 0 ) continue;
		
		// Get all artist's albums' tracks
		$artist_tracks = [];
		foreach ( $artist_albums->items as $album ) {
			$tracks        = $api->getAlbumTracks( $album->id, [
				'limit' => 50,
			] );
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
	}

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
	$tracks = $api->getPlaylistTracks( $playlist_id, [
		'limit' => 50,
	] );
	
	if ( $tracks->total == 0 ) return;

	$tracks = $tracks->items;

	// match the needed format for the deletePlaylistTracks method
	$tracks_to_delete = [];
	foreach ( $tracks as $track ) {
		$tracks_to_delete[ 'tracks' ][] = $track->track;
	}

	$test = $api->deletePlaylistTracks( $playlist_id, $tracks_to_delete );
}

delete_playlist_tracks( $api, $custom_playlist_id );
var_dump('TEST');
die();
$random_tracks = get_random_tracks_artists( $api, $artist_ids, $tracks_per_artist );
$playlist_id   = $api->addPlaylistTracks( $custom_playlist_id, $random_tracks );

var_dump( $playlist_id );