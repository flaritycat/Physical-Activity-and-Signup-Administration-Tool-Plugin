<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pasat-my-signups">
	<p><?php echo esc_html( $pasat['notice'] ?? '' ); ?></p>
	<form class="pasat-form" method="post">
		<input type="hidden" name="pasat_my_signups" value="1">
		<label><span><?php esc_html_e( 'E-mail', 'pasat' ); ?></span><input type="email" name="email" required autocomplete="email"></label>
		<button class="pasat-button" type="submit"><?php esc_html_e( 'Request Lookup', 'pasat' ); ?></button>
	</form>
</div>
