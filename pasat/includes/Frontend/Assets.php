<?php
namespace PASAT\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	public static function register(): void {
		wp_register_style( 'pasat-public', PASAT_PLUGIN_URL . 'assets/css/public.css', array(), PASAT_VERSION );
		wp_register_script( 'pasat-public', PASAT_PLUGIN_URL . 'assets/js/public.js', array(), PASAT_VERSION, true );
		wp_localize_script(
			'pasat-public',
			'PASAT_PUBLIC',
			array(
				'restUrl' => esc_url_raw( rest_url( 'pasat/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	public static function enqueue(): void {
		wp_enqueue_style( 'pasat-public' );
		wp_enqueue_script( 'pasat-public' );
	}
}
