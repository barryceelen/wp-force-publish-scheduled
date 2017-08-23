<?php
/**
 * Contains the plugin admin class.
 *
 * @package   Force_Publish_Scheduled
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-3.0+
 * @link      https://github.com/barryceelen/wp-force-publish-scheduled
 * @copyright 2017 Barry Ceelen
 */

/**
 * Admin plugin class.
 *
 * @package Sosovote
 * @author  Barry Ceelen <b@rryceelen.com>
 */
class Force_Publish_Scheduled_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize class.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		/*
		 * Add actions and filters.
		 *
		 * Note: We need access to the registered post types so let's do that late on admin_init.
		 */
		add_action( 'admin_init', array( $this, 'add_actions_and_filters' ), 999 );
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	function add_actions_and_filters() {

		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}

		/**
		 * Filter the post types to add the bulk action to.
		 *
		 * @since 1.0.0
		 *
		 * @param array $post_types A list of post types.
		 *                          Default post types with show_ui true.
		 */
		$post_types = apply_filters(
			'force_publish_scheduled_post_types',
			get_post_types(
				array(
					'show_ui' => true,
				)
			)
		);

		if ( ! empty( $post_types ) ) {

			// Show admin notice after publishing posts.
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );

			foreach ( $post_types as $post_type ) {

				$post_type_object = get_post_type_object( $post_type );

				if ( current_user_can( $post_type_object->cap->publish_posts ) ) {

					// Register bulk action.
					add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'register_bulk_action' ) );

					// Register bulk action.
					add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'bulk_action_handler' ), 10, 3 );
				}
			}
		}
	}

	/**
	 * Register bulk action.
	 *
	 * @since 1.0.0
	 * @param array $actions An array of the available bulk actions.
	 */
	function register_bulk_action( $actions ) {

		global $wp_query;

		// Look at $wp_query in stead of $_GET to make WPCS happy.
		if ( $wp_query->is_main_query() && 'future' === $wp_query->get( 'post_status' ) ) {
			$actions['force_publish_scheduled'] = __( 'Publish', 'force-publish-scheduled' );
		}

		return $actions;
	}

	/**
	 * Handle our bulk action.
	 *
	 * @since 1.0.0
	 * @param string $redirect_url The redirect URL.
	 * @param string $doaction     The action being taken.
	 * @param array  $post_ids     The items to take the action on.
	 * @return string The redirect URL.
	 */
	function bulk_action_handler( $redirect_url, $doaction, $post_ids ) {

		if ( 'force_publish_scheduled' !== $doaction ) {
			return $redirect_url;
		}

		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}

		$count   = 0;
		$user_id = get_current_user_id();

		foreach ( $post_ids as $post_id ) {

			$post             = get_post( $post_id );
			$post_type_object = get_post_type_object( $post->post_type );

			// Todo: Not sure how to handle capabilities here, feedback welcome.
			if ( current_user_can( $post_type_object->cap->publish_posts ) && ( (int) $post->post_author === $user_id || current_user_can( $post_type_object->cap->edit_others_posts ) ) ) {

				$postarr = array(
					'ID'            => $post_id,
					'post_date'     => '',
					'post_date_gmt' => '',
					'post_status'   => 'publish',
				);

				$id = wp_update_post( $postarr );

				if ( $id ) {
					$count++;
				}
			}
		}

		$redirect_url = add_query_arg(
			array(
				'force_publish_scheduled'       => $count,
				'force_publish_scheduled_nonce' => wp_create_nonce( 'force_publish_scheduled' ),
			),
			$redirect_url
		);

		return $redirect_url;
	}

	/**
	 * Show admin notice.
	 *
	 * @since 1.0.0
	 */
	function admin_notice() {

		$current_screen = get_current_screen();

		if ( 'edit' !== $current_screen->base ) {
			return;
		}

		if ( isset( $_GET['force_publish_scheduled_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['force_publish_scheduled_nonce'] ), 'force_publish_scheduled' ) && isset( $_GET['force_publish_scheduled'] ) ) { // WPCS: input var okay.

			$count = intval( $_GET['force_publish_scheduled'] ); // WPCS: input var okay.

			printf(
				/* translators: %d: number of published posts */
				'<div class="notice notice-success is-dismissible"><p>' . esc_html( _n( 'Published %d item.', 'Published %d items.', $count, 'force-publish-scheduled' ) ) . '</p></div>',
				intval( $count )
			);

		}
	}
}
