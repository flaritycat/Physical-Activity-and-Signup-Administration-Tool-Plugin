<?php
namespace PASAT\Admin;

use PASAT\Database\AuditLogRepository;
use PASAT\Helpers;
use PASAT\Privacy\Retention;
use PASAT\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PrivacyPage {
	public static function render(): void {
		if ( ! current_user_can( 'pasat_run_privacy_tools' ) ) {
			wp_die( esc_html__( 'You do not have permission to run PASAT privacy tools.', 'pasat' ) );
		}
		self::handle_post();
		$settings = Helpers::settings();
		$audit    = ( new AuditLogRepository() )->recent( 20 );
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Privacy', 'pasat' ); ?></h1>
			<p><?php esc_html_e( 'PASAT integrates with WordPress personal data export and erasure tools. It also runs a daily retention cleanup using WP-Cron.', 'pasat' ); ?></p>
			<ul>
				<li>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d is the configured retention period in days. */
							__( 'Retention period: %d days', 'pasat' ),
							(int) $settings['retention_period_days']
						)
					);
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s is the configured participant erasure mode. */
							__( 'Erasure mode: %s', 'pasat' ),
							$settings['erasure_mode']
						)
					);
					?>
				</li>
			</ul>
			<form method="post">
				<?php Nonces::field( 'privacy' ); ?>
				<input type="hidden" name="pasat_action" value="run_retention">
				<?php submit_button( __( 'Run Retention Cleanup Now', 'pasat' ), 'secondary' ); ?>
			</form>
			<h2><?php esc_html_e( 'Recent Audit Log', 'pasat' ); ?></h2>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Action', 'pasat' ); ?></th><th><?php esc_html_e( 'Object', 'pasat' ); ?></th><th><?php esc_html_e( 'Message', 'pasat' ); ?></th><th><?php esc_html_e( 'Created', 'pasat' ); ?></th></tr></thead><tbody>
			<?php foreach ( $audit as $row ) : ?><tr><td><?php echo esc_html( $row['action'] ); ?></td><td><?php echo esc_html( trim( ( $row['object_type'] ?? '' ) . ' #' . ( $row['object_id'] ?? '' ) ) ); ?></td><td><?php echo esc_html( $row['message'] ?? '' ); ?></td><td><?php echo esc_html( Helpers::local_datetime( $row['created_at'] ) ); ?></td></tr><?php endforeach; ?>
			<?php if ( ! $audit ) : ?><tr><td colspan="4"><?php esc_html_e( 'No audit entries yet.', 'pasat' ); ?></td></tr><?php endif; ?>
			</tbody></table>
		</div>
		<?php
	}

	private static function handle_post(): void {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== $request_method ) {
			return;
		}
		Nonces::verify( 'privacy' );
		if ( 'run_retention' !== sanitize_key( wp_unslash( $_POST['pasat_action'] ?? '' ) ) ) {
			return;
		}
		Retention::run_cleanup();
		wp_safe_redirect( admin_url( 'admin.php?page=pasat-privacy&updated=1' ) );
		exit;
	}
}
