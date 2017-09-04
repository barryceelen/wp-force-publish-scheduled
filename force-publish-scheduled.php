<?php
/**
 * Main plugin file
 *
 * @package   Force_Publish_Scheduled
 * @author    Barry Ceelen <b@rryceelen.com>
 * @license   GPL-3.0+
 * @link      https://github.com/barryceelen/wp-force-publish-scheduled
 * @copyright 2017 Barry Ceelen
 *
 * Plugin Name:       Force Publish Scheduled Posts
 * Plugin URI:        http://github.com/barryceelen/wp-force-publish-scheduled
 * Description:       Publish or unschedule multiple scheduled posts at once via a Bulk Action.
 * Version:           1.1.0
 * Author:            Barry Ceelen
 * Author URI:        https://github.com/barryceelen
 * Text Domain:       force-publish-scheduled
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/barryceelen/wp-force-publish-scheduled
 */

// Don't load directly.
defined( 'ABSPATH' ) || die();

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-force-publish-scheduled-admin.php' );

	global $force_publish_scheduled_admin;
	$force_publish_scheduled_admin = Force_Publish_Scheduled_Admin::get_instance();

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	function force_publish_scheduled_load_textdomain() {
		load_plugin_textdomain( 'force-publish-scheduled', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	add_action( 'plugins_loaded', 'force_publish_scheduled_load_textdomain' );
}
