<?php
namespace PASAT\Admin;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsPage {
	public static function register_settings(): void {
		register_setting( 'pasat_settings', 'pasat_settings', array( 'sanitize_callback' => array( self::class, 'sanitize' ) ) );
	}

	public static function sanitize( array $input ): array {
		$defaults = Helpers::default_settings();
		$output   = array();
		$checkboxes = array(
			'default_waitlist_enabled',
			'require_consent',
			'pasat_strict_email_delivery',
			'allow_duplicate_email_per_activity',
			'map_enabled',
		);

		foreach ( $defaults as $key => $default ) {
			if ( in_array( $key, $checkboxes, true ) ) {
				$output[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
				continue;
			}
			$value = $input[ $key ] ?? $default;
			if ( is_int( $default ) ) {
				$output[ $key ] = absint( $value );
			} elseif ( str_contains( $key, 'body' ) || str_contains( $key, 'text' ) ) {
				$output[ $key ] = sanitize_textarea_field( $value );
			} else {
				$output[ $key ] = sanitize_text_field( $value );
			}
		}

		$output['erasure_mode'] = in_array( $output['erasure_mode'], array( 'anonymize', 'delete' ), true ) ? $output['erasure_mode'] : 'anonymize';

		return $output;
	}

	public static function render(): void {
		if ( ! current_user_can( 'pasat_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage PASAT settings.', 'pasat' ) );
		}
		$settings = Helpers::settings();
		$pages    = get_pages();
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Settings', 'pasat' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'pasat_settings' ); ?>
				<table class="form-table" role="presentation">
					<?php self::text_row( 'organization_name', __( 'Organization Name', 'pasat' ), $settings ); ?>
					<tr><th><?php esc_html_e( 'Public Page', 'pasat' ); ?></th><td><select name="pasat_settings[public_page_id]"><option value="0"><?php esc_html_e( 'Select page', 'pasat' ); ?></option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( (int) $settings['public_page_id'], (int) $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select></td></tr>
					<?php self::text_row( 'activity_label', __( 'Activity Label', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'host_label', __( 'Host Label', 'pasat' ), $settings ); ?>
					<?php self::number_row( 'default_season_year', __( 'Default Season Year', 'pasat' ), $settings ); ?>
					<?php self::number_row( 'default_capacity', __( 'Default Capacity', 'pasat' ), $settings ); ?>
					<?php self::checkbox_row( 'default_waitlist_enabled', __( 'Default Waitlist Enabled', 'pasat' ), $settings ); ?>
					<?php self::checkbox_row( 'require_consent', __( 'Require Consent Checkbox', 'pasat' ), $settings ); ?>
					<?php self::textarea_row( 'consent_text', __( 'Consent Text', 'pasat' ), $settings ); ?>
					<?php self::textarea_row( 'default_warning_text', __( 'Default Warning Text', 'pasat' ), $settings ); ?>
					<?php self::checkbox_row( 'pasat_strict_email_delivery', __( 'Fail Signup If E-mail Fails', 'pasat' ), $settings ); ?>
					<?php self::checkbox_row( 'allow_duplicate_email_per_activity', __( 'Allow Duplicate E-mail Per Activity', 'pasat' ), $settings ); ?>
					<?php self::number_row( 'retention_period_days', __( 'Retention Period Days', 'pasat' ), $settings ); ?>
					<tr><th><?php esc_html_e( 'Erasure Mode', 'pasat' ); ?></th><td><select name="pasat_settings[erasure_mode]"><option value="anonymize" <?php selected( $settings['erasure_mode'], 'anonymize' ); ?>><?php esc_html_e( 'Anonymize', 'pasat' ); ?></option><option value="delete" <?php selected( $settings['erasure_mode'], 'delete' ); ?>><?php esc_html_e( 'Delete', 'pasat' ); ?></option></select></td></tr>
				</table>
				<h2><?php esc_html_e( 'E-mail Templates', 'pasat' ); ?></h2>
				<p><?php esc_html_e( 'Available placeholders: {organization_name}, {activity_title}, {activity_date}, {activity_time}, {venue_name}, {participant_name}, {signup_status}, {cancellation_url}, {site_name}, {site_url}.', 'pasat' ); ?></p>
				<table class="form-table" role="presentation">
					<?php self::text_row( 'sender_name', __( 'Sender Name', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'confirmation_subject', __( 'Confirmation Subject', 'pasat' ), $settings ); ?>
					<?php self::textarea_row( 'confirmation_body', __( 'Confirmation Body', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'cancellation_subject', __( 'Cancellation Subject', 'pasat' ), $settings ); ?>
					<?php self::textarea_row( 'cancellation_body', __( 'Cancellation Body', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'waitlist_promotion_subject', __( 'Waitlist Promotion Subject', 'pasat' ), $settings ); ?>
					<?php self::textarea_row( 'waitlist_promotion_body', __( 'Waitlist Promotion Body', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'activity_cancelled_subject', __( 'Activity Cancellation Subject', 'pasat' ), $settings ); ?>
					<?php self::textarea_row( 'activity_cancelled_body', __( 'Activity Cancellation Body', 'pasat' ), $settings ); ?>
				</table>
				<?php submit_button(); ?>
			</form>
			<h2><?php esc_html_e( 'Legacy Import', 'pasat' ); ?></h2>
			<p><?php esc_html_e( 'This plugin is a WordPress-native rewrite. Legacy data can be imported later from structured JSON or CSV exports. Legacy passwords and external authentication data should be replaced with WordPress users and roles.', 'pasat' ); ?></p>
		</div>
		<?php
	}

	private static function text_row( string $key, string $label, array $settings ): void {
		echo '<tr><th><label for="pasat-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input class="regular-text" id="pasat-' . esc_attr( $key ) . '" name="pasat_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $settings[ $key ] ?? '' ) . '"></td></tr>';
	}

	private static function textarea_row( string $key, string $label, array $settings ): void {
		echo '<tr><th><label for="pasat-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><textarea class="large-text" rows="5" id="pasat-' . esc_attr( $key ) . '" name="pasat_settings[' . esc_attr( $key ) . ']">' . esc_textarea( $settings[ $key ] ?? '' ) . '</textarea></td></tr>';
	}

	private static function number_row( string $key, string $label, array $settings ): void {
		echo '<tr><th><label for="pasat-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input type="number" id="pasat-' . esc_attr( $key ) . '" name="pasat_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) ( $settings[ $key ] ?? 0 ) ) . '"></td></tr>';
	}

	private static function checkbox_row( string $key, string $label, array $settings ): void {
		echo '<tr><th>' . esc_html( $label ) . '</th><td><label><input type="checkbox" name="pasat_settings[' . esc_attr( $key ) . ']" value="1" ' . checked( (int) ( $settings[ $key ] ?? 0 ), 1, false ) . '> ' . esc_html__( 'Enabled', 'pasat' ) . '</label></td></tr>';
	}
}
