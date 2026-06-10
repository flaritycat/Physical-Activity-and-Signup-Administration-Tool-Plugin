<?php
namespace PASAT\Admin;

use PASAT\Database\AuditLogRepository;
use PASAT\Database\BadgesRepository;
use PASAT\Database\ParticipationLogsRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Helpers;
use PASAT\Privacy\Eraser;
use PASAT\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ParticipantsPage {
	public static function render(): void {
		if ( ! current_user_can( 'pasat_view_participants' ) ) {
			wp_die( esc_html__( 'You do not have permission to view participants.', 'pasat' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Export nonce is verified before CSV output in export_csv().
		if ( isset( $_GET['pasat_export'] ) ) {
			self::export_csv();
		}

		self::handle_post();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This GET value is a read-only list filter; CSV export verifies a dedicated nonce.
		$query        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This GET value is a read-only list filter.
		$membership  = sanitize_key( wp_unslash( $_GET['membership_status'] ?? '' ) );
		$repo         = new ParticipantsRepository();
		$participants = $repo->search( $query, 100, array( 'membership_status' => $membership ) );
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Participants', 'pasat' ); ?></h1>
			<p><?php esc_html_e( 'Participant records contain personal data. Export, anonymize, or delete only when permitted by your site policy.', 'pasat' ); ?></p>
			<form method="get" class="pasat-filter-row">
				<input type="hidden" name="page" value="pasat-participants">
				<input type="search" name="s" value="<?php echo esc_attr( $query ); ?>">
				<select name="membership_status">
					<option value=""><?php esc_html_e( 'Any membership status', 'pasat' ); ?></option>
					<?php foreach ( ParticipantsRepository::MEMBERSHIP_STATUSES as $status ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $membership, $status ); ?>><?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Search', 'pasat' ), 'secondary', '', false ); ?>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'pasat_export' => 'participants', 'membership_status' => $membership ) ), Nonces::action( 'participants_export' ), '_pasat_nonce' ) ); ?>"><?php esc_html_e( 'Export CSV', 'pasat' ); ?></a>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Name', 'pasat' ); ?></th><th><?php esc_html_e( 'E-mail', 'pasat' ); ?></th><th><?php esc_html_e( 'Phone', 'pasat' ); ?></th><th><?php esc_html_e( 'Age', 'pasat' ); ?></th><th><?php esc_html_e( 'Consent', 'pasat' ); ?></th><th><?php esc_html_e( 'Membership', 'pasat' ); ?></th><th><?php esc_html_e( 'Badges', 'pasat' ); ?></th><th><?php esc_html_e( 'Participation', 'pasat' ); ?></th><th><?php esc_html_e( 'Related Signups', 'pasat' ); ?></th><th><?php esc_html_e( 'Actions', 'pasat' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $participants as $participant ) : ?>
					<tr>
						<td><?php echo esc_html( trim( $participant['first_name'] . ' ' . $participant['last_name'] ) ); ?></td>
						<td><?php echo esc_html( $participant['email'] ); ?></td>
						<td><?php echo esc_html( $participant['phone'] ?? '' ); ?></td>
						<td><?php echo esc_html( (string) ( $participant['age'] ?? '' ) ); ?></td>
						<td><?php echo $participant['consent_given'] ? esc_html__( 'Yes', 'pasat' ) : esc_html__( 'No', 'pasat' ); ?></td>
						<td><?php self::membership_details( $participant ); ?></td>
						<td><?php self::badge_summary( (int) $participant['id'] ); ?></td>
						<td><?php self::participation_summary( (int) $participant['id'] ); ?></td>
						<td><?php self::related_signups( (int) $participant['id'], $repo ); ?></td>
						<td><?php self::participant_actions( (int) $participant['id'], $participant ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $participants ) : ?><tr><td colspan="10"><?php esc_html_e( 'No participants found.', 'pasat' ); ?></td></tr><?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private static function handle_post(): void {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== $request_method ) {
			return;
		}
		check_admin_referer( Nonces::action( 'participant' ), '_pasat_nonce' );
		$id     = absint( $_POST['participant_id'] ?? 0 );
		$action = sanitize_key( wp_unslash( $_POST['pasat_action'] ?? '' ) );
		if ( in_array( $action, array( 'anonymize', 'delete' ), true ) && ! current_user_can( 'pasat_export_participants' ) ) {
			wp_die( esc_html__( 'You do not have permission to modify participant data.', 'pasat' ) );
		}
		if ( $id && 'anonymize' === $action ) {
			( new ParticipantsRepository() )->anonymize( $id );
			( new AuditLogRepository() )->log( 'participant.anonymize', 'participant', $id, 'Participant anonymized from admin' );
		}
		if ( $id && 'delete' === $action ) {
			Eraser::delete_participant( $id );
			( new AuditLogRepository() )->log( 'participant.delete', 'participant', $id, 'Participant deleted from admin' );
		}
		if ( $id && 'update_membership' === $action ) {
			if ( ! current_user_can( 'pasat_manage_memberships' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage memberships.', 'pasat' ) );
			}
			( new ParticipantsRepository() )->update_membership( $id, wp_unslash( $_POST ) );
			( new AuditLogRepository() )->log( 'participant.membership_update', 'participant', $id, 'Updated participant membership status' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=pasat-participants&updated=1' ) );
		exit;
	}

	private static function participant_actions( int $id, array $participant ): void {
		if ( ! current_user_can( 'pasat_export_participants' ) ) {
			return;
		}
		?>
		<?php if ( current_user_can( 'pasat_manage_memberships' ) ) : ?>
			<details>
				<summary><?php esc_html_e( 'Membership', 'pasat' ); ?></summary>
				<form method="post" class="pasat-admin-form">
					<?php Nonces::field( 'participant' ); ?>
					<input type="hidden" name="participant_id" value="<?php echo esc_attr( (string) $id ); ?>">
					<input type="hidden" name="pasat_action" value="update_membership">
					<p>
						<select name="membership_status">
							<?php foreach ( ParticipantsRepository::MEMBERSHIP_STATUSES as $status ) : ?>
								<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $participant['membership_status'] ?? 'none', $status ); ?>><?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p><input name="membership_number" value="<?php echo esc_attr( $participant['membership_number'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Member number', 'pasat' ); ?>"></p>
					<p><textarea name="membership_notes" rows="2" placeholder="<?php esc_attr_e( 'Private membership notes', 'pasat' ); ?>"><?php echo esc_textarea( $participant['membership_notes'] ?? '' ); ?></textarea></p>
					<?php submit_button( __( 'Save Membership', 'pasat' ), 'secondary small', '', false ); ?>
				</form>
			</details>
		<?php endif; ?>
		<form method="post" class="pasat-inline-form">
			<?php Nonces::field( 'participant' ); ?>
			<input type="hidden" name="participant_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<input type="hidden" name="pasat_action" value="anonymize">
			<button class="button-link" type="submit"><?php esc_html_e( 'Anonymize', 'pasat' ); ?></button>
		</form>
		<form method="post" class="pasat-inline-form">
			<?php Nonces::field( 'participant' ); ?>
			<input type="hidden" name="participant_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<input type="hidden" name="pasat_action" value="delete">
			<button class="button-link-delete" type="submit" onclick="return window.confirm('<?php echo esc_js( __( 'Delete this participant and related signups?', 'pasat' ) ); ?>');"><?php esc_html_e( 'Delete', 'pasat' ); ?></button>
		</form>
		<?php
	}

	private static function membership_details( array $participant ): void {
		echo esc_html( ucfirst( str_replace( '_', ' ', (string) ( $participant['membership_status'] ?? 'none' ) ) ) );
		if ( ! empty( $participant['membership_number'] ) ) {
			echo '<br><span class="description">' . esc_html( $participant['membership_number'] ) . '</span>';
		}
		if ( ! empty( $participant['membership_opted_in'] ) ) {
			echo '<br><span class="description">' . esc_html__( 'Opted in', 'pasat' ) . '</span>';
		}
	}

	private static function badge_summary( int $participant_id ): void {
		$badges = ( new BadgesRepository() )->active_for_participant( $participant_id );
		if ( ! $badges ) {
			esc_html_e( 'No badges', 'pasat' );
			return;
		}
		echo '<ul class="pasat-compact-list">';
		foreach ( $badges as $badge ) {
			echo '<li>' . esc_html( $badge['label'] ) . '</li>';
		}
		echo '</ul>';
	}

	private static function participation_summary( int $participant_id ): void {
		$logs = ( new ParticipationLogsRepository() )->list_for_participant( $participant_id );
		if ( ! $logs ) {
			esc_html_e( 'No participation', 'pasat' );
			return;
		}
		?>
		<details>
			<summary>
				<?php
				printf(
					/* translators: %d is the number of participation logs. */
					esc_html( _n( '%d log', '%d logs', count( $logs ), 'pasat' ) ),
					count( $logs )
				);
				?>
			</summary>
			<ul class="pasat-compact-list">
				<?php foreach ( $logs as $log ) : ?>
					<li><?php echo esc_html( trim( ( $log['activity_title'] ?? '' ) . ' - ' . ( $log['attendance_status'] ?? '' ) . ( ! empty( $log['placement'] ) ? ' - #' . (int) $log['placement'] : '' ) ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</details>
		<?php
	}

	private static function related_signups( int $participant_id, ParticipantsRepository $repo ): void {
		$signups = $repo->signups_for_participant( $participant_id );
		if ( ! $signups ) {
			esc_html_e( 'No signups', 'pasat' );
			return;
		}
		?>
		<details>
			<summary>
				<?php
				printf(
					/* translators: %d is the number of related signups. */
					esc_html( _n( '%d signup', '%d signups', count( $signups ), 'pasat' ) ),
					count( $signups )
				);
				?>
			</summary>
			<ul class="pasat-compact-list">
				<?php foreach ( $signups as $signup ) : ?>
					<li>
						<?php
						echo esc_html(
							sprintf(
								'%s - %s - %s',
								$signup['activity_title'] ?? '',
								$signup['status'] ?? '',
								Helpers::local_datetime( $signup['starts_at'] ?? '' )
							)
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>
		<?php
	}

	private static function export_csv(): void {
		if ( ! current_user_can( 'pasat_export_participants' ) ) {
			wp_die( esc_html__( 'You do not have permission to export participant data.', 'pasat' ) );
		}
		check_admin_referer( Nonces::action( 'participants_export' ), '_pasat_nonce' );
		$query        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$membership   = sanitize_key( wp_unslash( $_GET['membership_status'] ?? '' ) );
		$repo         = new ParticipantsRepository();
		$badges       = new BadgesRepository();
		$participants = $repo->search( $query, 1000, array( 'membership_status' => $membership ) );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pasat-participants.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'id', 'first_name', 'last_name', 'email', 'phone', 'age', 'consent_given', 'membership_status', 'membership_opted_in', 'membership_number', 'badges', 'created_at' ) );
		foreach ( $participants as $participant ) {
			fputcsv( $out, array( $participant['id'], Helpers::csv_cell( $participant['first_name'] ), Helpers::csv_cell( $participant['last_name'] ), Helpers::csv_cell( $participant['email'] ), Helpers::csv_cell( $participant['phone'] ), $participant['age'], $participant['consent_given'], $participant['membership_status'], $participant['membership_opted_in'], Helpers::csv_cell( $participant['membership_number'] ), Helpers::csv_cell( $badges->summary_labels( (int) $participant['id'] ) ), $participant['created_at'] ) );
		}
		exit;
	}
}
