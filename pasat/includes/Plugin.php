<?php
namespace PASAT;

use PASAT\Admin\AdminMenu;
use PASAT\Admin\SettingsPage;
use PASAT\Frontend\Assets as FrontendAssets;
use PASAT\Frontend\Shortcodes;
use PASAT\Privacy\Eraser;
use PASAT\Privacy\Exporter;
use PASAT\Privacy\Retention;
use PASAT\REST\Routes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ), 1 );
		add_action( 'init', array( Shortcodes::class, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( FrontendAssets::class, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( AdminMenu::class, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( AdminMenu::class, 'register' ) );
		add_action( 'admin_init', array( SettingsPage::class, 'register_settings' ) );
		add_action( 'rest_api_init', array( Routes::class, 'register' ) );
		add_action( 'admin_post_pasat_cancel_signup', array( Shortcodes::class, 'handle_cancellation_link' ) );
		add_action( 'admin_post_nopriv_pasat_cancel_signup', array( Shortcodes::class, 'handle_cancellation_link' ) );
		add_action( 'pasat_daily_retention_cleanup', array( Retention::class, 'run_scheduled' ) );

		add_filter( 'wp_privacy_personal_data_exporters', array( Exporter::class, 'register' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( Eraser::class, 'register' ) );
	}

	public function load_textdomain(): void {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- PASAT supports manual installs outside WordPress.org with bundled language files.
		load_plugin_textdomain( 'pasat', false, dirname( PASAT_PLUGIN_BASENAME ) . '/languages' );
	}
}
