<?php 

namespace Gturpin\Spotify\Helpers;

class User {

	private int $user_id;

	private string $artists_meta = 'spotify_artists';

	/**
	 * Get the artists data from the user meta
	 *
	 * @return array The artists data
	 */
	public function get_artists() {
		if ( empty( $this->user_id ) ) {
			$this->user_id = get_current_user_id();
		}

		return get_field( 'spotify_artists', 'user_' . $this->user_id );
	}

	/**
	 * Get artists meta values for the current user
	 *
	 * @param string $meta_key The meta key to get ( name or id for example )
	 *
	 * @return array The artists meta values
	 */
	public function get_artists_meta( string $meta_key ) {
		$artists = $this->get_artists();

		$artists_meta = array_map( function( $artist ) use ( $meta_key ) {
			return $artist[ $meta_key ];
		}, $artists );

		return $artists_meta;
	}
}