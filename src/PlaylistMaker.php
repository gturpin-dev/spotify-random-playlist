<?php 

namespace Gturpin\Spotify;

class PlaylistMaker {

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
		$artists_data = get_user_meta( $user_id, 'spotify_artists_data' );
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

		echo '<pre>' . print_r( $artists_data, 1 ) . '</pre>';
		die;

		// Treating artists
		foreach ( $artists_data as $artist_id => $artist_data ) { // TODO: insert in_array before to check if any artist can be treated ?
			echo '<pre>' . print_r( $artists_data, 1 ) . '</pre>';
			echo '<pre>' . print_r( $artist_data, 1 ) . '</pre>'; // TODO: Impossible de comprendre pourquoi il est vide
			die;
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

				// populate the albums array with 
				$albums = $artist_albums->items;

				// addinf the treated false to each album
				array_walk( $albums, function( $album ) {
					$album->treated = false;
				} );

				// Set the album id to the key of the array
				$albums = array_combine( wp_list_pluck( $albums, 'id' ), $albums );

				$artist_data['albums'] = $albums;
				update_user_meta( $user_id, 'spotify_artists_data', $artists_data );
				return true; // doing the rest later
			} else {

				// Treating albums
				foreach ( $artist_data['albums'] as $album_id => $album_data ) { // TODO: insert in_array before to check if any album can be treated ?
					if ( $album_data->treated === true ) continue;

					// Get all artist's albums' tracks
					$tracks = $api->getAlbumTracks( $album_id, [
						'limit' => 50,
					] );
					$album_data->tracks = $tracks->items;

					update_user_meta( $user_id, 'spotify_artists_data', $artists_data );
					$album_data->treated = true; // doing the rest later
				}
			}

			echo '<pre>' . print_r( get_user_meta( $user_id, 'spotify_artists_data' ), 1 ) . '</pre>';
			die;
			
		}
		
		echo '<pre>' . print_r( $artists_data, 1 ) . '</pre>';

		die;

		// Pour chaque artistes OK
			// Rajouter une key 'treated' => false OK
			// Get ses albums  OK
			// remplir le cache des albums ids avec la key 'treated' => false OK
			// Pour chaque album id
				// Get les tracks de l'album
				// Prendre ces tracks et les mettre dans la key artist_tracks
				// remplir le cache des tracks de l'album
				// Passer sa key 'treated' => true
			// Si toutes les tracks de l'album ont été traitées, passer sa key 'treated' => true
		
		// 	[
		// 		'artist_id' => '',
		// 		'artist_albums_count' => 12, // tant qu'on est pas la on refetch les autres albums
		// 		'artist_tracks_count' => 53,
		// 		'artists_albums' => [
		// 			'album_id' => '',
		// 			'tracks' => [
		// 				['track_id' => ''],
		// 				['track_id' => ''],
		// 			],
		// 		],
		// 		'tracks' => [
		// 			'track_id' => '',
		// 			'track_name' => '',
		// 			'track_artists' => [
		// 				'artist_id' => '',
		// 				'artist_name' => '',
		// 			],
		// 		],
		// 	],
		// [
		//
		// ]
		
		die();
	}
}