<?php 

namespace Gturpin\Spotify\admin;

use Gturpin\Spotify\Core;
use Gturpin\Spotify\PlaylistMaker;

class OptionPage {

	/**
	 * The option page slug.
	 *
	 * @var string
	 */
	private string $page_slug = 'spotify-playlist-maker';
	
	/**
	 * Parent slug values for the admin menu
	 *
	 * @var array
	 */
	private $parent_slugs = [
		'Dashboard'         => 'index.php',
		'Posts'             => 'edit.php',
		'Media'             => 'upload.php',
		'Pages'             => 'edit.php?post_type=page',
		'Comments'          => 'edit-comments.php',
		'Custom Post Types' => 'edit.php?post_type=your_post_type',
		'Appearance'        => 'themes.php',
		'Plugins'           => 'plugins.php',
		'Users'             => 'users.php',
		'Tools'             => 'tools.php',
		'Settings'          => 'options-general.php',
		'Network Settings'  => 'settings.php'
	];
	
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_option_page' ] );
		// add_action( 'admin_init', [ $this, 'register_settings_fields' ] );

		add_action( 'admin_post_spotify_processing_cache_tracks', [ $this, 'processing_cache_tracks' ] );

		add_action( 'admin_post_spotify_generate_random_playlist', [ $this, 'generate_random_playlist' ] );
	}

	/**
	 * Register the page settings
	 *
	 * @return void
	 */
	public function register_option_page() {
		add_menu_page(
			__( 'Spotify Playlist', 'spotify' ),
			__( 'Spotify Playlist', 'spotify' ),
			'manage_options',
			$this->page_slug,
			[ $this, 'settings_page_content' ],
			'dashicons-playlist-audio'
		);
	}

	/**
	 * Process the caching of the artists's tracks to avoid rate limit
	 *
	 * @return void
	 */
	public function processing_cache_tracks() {
		$referer = wp_get_referer();

		$playlist_maker = new PlaylistMaker();
		$test = $playlist_maker->processing_cache_tracks();

		who_logr( 'status : ' . $test );

		wp_safe_redirect( $referer );
		exit();
	}

	/**
	 * Generate a random playlist from the artists' tracks stored locally
	 *
	 * @return void
	 */
	public function generate_random_playlist() {
		$referer           = wp_get_referer();
		$user_id           = get_current_user_id();
		$tracks_per_artist = (int) get_field( 'spotify_tracks_per_artist', 'user_' . $user_id ) ?: 5;
		$playlist_id	   = get_field( 'spotify_custom_playlist_id', 'user_' . $user_id );

		$playlist_maker = new PlaylistMaker();
		$random_tracks  = $playlist_maker->get_random_tracks( $tracks_per_artist );
		$playlist_maker->override_playlist( $playlist_id, $random_tracks );
		
		wp_safe_redirect( $referer );
		exit();
	}

	/**
	 * Display the settings page content
	 *
	 * @return void
	 */
	public function settings_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'admotc' ) );
		}

		$plugin = Core::get_instance();

		?>
			<div class="wrapper">
				<h2><?php _e( 'Spotify Playlist Maker' ) ?></h2>
				<p><?php _e( 'This page allow you to generate a playlist based on the list of artist you made on your profile' ) ?></p>

				<?php settings_errors(); ?>

				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="spotify_processing_cache_tracks">

					<p class="submit">
						<input type="submit" class="button button-primary" value="<?php _e( 'Processing cache tracks' ) ?>">
					</p>
				</form>

				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="spotify_generate_random_playlist">

					<p class="submit">
						<input type="submit" class="button button-primary" value="<?php _e( 'Generate a Playlist' ) ?>">
					</p>
				</form>
			</div>
		<?php 
	}

	/**
	 * Register the settings fields for the option page
	 *
	 * @return void
	 */
	public function register_settings_fields() {
		$plugin = Core::get_instance();

		add_settings_section(
			$plugin->get_slug() . '-general_section',
			'',
			function() {
				echo '<p>' . __( 'To add the cookie button, go to Appearance > Menu > Add custom link and add a link with this exact anchor "#cookie-settings". No matter to the text and other settings link.', 'admotc' ) . '</p>';
			},
			$this->page_slug
		);

		add_settings_field(
			$plugin->get_slug() . '-cookie-script',
			__( 'Cookie Script', 'admotc' ),
			function() use ( $plugin ) {
				$option_name  = $plugin->get_slug() . '-cookie-script';
				$option_value = get_option( $option_name );
				?>
					<textarea id="<?php echo $option_name ?>" name="<?php echo $option_name ?>"><?php echo $option_value ?></textarea>
				<?php 
			},
			$this->page_slug,
			$plugin->get_slug() . '-general_section',
		);

		register_setting(
			$plugin->get_slug() . '_general_settings',
			$plugin->get_slug() . '-cookie-script',
			[
				'type'              => 'string',
				'sanitize_callback' => function( $value ) {
					return wp_kses( $value, [ 
						'script' => [ 
							'src'                    => [],
							'type'                   => [],
							'data-document-language' => [],
							'charset'                => [],
							'data-domain-script'     => [],
						],
						'a' => [
							'href'  => [],
							'title' => []
						],
						'br'     => [],
						'em'     => [],
						'strong' => [],
					] );
				},
				'default' => '',
			]
		);
	}
}