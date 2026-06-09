<?php
namespace PASAT\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Signup {
	public const STATUS_CONFIRMED = 'confirmed';
	public const STATUS_WAITLISTED = 'waitlisted';
	public const STATUS_CANCELLED = 'cancelled';
}
