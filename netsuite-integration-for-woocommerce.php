<?php
/**
 * Plugin Name: NetSuite Integration for WooCommerce
 * Description: Plugin will be used to add/update woocommerce orders and customers to Netsuite.
 * Version: 1.0.3
 * Author: Manish Gautam
 * Text Domain: TMWNI
 * WC requires at least: 2.2
 * WC tested up to: 3.5.1
 * Woo: 8277071:774402b21708c84be5bee56906b069f1

 */

defined( 'ABSPATH' ) || exit;

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '774402b21708c84be5bee56906b069f1', '8277071' );

// // WC active check
// if ( ! is_woocommerce_active() ) {
// 	return;
// }

	// Put your plugin code here
add_action('plugins_loaded', 'init_tm_netsuite_integration', 1);

function init_tm_netsuite_integration() {
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( '/netsuite-integration-for-woocommerce/netsuite-integration-for-woocommerce.php', true );
	}

	define('TMWNI_DIR', plugin_dir_path(__FILE__));
	define('TMWNI_URL', plugin_dir_url(__FILE__));

	require_once plugin_dir_path(__FILE__) . 'inc/tmwni-settings.php';

	$TMWNI_OPTIONS = TMWNI_Settings::getTabSettings();
	$GLOBALS['TMWNI_OPTIONS'] = $TMWNI_OPTIONS;

	if (is_admin()) {
		require_once TMWNI_DIR . 'inc/admin/admin-loader.php';
	}

	if (!empty($TMWNI_OPTIONS)) {
		//load the main file
		require_once plugin_dir_path(__FILE__) . 'inc/loader.php';
		// // Global for backwards compatibility.
		$GLOBALS['TMWNI_Loader'] = TMWNI_Loader();
	}
}



	// run the install scripts upon plugin activation
register_activation_hook(__FILE__, 'install_tm_netsuite_integration_plugin');

	// function for creating log table "in8sync_log"
function install_tm_netsuite_integration_plugin() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		//create log database table
		global $wpdb;
		$table_name = $wpdb->prefix . 'tm_woo_netsuite_logs';
		$charset_collate = '';

		if (!empty($wpdb->charset)) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}
		// Check if table empty
		if ($wpdb->get_var($wpdb->prepare('show tables like %s', $table_name)) != $table_name) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$sql = 'CREATE TABLE `' . $table_name . "` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`operation` varchar(100) NOT NULL,
			`status` tinyint(4) NOT NULL,
			`notes` text NOT NULL,
			`woo_object_id` int(11) NULL,
			PRIMARY KEY (`id`)
		) $charset_collate;";
		dbDelta($sql);
		}
	} else {
		die('Your store needs to be Woocomerce ready to activate this plugin.');
	}

}
