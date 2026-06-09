<?php
namespace PASAT\Admin;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\AuditLogRepository;
use PASAT\Database\HostsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Capabilities;
use PASAT\Email\Mailer;
use PASAT\Helpers;
use PASAT\Security\Nonces;
use PASAT\Security\Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SignupsPage {
	public static function render(): void {
		if ( ! current_user_can( 'pasat_view_signups' ) ) {
			wp_die( esc_html__( 'You do not have permission to view signups.', 'pasat' ) );
		}

		if ( isset( $_GET['pasat_export'] ) ) {
			self::export_csv();
		}

		self::handle_post();
		$repo        = new SignupsRepository();
		$args        = self::current_query_args();
		$activity_id = (int) $args['activity_id'];
		$status      = (string) $args['status'];
		$search      = (string) $args['search'];
		$signups     = $repo->list( $args );
		$activity_args = array( 'limit' => 500 );
		if ( ! current_user_can( 'pasat_manage_all_activities' ) ) {
			$activity_args['assigned_user_id'] = get_current_user_id();
		}
		$activities  = ( new ActivitiesRepository() )->list( $activity_args );
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Signups', 'pasat' ); ?></h1>
			<?php self::notice(); ?>
			<form method="get" class="pasat-filter-row">
				<input type="hidden" name="page" value="pasat-signups">
				<select name="activity_id"><option value="0"><?php esc_html_e( 'All activities', 'pasat' ); ?></option><?php foreach ( $activities as $activity ) : ?><option value="<?php echo esc_attr( (string) $activity['id'] ); ?>" <?php selected( $activity_id, (int) $activity['id'] ); ?>><?php echo esc_html( $activity['title'] ); ?></option><?php endforeach; ?></select>
				<select name="status"><option value=""><?php esc_html_e( 'All statuses', 'pasat' ); ?></option><?php foreach ( SignupsRepository::STATUSES as $item ) : ?><option value="<?php echo esc_attr( $item ); ?>" <?php selected( $status, $item ); ?>><?php echo esc_html( ucfirst( $item ) ); ?></option><?php endforeach; ?></select>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name, e-mail, activity', 'pasat' ); ?>">
				<?php submit_button( __( 'Filter', 'pasat' ), 'secondary', '', false ); ?>
				<a class="button" href="<?php echo esc_url( add_query_arg( 'pasat_export', 'signups' ) ); ?>"><?php esc_html_e( 'Export CSV', 'pasat' ); ?></a>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Participant', 'pasat' ); ?></th><th><?php esc_html_e( 'E-mail', 'pasat' ); ?></th><th><?php esc_html_e( 'Activity', 'pasat' ); ?></th><th><?php esc_html_e( 'Status', 'pasat' ); ?></th><th><?php esc_html_e( 'Created', 'pasat' ); ?></th><th><?php esc_html_e( 'Actions', 'pasat' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $signups as $signup ) : ?>
					<tr>
						<td><?php echo esc_html( trim( $signup['first_name'] . ' ' . $signup['last_name'] ) ); ?></td>
						<td><?php echo esc_html( $signup['email'] ); ?></td>
						<td><?php echo esc_html( $signup['activity_title'] ); ?></td>
						<td><?php echo esc_html( $signup['status'] ); ?></td>
						<td><?php echo esc_html( Helpers::local_datetime( $signup['created_at'] ) ); ?></td>
						<td><?php self::signup_actions( $signup ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $signups ) : ?><tr><td colspan="6"><?php esc_html_e( 'No signups found.', 'pasat' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function handle_post(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['pasat_action'] ) ) {
			return;
		}
		if ( ! current_user_can( 'pasat_manage_signups' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage signups.', 'pasat' ) );
		}

		Nonces::verify( 'signup' );
		$repo   = new SignupsRepository();
		$audit  = new AuditLogRepository();
		$action = sanitize_key( $_POST['pasat_action'] );
		$id     = absint( $_POST['signup_id'] ?? 0 );
		if ( ! self::can_manage_signup( $id, $repo ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this signup.', 'pasat' ) );
		}

		if ( 'cancel' === $action ) {
			$before = $repo->get_with_details( $id );
			$result = $repo->cancel( $id, __( 'Cancelled by administrator.', 'pasat' ) );
			if ( $before ) {
				Mailer::send_cancellation_confirmation( $before );
			}
			if ( ! empty( $result['promoted_signup_id'] ) ) {
				$promoted = $repo->get_with_details( (int) $result['promoted_signup_id'] );
				if ( $promoted ) {
					$token = Tokens::generate_for_signup( (int) $promoted['id'] );
					$repo->update_token_hash( (int) $promoted['id'], $token['hash'] );
					Mailer::send_waitlist_promotion( $promoted, $token['token'] );
				}
			}
			$audit->log( 'signup.cancel', 'signup', $id, 'Admin cancelled signup' );
		}

		if ( 'confirm' === $action ) {
			$confirmed = $repo->confirm_waitlisted( $id );
			$signup = $repo->get_with_details( $id );
			if ( $confirmed && $signup ) {
				$token = Tokens::generate_for_signup( (int) $signup['id'] );
				$repo->update_token_hash( (int) $signup['id'], $token['hash'] );
				Mailer::send_waitlist_promotion( $signup, $token['token'] );
			}
			$audit->log( 'signup.confirm_waitlisted', 'signup', $id, 'Admin confirmed waitlisted signup' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pasat-signups&updated=1' ) );
		exit;
	}

	private static function can_manage_signup( int $signup_id, SignupsRepository $repo ): bool {
		if ( current_user_can( 'pasat_manage_all_activities' ) ) {
			return true;
		}

		$signup = $repo->get_with_details( $signup_id );
		return $signup && Capabilities::can_manage_activity( (int) $signup['activity_id'] );
	}

	private static function signup_actions( array $signup ): void {
		if ( ! current_user_can( 'pasat_manage_signups' ) ) {
			return;
		}
		if ( 'cancelled' !== $signup['status'] ) {
			self::button( (int) $signup['id'], 'cancel', __( 'Cancel', 'pasat' ) );
		}
		if ( 'waitlisted' === $signup['status'] ) {
			self::button( (int) $signup['id'], 'confirm', __( 'Confirm', 'pasat' ) );
		}
	}

	private static function button( int $id, string $action, string $label ): void {
		?>
		<form method="post" class="pasat-inline-form">
			<?php Nonces::field( 'signup' ); ?>
			<input type="hidden" name="signup_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<input type="hidden" name="pasat_action" value="<?php echo esc_attr( $action ); ?>">
			<button class="button-link" type="submit"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	private static function export_csv(): void {
		if ( ! current_user_can( 'pasat_export_participants' ) ) {
			wp_die( esc_html__( 'You do not have permission to export signup data.', 'pasat' ) );
		}
		$repo    = new SignupsRepository();
		$signups = $repo->list( self::current_query_args() );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pasat-signups.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'id', 'activity', 'first_name', 'last_name', 'email', 'status', 'created_at' ) );
		foreach ( $signups as $signup ) {
			fputcsv(
				$out,
				array(
					$signup['id'],
					Helpers::csv_cell( $signup['activity_title'] ),
					Helpers::csv_cell( $signup['first_name'] ),
					Helpers::csv_cell( $signup['last_name'] ),
					Helpers::csv_cell( $signup['email'] ),
					$signup['status'],
					$signup['created_at'],
				)
			);
		}
		exit;
	}

	private static function current_query_args(): array {
		$activity_id = absint( $_GET['activity_id'] ?? 0 );
		$args        = array(
			'activity_id' => $activity_id,
			'status'      => sanitize_key( $_GET['status'] ?? '' ),
			'search'      => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
		);

		if ( ! current_user_can( 'pasat_manage_all_activities' ) ) {
			$host_ids             = ( new HostsRepository() )->activity_ids_for_user( get_current_user_id() );
			$args['activity_ids'] = $host_ids;
			if ( $activity_id && ! in_array( $activity_id, $host_ids, true ) ) {
				$args['activity_ids'] = array();
			}
		}

		return $args;
	}

	private static function notice(): void {
		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Signup updated.', 'pasat' ) . '</p></div>';
		}
	}
}
