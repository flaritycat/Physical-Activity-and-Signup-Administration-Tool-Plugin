<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( ! empty( $pasat['notice'] ) ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $pasat['notice'] ); ?></p></div>
<?php endif; ?>
