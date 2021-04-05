<?php
/*100% match*/

defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "wpdef_review" ) ) {
	class wpdef_review {
		private static $_this;
        public $found_in_posts_count;
        public $definitions_count;
        public $show_by_count;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}
			self::$_this = $this;

			//prio 20, to make sure it's loaded after our taxonomy is registered.
			add_action('admin_init', array($this, 'init'), 20);
			add_action('admin_init', array($this, 'process_get_review_dismiss' ));

		}

		static function this() {
			return self::$_this;
		}

		public function init(){
			$args = array(
				'taxonomy' => 'definitions_title',
				'orderby' => 'count',
				'hide_empty' => true,
			);

			$definitions_count = get_transient('wpdef_definitions_count');
			if ( !$definitions_count ) {
				$definitions_count = wp_count_terms( $args );
				set_transient('wpdef_definitions_count', $definitions_count, DAY_IN_SECONDS);
			}

			$found_in_posts_count = get_transient('wpdef_found_in_posts_count');
			if ( !$found_in_posts_count ) {
				$found_in_posts_count = DEFINITIONS::$text_parser->count_posts_with_definitions();
				set_transient('wpdef_found_in_posts_count', $found_in_posts_count, DAY_IN_SECONDS);
			}

			$this->found_in_posts_count = $found_in_posts_count;
			$this->definitions_count = $definitions_count;
			$this->show_by_count = ($definitions_count > 5 && $found_in_posts_count>5) && get_option( 'wpdef_activation_time' ) < strtotime( "-2 weeks" );
            $show_by_time = get_option( 'wpdef_activation_time' ) < strtotime( "-1 month" );
			//uncomment for testing
//			update_option('wpdef_review_notice_shown', false);
//			update_option( 'wpdef_activation_time', strtotime( "-2 month" ) );
			//show review notice, only to free users
			if ( ! defined( "wpdef_premium" ) && ! is_multisite() ) {
				if ( ! get_option( 'wpdef_review_notice_shown' )
				     && get_option( 'wpdef_activation_time' )
				     && ( $show_by_time || $this->show_by_count  )
				) {
					add_action( 'wp_ajax_dismiss_review_notice',
						array( $this, 'dismiss_review_notice_callback' ) );

					add_action( 'admin_notices',
						array( $this, 'show_leave_review_notice' ) );
					add_action( 'admin_print_footer_scripts',
						array( $this, 'insert_dismiss_review' ) );
				}

				//set a time for users who didn't have it set yet.
				if ( ! get_option( 'wpdef_activation_time' ) ) {
					update_option( 'wpdef_activation_time', time() );
				}
			}
        }

		public function show_leave_review_notice() {
			if (isset( $_GET['wpdef_dismiss_review'] ) ) return;

			/**
			 * Prevent notice from being shown on Gutenberg page, as it strips off the class we need for the ajax callback.
			 *
			 * */
			$screen = get_current_screen();
			if ( $screen->base === 'post' ) {
			    return;
			}
			?>
			<style>
				.wpdef-container {
					display: flex;
					padding: 12px;
				}

				.wpdef-container .dashicons {
					margin-left: 10px;
					margin-right: 5px;
				}

				.wpdef-review-image img {
					margin-top: 0.5em;
				}

				.wpdef-buttons-row {
					margin-top: 10px;
					display: flex;
					align-items: center;
				}
			</style>
			<div id="message"
			     class="updated fade notice is-dismissible wpdef-review really-simple-plugins"
			     style="border-left:4px solid #333">
				<div class="wpdef-container">
					<div class="wpdef-review-image">
                        <svg width="50px" height="50px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.82 126.88"><path d="M106.57,101.52l.06.06a27,27,0,0,0-4.47-5c-6.59-5.74-14.13-7.39-35,.36-9,3.35-17,5.77-23.19,1.53a13.71,13.71,0,0,0,3.14,3.93c6.39,5.57,14.86,3,24.47-.54C92.44,94.14,100,95.78,106.57,101.52Z" transform="translate(-31.29 -23.06)"/><path d="M103,107.36c-.39-.34-.79-.66-1.19-1,4.27,7.48,2.85,18.75-4,26.67a30.13,30.13,0,0,1-26.52,10.09,1.81,1.81,0,0,0-1.38.39,1.78,1.78,0,0,0-.66,1.24l-.26,3a1.83,1.83,0,0,0,.52,1.46,1.8,1.8,0,0,0,1,.51,29.89,29.89,0,0,0,3.08.2A39.94,39.94,0,0,0,92,145.56a33.55,33.55,0,0,0,10-7.73,44.13,44.13,0,0,0,2.82-3.61C110.39,125,109.83,113.32,103,107.36Z" transform="translate(-31.29 -23.06)"/><path d="M84.13,71h0l2.18-.82.76-.27h0l2.07-.77a.61.61,0,0,0,.33-.34.57.57,0,0,0-.12-.63l-5.66-5.66-4-4L79,57.8l9.84-9.7a.59.59,0,0,0,0-.83l-3.19-3.19a.59.59,0,0,0-.83,0L75,53.78,62.91,41.69l-4-4L58.24,37l9.84-9.69a.6.6,0,0,0,0-.84l-3.19-3.18a.58.58,0,0,0-.84,0L54.21,33l-9.76-9.77a.59.59,0,0,0-1,.21l-.78,2.07,0,0c-.27.76-.56,1.54-.86,2.34l-.23.6h0c-7,18.39-11.06,31.08-6.65,38.67a18.62,18.62,0,0,0,2,2.52c-8,11.74-7.82,25.08,2.08,33.73l.27.22c-5.56-8.34-4.58-19.2,2-29a.25.25,0,0,1,0-.07h0a6.5,6.5,0,0,0,.57.52,21,21,0,0,0,4.43,3C53.85,81.82,66.55,77.62,84.13,71Zm-39-1.57c-.23-.2-.44-.4-.65-.6h0a18.82,18.82,0,0,1-1.39-1.43A16.55,16.55,0,0,1,40,62.1C37.31,54.8,43,43.28,46.5,34c.06-.18.12-.34.19-.52L79,65.87l-.37.14-.15,0c-8.75,3.27-19.92,8.8-27,6.84A16.13,16.13,0,0,1,45.79,70Z" transform="translate(-31.29 -23.06)"/><path d="M104.89,134.22c7.67-11,8.24-23.31,1.74-32.64l-.06-.06c-6.59-5.74-14.13-7.38-35,.36-9.61,3.59-18.08,6.11-24.47.54a13.71,13.71,0,0,1-3.14-3.93,14.51,14.51,0,0,1-1.27-1C36.32,92,36.53,82.9,41.35,74.61c-6.6,9.76-7.58,20.62-2,29,7,5.88,15.48,5.54,33.39-1.29,12.08-4.61,19-5.77,25.84.16a15.18,15.18,0,0,1,3.22,4c.4.3.8.62,1.19,1C109.83,113.32,110.39,125,104.89,134.22Z" transform="translate(-31.29 -23.06)"/></svg>
                    </div>
					<div style="margin-left:30px">
						<p><?php
                            if ($this->show_by_count){
	                            printf( __( 'Hi, you already have %s definitions used in %s posts, awesome! If you have a moment, please consider leaving a review on WordPress.org to spread the word. We greatly appreciate it! If you have any questions or feedback, leave us a %smessage%s.',
		                            'definitions' ),$this->definitions_count, $this->found_in_posts_count, '<a href="https://wpdefinitions.com" target="_blank">', '</a>' );
                            } else {
	                            printf( __( 'Hi, you have been using WP Definitions for a month now, awesome! If you have a moment, please consider leaving a review on WordPress.org to spread the word. We greatly appreciate it! If you have any questions or feedback, leave us a %smessage%s.',
		                            'definitions' ), '<a href="https://wpdefinitions.com" target="_blank">', '</a>' );
                            }
						?></p>
						<i>- Rogier</i>
						<div class="wpdef-buttons-row">
							<a class="button button-primary" target="_blank"
							   href="https://wordpress.org/support/plugin/definitions/reviews/#new-post"><?php _e( 'Leave a review', 'definitions' ); ?></a>

							<div class="dashicons dashicons-calendar"></div>
							<a href="<?php echo add_query_arg(array('wpdef_dismiss_review'=>1), admin_url() )?>"
							   id="maybe-later"><?php _e( 'Maybe later', 'definitions' ); ?></a>

							<div class="dashicons dashicons-no-alt"></div>
							<a href="<?php echo add_query_arg(array('wpdef_dismiss_review'=>1), admin_url() )?>"><?php _e( 'Don\'t show again', 'definitions' ); ?></a>
						</div>
					</div>
				</div>
			</div>
			<?php

		}

		/**
		 * Insert some ajax script to dismiss the review notice, and stop nagging about it
		 *
		 * @since  2.0
		 *
		 * @access public
		 *
		 * type: dismiss, later
		 *
		 */

		public function insert_dismiss_review() {
			$ajax_nonce = wp_create_nonce( "wpdef_dismiss_review" );
			?>
			<script type='text/javascript'>
                jQuery(document).ready(function ($) {
                    $(".wpdef-review.notice.is-dismissible").on("click", ".notice-dismiss", function (event) {
                        rsssl_dismiss_review('dismiss');
                    });
                    $(".wpdef-review.notice.is-dismissible").on("click", "#maybe-later", function (event) {
                        rsssl_dismiss_review('later');
                        $(this).closest('.wpdef-review').remove();
                    });
                    $(".wpdef-review.notice.is-dismissible").on("click", ".review-dismiss", function (event) {
                        rsssl_dismiss_review('dismiss');
                        $(this).closest('.wpdef-review').remove();
                    });

                    function rsssl_dismiss_review(type) {
                        var data = {
                            'action': 'dismiss_review_notice',
                            'type': type,
                            'token': '<?php echo $ajax_nonce; ?>'
                        };
                        $.post(ajaxurl, data, function (response) {
                        });
                    }
                });
			</script>
			<?php
		}

		/**
		 * Process the ajax dismissal of the review message.
		 *
		 * @since  2.1
		 *
		 * @access public
		 *
		 */

		public function dismiss_review_notice_callback() {
			$type = isset( $_POST['type'] ) ? $_POST['type'] : false;

			if ( $type === 'dismiss' ) {
				update_option( 'wpdef_review_notice_shown', true );
			}
			if ( $type === 'later' ) {
				//Reset activation timestamp, notice will show again in one month.
				update_option( 'wpdef_activation_time', time() );
			}

			wp_die(); // this is required to terminate immediately and return a proper response
		}

		/**
		 * Dismiss review notice with get, which is more stable
		 */

		public function process_get_review_dismiss(){
			if (isset( $_GET['wpdef_dismiss_review'] ) ){
				update_option( 'wpdef_review_notice_shown', true );
			}
		}
	}
}
