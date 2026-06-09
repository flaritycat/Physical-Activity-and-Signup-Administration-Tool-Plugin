<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php echo wp_kses_post( PASAT\Frontend\Renderer::render( 'public/activity-list.php', $pasat ) ); ?>
