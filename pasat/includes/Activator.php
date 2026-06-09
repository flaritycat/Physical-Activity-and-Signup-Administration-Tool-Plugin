<?php
namespace PASAT;

use PASAT\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {
	public static function activate(): void {
		Schema::install();
		Installer::install_defaults();
		Capabilities::install();
		Installer::schedule_events();
	}
}
