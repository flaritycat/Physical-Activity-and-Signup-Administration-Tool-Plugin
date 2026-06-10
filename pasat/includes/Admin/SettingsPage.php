<?php
namespace PASAT\Admin;

use PASAT\Email\Mailer;
use PASAT\Helpers;
use PASAT\Migration\HsfImporter;
use PASAT\Security\Nonces;

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
			'show_map_on_signup',
			'geocoding_enabled',
		);

		foreach ( $defaults as $key => $default ) {
			if ( in_array( $key, $checkboxes, true ) ) {
				$output[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
				continue;
			}
			$value = $input[ $key ] ?? $default;
			if ( is_int( $default ) ) {
				$output[ $key ] = absint( $value );
			} elseif ( 'map_tile_url' === $key ) {
				$output[ $key ] = sanitize_text_field( $value );
			} elseif ( str_contains( $key, 'url' ) || str_contains( $key, 'endpoint' ) ) {
				$output[ $key ] = esc_url_raw( $value );
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
		self::handle_post();
		$settings = Helpers::settings();
		$pages    = get_pages();
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Settings', 'pasat' ); ?></h1>
			<?php self::notices(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'pasat_settings' ); ?>
				<table class="form-table" role="presentation">
					<?php self::text_row( 'organization_name', __( 'Organization Name', 'pasat' ), $settings ); ?>
					<tr><th><?php esc_html_e( 'Public Page', 'pasat' ); ?></th><td><select name="pasat_settings[public_page_id]"><option value="0"><?php esc_html_e( 'Select page', 'pasat' ); ?></option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( (int) $settings['public_page_id'], (int) $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select></td></tr>
					<?php self::logo_row( $settings ); ?>
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
				<h2><?php esc_html_e( 'Map Settings', 'pasat' ); ?></h2>
				<p><?php esc_html_e( 'PASAT can display open-source venue maps using Leaflet and OpenStreetMap-compatible tiles. Address geocoding is optional and should respect the selected provider usage policy.', 'pasat' ); ?></p>
				<table class="form-table" role="presentation">
					<?php self::checkbox_row( 'map_enabled', __( 'Enable Interactive Venue Maps', 'pasat' ), $settings ); ?>
					<?php self::checkbox_row( 'show_map_on_signup', __( 'Show Map On Signup Page By Default', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'map_tile_url', __( 'Map Tile URL', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'map_tile_attribution', __( 'Map Attribution', 'pasat' ), $settings ); ?>
					<?php self::number_row( 'map_default_height', __( 'Default Map Height', 'pasat' ), $settings ); ?>
					<?php self::number_row( 'map_default_zoom', __( 'Default Map Zoom', 'pasat' ), $settings ); ?>
					<?php self::checkbox_row( 'geocoding_enabled', __( 'Enable Address Geocoding', 'pasat' ), $settings ); ?>
					<?php self::text_row( 'geocoding_endpoint', __( 'Geocoding Endpoint', 'pasat' ), $settings ); ?>
					<?php self::number_row( 'geocoding_throttle_seconds', __( 'Geocoding Throttle Seconds', 'pasat' ), $settings ); ?>
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
			<h2><?php esc_html_e( 'Mail Delivery Test', 'pasat' ); ?></h2>
			<p><?php esc_html_e( 'Send a test e-mail through WordPress mail to confirm PASAT notifications can leave this site.', 'pasat' ); ?></p>
			<form method="post" class="pasat-admin-form">
				<?php Nonces::field( 'settings_mail_test' ); ?>
				<input type="hidden" name="pasat_action" value="send_test_email">
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pasat-test-email"><?php esc_html_e( 'Test Recipient', 'pasat' ); ?></label></th>
						<td><input class="regular-text" type="email" id="pasat-test-email" name="test_email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required></td>
					</tr>
				</table>
				<?php submit_button( __( 'Send Test E-mail', 'pasat' ), 'secondary' ); ?>
			</form>
			<h2><?php esc_html_e( 'Legacy Import', 'pasat' ); ?></h2>
			<p><?php esc_html_e( 'This plugin is a WordPress-native rewrite. Import structured JSON or CSV exports for venues, activities, participants, signups, and host assignments. Legacy passwords and external authentication data should be replaced with WordPress users and roles.', 'pasat' ); ?></p>
			<form method="post" enctype="multipart/form-data" class="pasat-admin-form">
				<?php Nonces::field( 'settings_import' ); ?>
				<input type="hidden" name="pasat_action" value="legacy_import">
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pasat-import-source"><?php esc_html_e( 'Import Type', 'pasat' ); ?></label></th>
						<td>
							<select id="pasat-import-source" name="import_source" required>
								<?php foreach ( ( new HsfImporter() )->importable_sources() as $source ) : ?>
									<option value="<?php echo esc_attr( $source ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $source ) ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="pasat-import-file"><?php esc_html_e( 'JSON or CSV File', 'pasat' ); ?></label></th>
						<td>
							<input type="file" id="pasat-import-file" name="import_file" accept=".json,.csv,application/json,text/csv" required>
							<p class="description"><?php esc_html_e( 'Maximum file size: 2 MB. CSV files must include a header row. JSON files may be an array of rows or an object containing the selected import type.', 'pasat' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Run Import', 'pasat' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	private static function handle_post(): void {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== $request_method ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['pasat_action'] ?? '' ) );
		if ( 'send_test_email' === $action ) {
			check_admin_referer( Nonces::action( 'settings_mail_test' ), '_pasat_nonce' );

			$email = sanitize_email( wp_unslash( $_POST['test_email'] ?? '' ) );
			$sent  = is_email( $email ) && Mailer::send_test_email( $email );
			if ( $sent ) {
				update_option( 'pasat_mail_last_test_at', Helpers::now() );
			}

			wp_safe_redirect(
				add_query_arg(
					'pasat_mail_test',
					$sent ? 'success' : 'failed',
					admin_url( 'admin.php?page=pasat-settings' )
				)
			);
			exit;
		}

		if ( 'legacy_import' === $action ) {
			check_admin_referer( Nonces::action( 'settings_import' ), '_pasat_nonce' );
			$source = sanitize_key( wp_unslash( $_POST['import_source'] ?? '' ) );
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload metadata is validated by the importer and not persisted directly.
			$file   = isset( $_FILES['import_file'] ) && is_array( $_FILES['import_file'] ) ? $_FILES['import_file'] : array();
			$result = ( new HsfImporter() )->import_uploaded_file( $file, $source );
			$key    = 'pasat_import_result_' . get_current_user_id();

			set_transient(
				$key,
				is_wp_error( $result )
					? array( 'error' => $result->get_error_message() )
					: $result,
				5 * MINUTE_IN_SECONDS
			);

			wp_safe_redirect(
				add_query_arg(
					'pasat_import',
					is_wp_error( $result ) ? 'failed' : 'success',
					admin_url( 'admin.php?page=pasat-settings' )
				)
			);
			exit;
		}
	}

	private static function notices(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only admin notice flag set after a nonce-protected POST redirect.
		$result = sanitize_key( wp_unslash( $_GET['pasat_mail_test'] ?? '' ) );
		if ( 'success' === $result ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'PASAT test e-mail was accepted by WordPress mail.', 'pasat' ) . '</p></div>';
		}
		if ( 'failed' === $result ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'PASAT test e-mail could not be sent. Check the site mail configuration or SMTP plugin.', 'pasat' ) . '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only admin notice flag set after a nonce-protected POST redirect.
		$import = sanitize_key( wp_unslash( $_GET['pasat_import'] ?? '' ) );
		if ( '' !== $import ) {
			$key     = 'pasat_import_result_' . get_current_user_id();
			$summary = get_transient( $key );
			delete_transient( $key );

			if ( is_array( $summary ) && ! empty( $summary['error'] ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $summary['error'] ) . '</p></div>';
				return;
			}

			if ( is_array( $summary ) ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(
					sprintf(
						/* translators: 1: imported row count, 2: skipped row count. */
						__( 'PASAT import complete. Imported %1$d rows and skipped %2$d rows.', 'pasat' ),
						absint( $summary['imported'] ?? 0 ),
						absint( $summary['skipped'] ?? 0 )
					)
				) . '</p>';

				$errors = array_slice( array_map( 'sanitize_text_field', $summary['errors'] ?? array() ), 0, 5 );
				if ( $errors ) {
					echo '<ul>';
					foreach ( $errors as $error ) {
						echo '<li>' . esc_html( $error ) . '</li>';
					}
					echo '</ul>';
				}
				echo '</div>';
			}
		}
	}

	private static function text_row( string $key, string $label, array $settings ): void {
		echo '<tr><th><label for="pasat-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input class="regular-text" id="pasat-' . esc_attr( $key ) . '" name="pasat_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $settings[ $key ] ?? '' ) . '"></td></tr>';
	}

	private static function textarea_row( string $key, string $label, array $settings ): void {
		echo '<tr><th><label for="pasat-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><textarea class="large-text" rows="5" id="pasat-' . esc_attr( $key ) . '" name="pasat_settings[' . esc_attr( $key ) . ']">' . esc_textarea( $settings[ $key ] ?? '' ) . '</textarea></td></tr>';
	}

	private static function logo_row( array $settings ): void {
		$logo_id = absint( $settings['poster_logo_id'] ?? 0 );
		echo '<tr><th><label for="pasat-poster-logo-id">' . esc_html__( 'Poster Logo', 'pasat' ) . '</label></th><td>';
		echo '<input type="hidden" id="pasat-poster-logo-id" data-pasat-logo-id name="pasat_settings[poster_logo_id]" value="' . esc_attr( (string) $logo_id ) . '">';
		echo '<div class="pasat-logo-preview" data-pasat-logo-preview data-empty-label="' . esc_attr__( 'No logo selected', 'pasat' ) . '">';
		if ( $logo_id ) {
			echo wp_kses_post( wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'pasat-logo-preview__image' ) ) );
		}
		echo '</div>';
		echo '<button type="button" class="button" data-pasat-logo-select>' . esc_html__( 'Choose Logo', 'pasat' ) . '</button> ';
		echo '<button type="button" class="button" data-pasat-logo-remove>' . esc_html__( 'Remove Logo', 'pasat' ) . '</button>';
		echo '<p class="description">' . esc_html__( 'Used on activity poster PDFs. PNG and JPEG images are supported when the WordPress image editor is available; JPEG is the safest fallback.', 'pasat' ) . '</p>';
		echo '</td></tr>';
	}

	private static function number_row( string $key, string $label, array $settings ): void {
		echo '<tr><th><label for="pasat-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td><input type="number" id="pasat-' . esc_attr( $key ) . '" name="pasat_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) ( $settings[ $key ] ?? 0 ) ) . '"></td></tr>';
	}

	private static function checkbox_row( string $key, string $label, array $settings ): void {
		echo '<tr><th>' . esc_html( $label ) . '</th><td><label><input type="checkbox" name="pasat_settings[' . esc_attr( $key ) . ']" value="1" ' . checked( (int) ( $settings[ $key ] ?? 0 ), 1, false ) . '> ' . esc_html__( 'Enabled', 'pasat' ) . '</label></td></tr>';
	}
}
