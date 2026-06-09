<?php
namespace PASAT\Admin;

use PASAT\Database\AuditLogRepository;
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

		if ( isset( $_GET['pasat_export'] ) ) {
			self::export_csv();
		}

		self::handle_post();
		$query        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$repo         = new ParticipantsRepository();
		$participants = $repo->search( $query );
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Participants', 'pasat' ); ?></h1>
			<p><?php esc_html_e( 'Participant records contain personal data. Export, anonymize, or delete only when permitted by your site policy.', 'pasat' ); ?></p>
			<form method="get" class="pasat-filter-row"><input type="hidden" name="page" value="pasat-participants"><input type="search" name="s" value="<?php echo esc_attr( $query ); ?>"><?php submit_button( __( 'Search', 'pasat' ), 'secondary', '', false ); ?> <a class="button" href="<?php echo esc_url( add_query_arg( 'pasat_export', 'participants' ) ); ?>"><?php esc_html_e( 'Export CSV', 'pasat' ); ?></a></form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Name', 'pasat' ); ?></th><th><?php esc_html_e( 'E-mail', 'pasat' ); ?></th><th><?php esc_html_e( 'Phone', 'pasat' ); ?></th><th><?php esc_html_e( 'Age', 'pasat' ); ?></th><th><?php esc_html_e( 'Consent', 'pasat' ); ?></th><th><?php esc_html_e( 'Related Signups', 'pasat' ); ?></th><th><?php esc_html_e( 'Actions', 'pasat' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $participants as $participant ) : ?>
					<tr>
						<td><?php echo esc_html( trim( $participant['first_name'] . ' ' . $participant['last_name'] ) ); ?></td>
						<td><?php echo esc_html( $participant['email'] ); ?></td>
						<td><?php echo esc_html( $participant['phone'] ?? '' ); ?></td>
						<td><?php echo esc_html( (string) ( $participant['age'] ?? '' ) ); ?></td>
						<td><?php echo $participant['consent_given'] ? esc_html__( 'Yes', 'pasat' ) : esc_html__( 'No', 'pasat' ); ?></td>
						<td><?php self::related_signups( (int) $participant['id'], $repo ); ?></td>
						<td><?php self::participant_actions( (int) $participant['id'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $participants ) : ?><tr><td colspan="7"><?php esc_html_e( 'No participants found.', 'pasat' ); ?></td></tr><?php endif; ?>
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
		if ( ! current_user_can( 'pasat_export_participants' ) ) {
			wp_die( esc_html__( 'You do not have permission to modify participant data.', 'pasat' ) );
		}
		Nonces::verify( 'participant' );
		$id     = absint( $_POST['participant_id'] ?? 0 );
		$action = sanitize_key( wp_unslash( $_POST['pasat_action'] ?? '' ) );
		if ( $id && 'anonymize' === $action ) {
			( new ParticipantsRepository() )->anonymize( $id );
			( new AuditLogRepository() )->log( 'participant.anonymize', 'participant', $id, 'Participant anonymized from admin' );
		}
		if ( $id && 'delete' === $action ) {
			Eraser::delete_participant( $id );
			( new AuditLogRepository() )->log( 'participant.delete', 'participant', $id, 'Participant deleted from admin' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=pasat-participants&updated=1' ) );
		exit;
	}

	private static function participant_actions( int $id ): void {
		if ( ! current_user_can( 'pasat_export_participants' ) ) {
			return;
		}
		?>
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
		$query        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$participants = ( new ParticipantsRepository() )->search( $query, 1000 );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pasat-participants.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'id', 'first_name', 'last_name', 'email', 'phone', 'age', 'consent_given', 'created_at' ) );
		foreach ( $participants as $participant ) {
			fputcsv( $out, array( $participant['id'], Helpers::csv_cell( $participant['first_name'] ), Helpers::csv_cell( $participant['last_name'] ), Helpers::csv_cell( $participant['email'] ), Helpers::csv_cell( $participant['phone'] ), $participant['age'], $participant['consent_given'], $participant['created_at'] ) );
		}
		exit;
	}
}
