<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'you do not have access to this page!' );
}

class wpdef_tour {

	private static $_this;

	public $capability = 'activate_plugins';
	public $url;
	public $version;

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
				get_class( $this ) ) );
		}

		self::$_this = $this;

		$this->url     = WPDEF_URL . '/shepherd';
		$this->version = WPDEF_VERSION;

		add_action( 'wp_ajax_wpdef_cancel_tour', array( $this, 'listen_for_cancel_tour' ) );
		add_action( 'admin_init', array( $this, 'restart_tour' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	static function this() {
		return self::$_this;
	}

	public function enqueue_assets( $hook ) {

		if ( get_site_option( 'wpdef_tour_started' ) ) {
			if ( $hook !== 'plugins.php' ) {
				return;
			}

			wp_register_script( 'wpdef-tether',
				trailingslashit( $this->url )
				. 'tether/tether.min.js', "", $this->version );
			wp_enqueue_script( 'wpdef-tether' );

			wp_register_script( 'wpdef-shepherd',
				trailingslashit( $this->url )
				. 'tether-shepherd/shepherd.min.js', "", $this->version );
			wp_enqueue_script( 'wpdef-shepherd' );

			wp_register_style( 'wpdef-shepherd',
				trailingslashit( $this->url )
				. "css/shepherd-theme-arrows.min.css", "",
				$this->version );
			wp_enqueue_style( 'wpdef-shepherd' );

			wp_register_style( 'wpdef-shepherd-tour',
				trailingslashit( $this->url ) . "css/wpdef-tour.min.css", "",
				$this->version );
			wp_enqueue_style( 'wpdef-shepherd-tour' );

			wp_register_script( 'wpdef-shepherd-tour',
				trailingslashit( $this->url )
				. '/js/wpdef-tour.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'wpdef-shepherd-tour' );

			$logo  = '<span class="wpdef-tour-logo"><svg width="50px" height="50px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.82 126.88"><path d="M106.57,101.52l.06.06a27,27,0,0,0-4.47-5c-6.59-5.74-14.13-7.39-35,.36-9,3.35-17,5.77-23.19,1.53a13.71,13.71,0,0,0,3.14,3.93c6.39,5.57,14.86,3,24.47-.54C92.44,94.14,100,95.78,106.57,101.52Z" transform="translate(-31.29 -23.06)"/><path d="M103,107.36c-.39-.34-.79-.66-1.19-1,4.27,7.48,2.85,18.75-4,26.67a30.13,30.13,0,0,1-26.52,10.09,1.81,1.81,0,0,0-1.38.39,1.78,1.78,0,0,0-.66,1.24l-.26,3a1.83,1.83,0,0,0,.52,1.46,1.8,1.8,0,0,0,1,.51,29.89,29.89,0,0,0,3.08.2A39.94,39.94,0,0,0,92,145.56a33.55,33.55,0,0,0,10-7.73,44.13,44.13,0,0,0,2.82-3.61C110.39,125,109.83,113.32,103,107.36Z" transform="translate(-31.29 -23.06)"/><path d="M84.13,71h0l2.18-.82.76-.27h0l2.07-.77a.61.61,0,0,0,.33-.34.57.57,0,0,0-.12-.63l-5.66-5.66-4-4L79,57.8l9.84-9.7a.59.59,0,0,0,0-.83l-3.19-3.19a.59.59,0,0,0-.83,0L75,53.78,62.91,41.69l-4-4L58.24,37l9.84-9.69a.6.6,0,0,0,0-.84l-3.19-3.18a.58.58,0,0,0-.84,0L54.21,33l-9.76-9.77a.59.59,0,0,0-1,.21l-.78,2.07,0,0c-.27.76-.56,1.54-.86,2.34l-.23.6h0c-7,18.39-11.06,31.08-6.65,38.67a18.62,18.62,0,0,0,2,2.52c-8,11.74-7.82,25.08,2.08,33.73l.27.22c-5.56-8.34-4.58-19.2,2-29a.25.25,0,0,1,0-.07h0a6.5,6.5,0,0,0,.57.52,21,21,0,0,0,4.43,3C53.85,81.82,66.55,77.62,84.13,71Zm-39-1.57c-.23-.2-.44-.4-.65-.6h0a18.82,18.82,0,0,1-1.39-1.43A16.55,16.55,0,0,1,40,62.1C37.31,54.8,43,43.28,46.5,34c.06-.18.12-.34.19-.52L79,65.87l-.37.14-.15,0c-8.75,3.27-19.92,8.8-27,6.84A16.13,16.13,0,0,1,45.79,70Z" transform="translate(-31.29 -23.06)"/><path d="M104.89,134.22c7.67-11,8.24-23.31,1.74-32.64l-.06-.06c-6.59-5.74-14.13-7.38-35,.36-9.61,3.59-18.08,6.11-24.47.54a13.71,13.71,0,0,1-3.14-3.93,14.51,14.51,0,0,1-1.27-1C36.32,92,36.53,82.9,41.35,74.61c-6.6,9.76-7.58,20.62-2,29,7,5.88,15.48,5.54,33.39-1.29,12.08-4.61,19-5.77,25.84.16a15.18,15.18,0,0,1,3.22,4c.4.3.8.62,1.19,1C109.83,113.32,110.39,125,104.89,134.22Z" transform="translate(-31.29 -23.06)"/></svg></span>';
			$html  = '<div class="wpdef-tour-logo-text">' . $logo
			         . '<span class="wpdef-tour-text">{content}</span></div>';
			$steps = array(
				0 => array(
					'title'  => __( 'Welcome to Definitions - Internal Linkbuilding', 'definitions' ),
					'text'   => __( "You can find the settings of Definitions under every post. To get a headstart, have a look at our documentation.",
						'definitions' ),
					'start_link'   => admin_url( "edit.php" ),
					'documentation_link'   => "https://really-simple-plugins.com/definitions-internal-linkbuilding/documentation",
				),
			);
			$steps = apply_filters( 'wpdef_shepherd_steps', $steps );
			wp_localize_script( 'wpdef-shepherd-tour', 'wpdef_tour',
				array(
					'ajaxurl'        => admin_url( 'admin-ajax.php' ),
					'html'           => $html,
					'token'          => wp_create_nonce( 'wpdef_tour_nonce' ),
					'start'          => __( "Start", "definitions" ),
					'documentation'  => __( "Documentation", "definitions" ),
					'steps'          => $steps,


				) );

		}
	}


	/**
	 *
	 * @since 1.0
	 *
	 * When the tour is cancelled, a post will be sent. Listen for post and update tour cancelled option.
	 *
	 */

	public function listen_for_cancel_tour() {

		if ( ! isset($_POST['token']) || ! wp_verify_nonce($_POST['token'], 'wpdef_tour_nonce') ) {
			return;
		}
		update_site_option( 'wpdef_tour_started', false );
		update_site_option( 'wpdef_tour_shown_once', true );
	}


	public function restart_tour() {

		if ( ! isset($_POST['wpdef_restart_tour']) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options') ) {
			return;
		}

		update_site_option( 'wpdef_tour_started', true );

		wp_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

}
