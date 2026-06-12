<?php
declare(strict_types=1);

$pairs = array(
	array( 'Text on surface', '#162b35', '#ffffff', 4.5 ),
	array( 'Muted text on surface', '#5b6b73', '#ffffff', 4.5 ),
	array( 'Muted text on alternate surface', '#5b6b73', '#f8fbfc', 4.5 ),
	array( 'Body description on surface', '#2f424b', '#ffffff', 4.5 ),
	array( 'Primary link on surface', '#165a72', '#ffffff', 4.5 ),
	array( 'Primary button text', '#ffffff', '#165a72', 4.5 ),
	array( 'Secondary button hover text', '#0d4154', '#edf5f7', 4.5 ),
	array( 'Default status pill', '#165a72', '#e9f4f7', 4.5 ),
	array( 'Success status pill', '#14633b', '#eaf7ef', 4.5 ),
	array( 'Warning status pill', '#775400', '#fff5d6', 4.5 ),
	array( 'Danger status pill', '#8a1f11', '#fdeceb', 4.5 ),
	array( 'Board refreshing status', '#165a72', '#e9f4f7', 4.5 ),
	array( 'Board error status', '#8a1f11', '#fdeceb', 4.5 ),
	array( 'Year badge', '#14633b', '#eaf7ef', 4.5 ),
	array( 'Gold placement badge', '#6e4a00', '#fff5d6', 4.5 ),
	array( 'Silver placement badge', '#4f626b', '#eef3f6', 4.5 ),
	array( 'Bronze placement badge', '#77512f', '#f7efe7', 4.5 ),
);

$failed = false;

foreach ( $pairs as $pair ) {
	list( $label, $foreground, $background, $minimum ) = $pair;
	$ratio = contrast_ratio( $foreground, $background );
	printf( "%-34s %.2f:1\n", $label, $ratio );

	if ( $ratio < $minimum ) {
		fwrite( STDERR, sprintf( "%s contrast %.2f is below %.2f.\n", $label, $ratio, $minimum ) );
		$failed = true;
	}
}

if ( $failed ) {
	exit( 1 );
}

function contrast_ratio( string $foreground, string $background ): float {
	$foreground_luminance = relative_luminance( $foreground );
	$background_luminance = relative_luminance( $background );

	return ( max( $foreground_luminance, $background_luminance ) + 0.05 ) / ( min( $foreground_luminance, $background_luminance ) + 0.05 );
}

function relative_luminance( string $hex ): float {
	$rgb = hex_to_rgb( $hex );

	return 0.2126 * linear_channel( $rgb[0] ) + 0.7152 * linear_channel( $rgb[1] ) + 0.0722 * linear_channel( $rgb[2] );
}

function hex_to_rgb( string $hex ): array {
	$hex = ltrim( $hex, '#' );

	if ( 6 !== strlen( $hex ) ) {
		throw new InvalidArgumentException( 'Expected a six-digit hex color.' );
	}

	return array(
		hexdec( substr( $hex, 0, 2 ) ) / 255,
		hexdec( substr( $hex, 2, 2 ) ) / 255,
		hexdec( substr( $hex, 4, 2 ) ) / 255,
	);
}

function linear_channel( float $channel ): float {
	return $channel <= 0.03928 ? $channel / 12.92 : ( ( $channel + 0.055 ) / 1.055 ) ** 2.4;
}
