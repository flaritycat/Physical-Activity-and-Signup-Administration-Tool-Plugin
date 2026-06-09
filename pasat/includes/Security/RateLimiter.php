<?php
namespace PASAT\Security;

use PASAT\Helpers;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RateLimiter {
	public static function check( string $scope, int $limit = 10, int $window = 300 ): bool|WP_Error {
		$key   = 'pasat_rate_' . md5( $scope . Helpers::client_ip_hash() );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return new WP_Error( 'pasat_rate_limited', __( 'Please wait before trying again.', 'pasat' ), array( 'status' => 429 ) );
		}

		set_transient( $key, $count + 1, $window );
		return true;
	}
}
