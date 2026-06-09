<?php
namespace PASAT\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Renderer {
	public static function render( string $template, array $data = array() ): string {
		$file = PASAT_PLUGIN_DIR . 'templates/' . ltrim( $template, '/' );
		if ( ! is_readable( $file ) ) {
			return '';
		}

		ob_start();
		$pasat = $data;
		include $file;
		return (string) ob_get_clean();
	}
}
