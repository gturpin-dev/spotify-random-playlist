<?php

namespace Gturpin\Spotify;

class Core extends Plugin {

	protected      $main_file;
	private static $_instance;

	public function __construct( $main_file = null ) {
		$this->main_file = $main_file;
		parent::__construct();
		$this->init();

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Plugin init setup
	 *
	 * @return void
	 */
	public function init() {

	}

	/**
	 * Load things after plugins are loaded
	 *
	 * @return void
	 */
	public function plugins_loaded() {

	}

	/**
	 * Get instance of this class
	 *
	 * @return Core Instance of this class
	 */
	public static function get_instance( $main_file = null ): Core {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new Core( $main_file );
		}

		return self::$_instance;
	}
}