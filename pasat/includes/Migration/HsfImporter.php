<?php
namespace PASAT\Migration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HsfImporter {
	public function supported_sources(): array {
		return array( 'venues', 'activities', 'participants', 'signups', 'hosts', 'winners' );
	}

	public function describe(): string {
		return __( 'PASAT can be extended to import structured JSON or CSV exports from a legacy standalone signup system. Passwords and external authentication records should be mapped to WordPress users instead of imported directly.', 'pasat' );
	}
}
