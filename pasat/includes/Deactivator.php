<?php
namespace PASAT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivator {
	public static function deactivate(): void {
		Installer::clear_scheduled_events();
	}
}
