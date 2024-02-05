<?php
/**
 * Contains internationalization functionality
 *
 * @package PluginPizza\ForcePublishScheduled
 */

namespace PluginPizza\ForcePublishScheduled\I18n;

// Load plugin textdomain.
add_action(
	'plugins_loaded',
	__NAMESPACE__ . '\load_textdomain'
);

/**
 * Load plugin textdomain.
 *
 * @return void
 */
function load_textdomain() {

	load_plugin_textdomain(
		'force-publish-scheduled',
		false,
		basename( dirname( __FILE__ ) ) . '/languages'
	);
}
