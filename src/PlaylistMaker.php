<?php 

namespace Gturpin\Spotify;

use Gturpin\Spotify\Helpers\User;

class PlaylistMaker {

	/**
	 * Getting the api wrapper with a valid token
	 *
	 * @return \SpotifyWebAPI\SpotifyWebAPI the api wrapper
	 */
	public function get_api_wrapper() {
		$user_id       = get_current_user_id();
		$access_token  = get_field( 'spotify_access_token', 'user_' . $user_id );
		$refresh_token = get_field( 'spotify_refresh_token', 'user_' . $user_id );
		$client_id     = get_field( 'spotify_client_id', 'user_' . $user_id );
		$client_secret = get_field( 'spotify_client_secret', 'user_' . $user_id );

		$session = new \SpotifyWebAPI\Session(
			$client_id,
			$client_secret,
		);
		
		// Use previously requested tokens
		if ( $access_token ) {
			$session->setAccessToken( $access_token );
			$session->setRefreshToken( $refresh_token );
		} else {
			// Or request a new access token
			$session->refreshAccessToken( $refresh_token );
		}

		$options = [
			'auto_refresh' => true,
		];
		$api = new \SpotifyWebAPI\SpotifyWebAPI( $options, $session );
		
		$api->setSession( $session );
		
		// Store the new tokens
		update_field( 'spotify_access_token', $session->getAccessToken(), 'user_' . $user_id );
		update_field( 'spotify_refresh_token', $session->getRefreshToken(), 'user_' . $user_id );

		return $api;
	}

	/**
	 * Process the caching of the artists's tracks to avoid rate limit
	 * Must be called at 30 seconds intervals to avoid rate limit
	 * 
	 * @see https://developer.spotify.com/documentation/web-api/guides/rate-limits/
	 *
	 * @return void
	 */
	public function processing_cache_tracks() {
		$user_id      = get_current_user_id();
		$artists_data = get_user_meta( $user_id, 'spotify_artists_data', true ) ?: [];
		$api          = $this->get_api_wrapper();

		$artists_ids = get_field( 'spotify_artists', 'user_' . $user_id );
		$artists_ids = array_map( function( $artist ) {
			return $artist['spotify_artist_id'];
		}, $artists_ids );

		// Adding non treated artists to the list
		foreach ( $artists_ids as $artist_id ) {
			if ( isset( $artists_data[ $artist_id ] ) ) continue;

			$artists_data[ $artist_id ] = [
				'albums'  => [],
				'treated' => false,
			];
		}

		// Treating artists
		foreach ( $artists_data as $artist_id => &$artist_data ) { // TODO: insert in_array before to check if any artist can be treated ?
			if ( $artist_data['treated'] === true ) continue;

			// If albums are not fetched yet, we do it and treat the rest later
			if ( empty( $artist_data['albums'] ) ) {

				$artist_albums = $api->getArtistAlbums( $artist_id, [
					'limit'   => 50,
					'country' => 'FR'
				] );
				// TODO: check to fetch more albums if next isset ?

				// go to next artist if no albums found
				if ( $artist_albums->total === 0 ) continue;

				// Populate the albums array
				$formatted_albums = [];
				foreach ( $artist_albums->items as $album ) {
					$formatted_albums[ $album->id ] = [
						'name'    => $album->name,
						'tracks'  => [],
						'treated' => false,
					];
				}
				
				$artist_data['albums'] = $formatted_albums;
				update_user_meta( $user_id, 'spotify_artists_data', $artists_data );
				return true; // doing the rest later
			} else {

				// Treating albums
				foreach ( $artist_data['albums'] as $album_id => &$album_data ) { // TODO: insert in_array before to check if any album can be treated ?
					if ( $album_data['treated'] === true ) continue;

					// Get all artist's albums' tracks
					$tracks = $api->getAlbumTracks( $album_id, [
						'limit' => 50,
					] );

					$tracks                = $tracks->items;
					$track_ids             = array_map( fn ( $track ) => $track->id, $tracks );
					$album_data['tracks']  = $track_ids;
					$album_data['treated'] = true;

					update_user_meta( $user_id, 'spotify_artists_data', $artists_data );
					return true; // doing the rest later
				}
			}

			// If we are here, all albums have been treated
			$artist_data['treated'] = true;
			update_user_meta( $user_id, 'spotify_artists_data', $artists_data );
		}
		
		return false; // Nothing as done
	}

	/**
	 * Getting random tracks from the artists stored locally
	 *
	 * @return void
	 */
	public function get_random_tracks( int $tracks_per_artist = 5 ) {
		$user_id           = get_current_user_id();
		$artists_data      = get_user_meta( $user_id, 'spotify_artists_data', true ) ?: [];
		$tracks_per_artist = (int) get_field( 'spotify_tracks_per_artist', 'user_' . $user_id ) ?: 5;
		$random_tracks     = [];

		$user_helper = new User();
		$artists_ids = $user_helper->get_artists_meta( 'spotify_artist_id' );

		// Filter the artists that are cached
		$artists_data = array_filter( $artists_data, function( $artist_key ) use ( $artists_ids ) {
			return array_search( $artist_key, $artists_ids ) !== false;
		}, ARRAY_FILTER_USE_KEY );
		
		foreach ( $artists_data as $artist_id => $artist ) {
			$artist_tracks = $this->get_all_tracks_from_cache( $artist_id );

			// Shuffle and get the tracks from begining
			shuffle( $artist_tracks );
			$artist_tracks = array_slice( $artist_tracks, 0, $tracks_per_artist );
			
			$random_tracks = array_merge( $random_tracks, $artist_tracks );
		}

		shuffle( $random_tracks );
		return $random_tracks;
	}

	/**
	 * Get the tracks from the artists stored locally
	 * 
	 * @param string $artist_id The artist id
	 * 
	 * @return array The tracks
	 */
	public function get_all_tracks_from_cache( string $artist_id ) {
		$user_id      = get_current_user_id();
		$artists_data = get_user_meta( $user_id, 'spotify_artists_data', true ) ?: [];
		$array_tracks = [];
		
		// bail early if no artist found
		if ( ! isset( $artists_data[ $artist_id ] ) ) return false;
		
		$artist = $artists_data[ $artist_id ];

		// Bail early if the artist not fully treated yet
		if ( $artist['treated'] !== true ) return false;

		foreach ( $artist['albums'] as $album ) {
			$array_tracks = array_merge( $array_tracks, $album['tracks'] );
		}

		return array_unique( $array_tracks );
	}

	/**
	 * Override the playlist tracks with the random tracks
	 *
	 * @param string $playlist_id The playlist id
	 * @param string[] $random_tracks The random tracks ids
	 *
	 * @return bool True if the tracks have been overridden, false otherwise
	 */
	public function override_playlist( $playlist_id, $random_tracks ) {
		$api      = $this->get_api_wrapper();
		$response = $api->replacePlaylistTracks( $playlist_id, $random_tracks );
		return $response;
	}
}