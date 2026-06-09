<?php
namespace PASAT\Admin;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\AuditLogRepository;
use PASAT\Database\HostsRepository;
use PASAT\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HostsPage {
	public static function render(): void {
		if ( ! current_user_can( 'pasat_manage_hosts' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage hosts.', 'pasat' ) );
		}
		self::handle_post();
		$activities = ( new ActivitiesRepository() )->list( array( 'limit' => 500 ) );
		$users      = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ) ) );
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Hosts', 'pasat' ); ?></h1>
			<form method="post" class="pasat-admin-form">
				<?php Nonces::field( 'host' ); ?>
				<input type="hidden" name="pasat_action" value="assign">
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Activity', 'pasat' ); ?></th><td><select name="activity_id" required><?php foreach ( $activities as $activity ) : ?><option value="<?php echo esc_attr( (string) $activity['id'] ); ?>"><?php echo esc_html( $activity['title'] ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><?php esc_html_e( 'WordPress User', 'pasat' ); ?></th><td><select name="user_id" required><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( (string) $user->ID ); ?>"><?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><?php esc_html_e( 'Role Label', 'pasat' ); ?></th><td><input name="host_role" value="host"></td></tr>
				</table>
				<?php submit_button( __( 'Assign Host', 'pasat' ) ); ?>
			</form>
			<p><?php esc_html_e( 'Hosts with the PASAT Activity Host role can manage only activities assigned here unless they also have broader PASAT capabilities.', 'pasat' ); ?></p>
		</div>
		<?php
	}

	private static function handle_post(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['pasat_action'] ) ) {
			return;
		}
		Nonces::verify( 'host' );
		$activity_id = absint( $_POST['activity_id'] ?? 0 );
		$user_id     = absint( $_POST['user_id'] ?? 0 );
		$role        = sanitize_text_field( wp_unslash( $_POST['host_role'] ?? 'host' ) );
		if ( $activity_id && $user_id ) {
			( new HostsRepository() )->assign( $activity_id, $user_id, $role );
			( new AuditLogRepository() )->log( 'host.assign', 'activity', $activity_id, 'Assigned activity host' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=pasat-hosts&updated=1' ) );
		exit;
	}
}
