<?php
declare(strict_types=1);

$root = dirname( __DIR__ );

$checks = array(
	array(
		'file'    => 'pasat/templates/public/signup-form.php',
		'label'   => 'Signup notice live region',
		'pattern' => '/data-pasat-notice-region[^>]+aria-live="polite"[^>]+aria-atomic="true"/',
	),
	array(
		'file'    => 'pasat/templates/public/signup-form.php',
		'label'   => 'Signup activity summary live region',
		'pattern' => '/data-pasat-signup-summary.*?role="status".*?aria-live="polite".*?aria-atomic="true"/s',
	),
	array(
		'file'    => 'pasat/templates/public/signup-form.php',
		'label'   => 'Signup activity select controls summary',
		'pattern' => '/name="activity_id" required aria-controls="\<\?php echo esc_attr\( \$pasat_summary_id \); \?>"/',
	),
	array(
		'file'    => 'pasat/assets/js/public.js',
		'label'   => 'AJAX-created signup notice live region',
		'pattern' => '/region\.setAttribute\(\s*[\'"]aria-live[\'"],\s*[\'"]polite[\'"]\s*\).*region\.setAttribute\(\s*[\'"]aria-atomic[\'"],\s*[\'"]true[\'"]\s*\)/s',
	),
	array(
		'file'    => 'pasat/templates/public/activity-list.php',
		'label'   => 'Activity filter controls describe live result count',
		'pattern' => '/data-pasat-filter-search.*?aria-describedby="\<\?php echo esc_attr\( \$pasat_filter_count_id \); \?>"/s',
	),
	array(
		'file'    => 'pasat/templates/public/activity-list.php',
		'label'   => 'Activity filter live result count',
		'pattern' => '/data-pasat-filter-count.*?role="status".*?aria-live="polite".*?aria-atomic="true"/s',
	),
	array(
		'file'    => 'pasat/templates/public/activity-list.php',
		'label'   => 'Activity board atomic refresh status',
		'pattern' => '/data-pasat-board-updated[^>]+role="status"[^>]+aria-live="polite"[^>]+aria-atomic="true"/',
	),
	array(
		'file'    => 'pasat/assets/js/public.js',
		'label'   => 'JS-created board refresh status is atomic',
		'pattern' => '/updated\.setAttribute\(\s*[\'"]aria-atomic[\'"],\s*[\'"]true[\'"]\s*\)/',
	),
	array(
		'file'    => 'pasat/templates/public/venue-map.php',
		'label'   => 'Venue map status live region',
		'pattern' => '/data-pasat-map-status[^>]+role="status"[^>]+aria-live="polite"[^>]+aria-atomic="true"/',
	),
	array(
		'file'    => 'pasat/templates/public/venue-map.php',
		'label'   => 'Venue map buttons expose pressed state',
		'pattern' => '/data-pasat-map-focus="\<\?php echo esc_attr\( \(string\) \$pasat_venue\[\'id\'\] \); \?>"[^>]+aria-pressed="false"/',
	),
	array(
		'file'    => 'pasat/assets/js/public.js',
		'label'   => 'Venue map pressed state updates',
		'pattern' => '/button\.setAttribute\(\s*[\'"]aria-pressed[\'"],\s*active \? [\'"]true[\'"] : [\'"]false[\'"]\s*\)/',
	),
	array(
		'file'    => 'pasat/assets/js/public.js',
		'label'   => 'Venue map status announces selected venue',
		'pattern' => '/mapLabel\(\s*[\'"]showingVenue[\'"],\s*[\'"]Showing %s on the map\.[\'"]\s*\)/',
	),
	array(
		'file'    => 'pasat/assets/js/public.js',
		'label'   => 'Venue popup directions have venue-specific accessible names',
		'pattern' => '/directions\.setAttribute\(\s*[\'"]aria-label[\'"],\s*templateLabel\(mapLabel\(\s*[\'"]directionsToVenue[\'"],\s*[\'"]Directions to %s[\'"]\s*\),\s*venue\.name\)\s*\)/',
	),
	array(
		'file'    => 'pasat/templates/public/my-signups.php',
		'label'   => 'My Signups notice live region',
		'pattern' => '/pasat-notice-region"[^>]+aria-live="polite"[^>]+aria-atomic="true"/',
	),
);

$failed = false;

foreach ( $checks as $check ) {
	$path = $root . '/' . $check['file'];
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, sprintf( "%s: %s is not readable.\n", $check['label'], $check['file'] ) );
		$failed = true;
		continue;
	}

	$contents = (string) file_get_contents( $path );
	if ( ! preg_match( $check['pattern'], $contents ) ) {
		fwrite( STDERR, sprintf( "%s: expected accessibility hook not found in %s.\n", $check['label'], $check['file'] ) );
		$failed = true;
		continue;
	}

	printf( "%s\n", $check['label'] );
}

if ( $failed ) {
	exit( 1 );
}
