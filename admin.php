<?php
/**
 * Contains the admin functionality.
 *
 * @package PluginPizza\ForcePublishScheduled
 */

namespace PluginPizza\ForcePublishScheduled\Admin;

/**
 * Get the bulk actions.
 *
 * @return array List of bulk actions where the name is the key and the label is
 *               the value.
 */
function get_supported_bulk_actions() {

	return array(
		'force_publish_scheduled_publish' => __(
			'Publish',
			'force-publish-scheduled'
		),
		'force_publish_scheduled_draft'   => __(
			'Unschedule',
			'force-publish-scheduled'
		),
	);
}

/*
 * Add actions and filters.
 *
 * Note: We need access to the registered post types so let's run this action
 *       late on the admin_init hook.
 */
add_action(
	'admin_init',
	__NAMESPACE__ . '\add_actions_and_filters',
	999
);

/**
 * Add actions and filters.
 *
 * @return void
 */
function add_actions_and_filters() {

	/**
	 * Filter the post types to add the bulk action to.
	 *
	 * @param array $post_types A list of post types with show_ui true.
	 */
	$post_types = apply_filters(
		'force_publish_scheduled_post_types',
		get_post_types(
			array(
				'show_ui' => true,
			)
		)
	);

	if ( empty( $post_types ) ) {
		return;
	}

	// Show an admin notice if we're performing a bulk action.
	add_action( 'admin_notices', __NAMESPACE__ . '\admin_notice' );

	foreach ( $post_types as $post_type ) {

		$post_type_object = get_post_type_object( $post_type );

		if ( current_user_can( $post_type_object->cap->publish_posts ) ) {

			// Register bulk action.
			add_filter(
				"bulk_actions-edit-{$post_type}",
				__NAMESPACE__ . '\register_bulk_action'
			);

			// Register bulk action handler.
			add_filter(
				"handle_bulk_actions-edit-{$post_type}",
				__NAMESPACE__ . '\bulk_action_handler',
				10,
				3
			);
		}
	}
}

/**
 * Register bulk action.
 *
 * @param array $actions An array of the available bulk actions.
 * @return array The filtered list of bulk actions
 */
function register_bulk_action( $actions ) {

	global $wp_query;

	if ( $wp_query->is_main_query() &&
		'future' === $wp_query->get( 'post_status' ) ) {
			$actions = array_merge( $actions, get_supported_bulk_actions() );
	}

	return $actions;
}

/**
 * Handle our bulk action.
 *
 * @param string $redirect_url The redirect URL.
 * @param string $doaction     The action being taken.
 * @param array  $post_ids     The items to take the action on.
 * @return string The redirect URL.
 */
function bulk_action_handler( $redirect_url, $doaction, $post_ids ) {

	if ( ! array_key_exists( $doaction, get_supported_bulk_actions() ) ) {
		return $redirect_url;
	}

	if ( ! current_user_can( 'publish_posts' ) ) {
		return;
	}

	$count  = 0;
	$status = str_replace( 'force_publish_scheduled_', '', $doaction );

	if ( ! in_array( $status, [ 'publish', 'draft' ], true ) ) {
		return;
	}

	$capability = 'publish' === $status ? 'publish_post' : 'edit_post';

	foreach ( $post_ids as $post_id ) {

		if ( ! current_user_can( $capability, $post_id ) ) {
			continue;
		}

		$post = get_post( $post_id );

		if ( ! is_a( $post, '\WP_Post' ) ) {
			continue;
		}

		$postarr = array(
			'ID'            => $post_id,
			'post_date'     => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', 1 ),
			'post_status'   => $status,
		);

		$id = wp_update_post( $postarr );

		if ( is_wp_error( $id ) || empty( $id ) ) {
			continue;
		}

		$count++;
	}

	$redirect_url = add_query_arg(
		array(
			'force_publish_scheduled'       => $count,
			'force_publish_scheduled_type'  => $status,
			'force_publish_scheduled_nonce' => wp_create_nonce(
				'force_publish_scheduled'
			),
		),
		$redirect_url
	);

	return $redirect_url;
}

/**
 * Show an admin notice if we're performing a bulk action.
 *
 * @return void
 */
function admin_notice() {

	global $typenow;

	if ( ! $typenow ) {
		return;
	}

	if ( ! in_array( $typenow, get_post_types( array( 'show_ui' => true ) ), true ) ) {
		return;
	}

	if ( ! isset( $_GET['force_publish_scheduled'] ) ) {
		return;
	}

	if ( ! isset( $_GET['force_publish_scheduled_nonce'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( ! wp_verify_nonce( $_GET['force_publish_scheduled_nonce'], 'force_publish_scheduled' ) ) {
		return;
	}

	$current_screen = get_current_screen();

	if ( 'edit' !== $current_screen->base ) {
		return;
	}

	$bulk_counts = absint( $_GET['force_publish_scheduled'] );

	/*
	 * Let's replicate how core handles this for its trashed posts messages,
	 * see wp-admin/edit.php.
	 */
	$bulk_messages = array(
		'post'     => array(
			/* translators: %d: number of posts */
			'publish' => _n(
				'%d post published.',
				'%d posts published.',
				$bulk_counts,
				'force-publish-scheduled'
			),
			/* translators: %d: number of posts */
			'draft'   => _n(
				'%s post reverted to draft.',
				'%s posts reverted to draft.',
				$bulk_counts,
				'force-publish-scheduled'
			),
		),
		'page'     => array(
			/* translators: %d: number of pages */
			'publish' => _n(
				'%d page published.',
				'%d pages published.',
				$bulk_counts,
				'force-publish-scheduled'
			),
			/* translators: %d: number of pages */
			'draft'   => _n(
				'%s page reverted to draft.',
				'%s pages reverted to draft.',
				$bulk_counts,
				'force-publish-scheduled'
			),
		),
		'wp_block' => array(
			/* translators: %d: number of patterns */
			'publish' => _n(
				'%d pattern published.',
				'%d patterns published.',
				$bulk_counts,
				'force-publish-scheduled'
			),
			/* translators: %d: number of patterns */
			'draft'   => _n(
				'%s pattern reverted to draft.',
				'%s patterns reverted to draft.',
				$bulk_counts,
				'force-publish-scheduled'
			),
		),
	);

	/**
	 * Filters the bulk action updated messages.
	 *
	 * By default, custom post types use the messages for the 'post' post type.
	 *
	 * @param array[] $bulk_messages Arrays of messages, each keyed by the
	 *                               corresponding post type. Messages are keyed
	 *                               with 'published' or 'updated'.
	 * @param int[]   $bulk_counts   The item count for each message, used to
	 *                               build internationalized strings.
	 */
	$bulk_messages = apply_filters(
		'force_publish_scheduled_bulk_post_updated_messages',
		$bulk_messages,
		$bulk_counts
	);

	$bulk_action = 'draft';

	if ( isset( $_GET['force_publish_scheduled_type'] ) &&
		'publish' === $_GET['force_publish_scheduled_type'] ) {
			$bulk_action = 'publish';
	}

	$message = '';

	if ( isset( $bulk_messages[ $typenow ][ $bulk_action ] ) ) {

		$message = $bulk_messages[ $typenow ][ $bulk_action ];

	} elseif ( isset( $bulk_messages['post'][ $bulk_action ] ) ) {

		$message = $bulk_messages['post'][ $bulk_action ];
	}

	if ( ! empty( $message ) ) {

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
