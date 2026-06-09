<?php
namespace PASAT\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activity {
	public const STATUS_DRAFT = 'draft';
	public const STATUS_PUBLISHED = 'published';
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_ARCHIVED = 'archived';
}
