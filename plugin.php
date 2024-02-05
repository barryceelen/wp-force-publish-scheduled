<?php
/**
 * Main plugin file
 *
 * Plugin Name: Force Publish Scheduled Posts
 * Description: Unschedule or publish multiple scheduled posts at once via a bulk action in the WordPress admin.
 * Version: 2.0.0
 * Author: Plugin Pizza, Barry Ceelen
 * Author URI: https://plugin.pizza
 * Plugin URI: https://github.com/plugin-pizza/wp-force-publish-scheduled
 * Text Domain: force-publish-scheduled
 * License: GPLv3+
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/plugin-pizza/wp-force-publish-scheduled
 *
 * @package PluginPizza\ForcePublishScheduled
 */

defined( 'ABSPATH' ) || exit;

if ( is_admin() ) {

	require_once plugin_dir_path( __FILE__ ) . 'i18n.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin.php';
}
