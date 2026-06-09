<?php
/**
 * Plugin Name: Physical Activity Signup and Administration Tool
 * Plugin URI:
 * Description: A WordPress plugin for managing public signups and administration for physical activities, sessions, classes, and events.
 * Version: 0.1.0
 * Author: Project Contributors
 * Text Domain: pasat
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package PASAT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PASAT_VERSION', '0.1.0' );
define( 'PASAT_DB_VERSION', '0.1.0' );
define( 'PASAT_PLUGIN_FILE', __FILE__ );
define( 'PASAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PASAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PASAT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'PASAT\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = PASAT_PLUGIN_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'PASAT\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PASAT\\Deactivator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		PASAT\Plugin::instance()->boot();
	}
);
