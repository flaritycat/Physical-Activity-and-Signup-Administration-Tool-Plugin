<?php
namespace PASAT\Admin;

use PASAT\Capabilities;
use PASAT\Database\ActivitiesRepository;
use PASAT\Database\AuditLogRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Email\Mailer;
use PASAT\Helpers;
use PASAT\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivitiesPage {
	public static function render(): void {
		if ( ! current_user_can( 'pasat_manage_assigned_activities' ) && ! current_user_can( 'pasat_manage_all_activities' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage activities.', 'pasat' ) );
		}

		self::handle_post();

		$repo   = new ActivitiesRepository();
		$action = sanitize_key( $_GET['action'] ?? '' );
		$id     = absint( $_GET['id'] ?? 0 );

		echo '<div class="wrap pasat-admin">';
		echo '<h1>' . esc_html__( 'PASAT Activities', 'pasat' ) . ' <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=pasat-activities&action=new' ) ) . '">' . esc_html__( 'Add New', 'pasat' ) . '</a></h1>';
		self::notice();

		if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
			$activity = $id ? $repo->get( $id ) : null;
			if ( $id && ! Capabilities::can_manage_activity( $id ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this activity.', 'pasat' ) );
			}
			self::form( $activity ?: array() );
		} else {
			self::table();
		}

		echo '</div>';
	}

	private static function handle_post(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['pasat_action'] ) ) {
			return;
		}

		Nonces::verify( 'activity' );
		$repo   = new ActivitiesRepository();
		$audit  = new AuditLogRepository();
		$action = sanitize_key( $_POST['pasat_action'] );
		$id     = absint( $_POST['activity_id'] ?? 0 );

		if ( $id && ! Capabilities::can_manage_activity( $id ) ) {
			wp_die( esc_html__( 'You do not have permission to change this activity.', 'pasat' ) );
		}

		if ( 'save' === $action ) {
			$before = $id ? $repo->get( $id ) : null;
			$id = $repo->save( wp_unslash( $_POST ), $id );
			self::maybe_notify_cancellation( $id, $before, sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ) );
			$audit->log( 'activity.save', 'activity', $id, 'Saved activity' );
			wp_safe_redirect( admin_url( 'admin.php?page=pasat-activities&updated=1' ) );
			exit;
		}

		if ( in_array( $action, array( 'publish', 'cancel', 'archive', 'draft' ), true ) && $id ) {
			$before             = $repo->get( $id ) ?: array();
			$activity           = $before;
			$activity['status'] = 'publish' === $action ? 'published' : $action;
			$repo->save( $activity, $id );
			self::maybe_notify_cancellation( $id, $before, $activity['status'] );
			$audit->log( 'activity.' . $action, 'activity', $id, 'Changed activity status' );
			wp_safe_redirect( admin_url( 'admin.php?page=pasat-activities&updated=1' ) );
			exit;
		}

		if ( 'duplicate' === $action && $id ) {
			$new_id = $repo->duplicate( $id );
			$audit->log( 'activity.duplicate', 'activity', $new_id, 'Duplicated activity' );
			wp_safe_redirect( admin_url( 'admin.php?page=pasat-activities&updated=1' ) );
			exit;
		}
	}

	private static function form( array $activity ): void {
		$venues = ( new VenuesRepository() )->list();
		$id     = absint( $activity['id'] ?? 0 );
		?>
		<form method="post" class="pasat-admin-form">
			<?php Nonces::field( 'activity' ); ?>
			<input type="hidden" name="pasat_action" value="save">
			<input type="hidden" name="activity_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<table class="form-table" role="presentation">
				<tr><th><label for="pasat-title"><?php esc_html_e( 'Title', 'pasat' ); ?></label></th><td><input class="regular-text" id="pasat-title" name="title" required value="<?php echo esc_attr( $activity['title'] ?? '' ); ?>"></td></tr>
				<tr><th><label for="pasat-description"><?php esc_html_e( 'Description', 'pasat' ); ?></label></th><td><textarea class="large-text" id="pasat-description" name="description" rows="5"><?php echo esc_textarea( $activity['description'] ?? '' ); ?></textarea></td></tr>
				<tr><th><label for="pasat-type"><?php esc_html_e( 'Activity Type', 'pasat' ); ?></label></th><td><input id="pasat-type" name="activity_type" value="<?php echo esc_attr( $activity['activity_type'] ?? '' ); ?>"></td></tr>
				<tr><th><label for="pasat-season"><?php esc_html_e( 'Season Year', 'pasat' ); ?></label></th><td><input type="number" id="pasat-season" name="season_year" value="<?php echo esc_attr( (string) ( $activity['season_year'] ?? Helpers::setting( 'default_season_year' ) ) ); ?>"></td></tr>
				<tr><th><label for="pasat-starts"><?php esc_html_e( 'Starts At', 'pasat' ); ?></label></th><td><input type="datetime-local" id="pasat-starts" name="starts_at" value="<?php echo esc_attr( self::datetime_input( $activity['starts_at'] ?? '' ) ); ?>"></td></tr>
				<tr><th><label for="pasat-ends"><?php esc_html_e( 'Ends At', 'pasat' ); ?></label></th><td><input type="datetime-local" id="pasat-ends" name="ends_at" value="<?php echo esc_attr( self::datetime_input( $activity['ends_at'] ?? '' ) ); ?>"></td></tr>
				<tr><th><label for="pasat-venue"><?php esc_html_e( 'Venue', 'pasat' ); ?></label></th><td><select id="pasat-venue" name="venue_id"><option value="0"><?php esc_html_e( 'No venue', 'pasat' ); ?></option><?php foreach ( $venues as $venue ) : ?><option value="<?php echo esc_attr( (string) $venue['id'] ); ?>" <?php selected( (int) ( $activity['venue_id'] ?? 0 ), (int) $venue['id'] ); ?>><?php echo esc_html( $venue['name'] ); ?></option><?php endforeach; ?></select></td></tr>
				<tr><th><label for="pasat-capacity"><?php esc_html_e( 'Capacity', 'pasat' ); ?></label></th><td><input type="number" min="0" id="pasat-capacity" name="capacity" value="<?php echo esc_attr( (string) ( $activity['capacity'] ?? Helpers::setting( 'default_capacity' ) ) ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Waitlist', 'pasat' ); ?></th><td><label><input type="checkbox" name="waitlist_enabled" value="1" <?php checked( (int) ( $activity['waitlist_enabled'] ?? Helpers::setting( 'default_waitlist_enabled' ) ), 1 ); ?>> <?php esc_html_e( 'Enable waitlist when full', 'pasat' ); ?></label></td></tr>
				<tr><th><label for="pasat-open"><?php esc_html_e( 'Signup Opens', 'pasat' ); ?></label></th><td><input type="datetime-local" id="pasat-open" name="signup_opens_at" value="<?php echo esc_attr( self::datetime_input( $activity['signup_opens_at'] ?? '' ) ); ?>"></td></tr>
				<tr><th><label for="pasat-close"><?php esc_html_e( 'Signup Closes', 'pasat' ); ?></label></th><td><input type="datetime-local" id="pasat-close" name="signup_closes_at" value="<?php echo esc_attr( self::datetime_input( $activity['signup_closes_at'] ?? '' ) ); ?>"></td></tr>
				<tr><th><label for="pasat-status"><?php esc_html_e( 'Status', 'pasat' ); ?></label></th><td><select id="pasat-status" name="status"><?php foreach ( ActivitiesRepository::STATUSES as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $activity['status'] ?? 'draft', $status ); ?>><?php echo esc_html( ucfirst( $status ) ); ?></option><?php endforeach; ?></select></td></tr>
				<tr><th><label for="pasat-visibility"><?php esc_html_e( 'Visibility', 'pasat' ); ?></label></th><td><select id="pasat-visibility" name="public_visibility"><option value="public" <?php selected( $activity['public_visibility'] ?? 'public', 'public' ); ?>><?php esc_html_e( 'Public', 'pasat' ); ?></option><option value="unlisted" <?php selected( $activity['public_visibility'] ?? 'public', 'unlisted' ); ?>><?php esc_html_e( 'Unlisted', 'pasat' ); ?></option><option value="private" <?php selected( $activity['public_visibility'] ?? 'public', 'private' ); ?>><?php esc_html_e( 'Private', 'pasat' ); ?></option></select></td></tr>
				<tr><th><?php esc_html_e( 'Age Limits', 'pasat' ); ?></th><td><input type="number" min="0" name="minimum_age" placeholder="<?php esc_attr_e( 'Minimum', 'pasat' ); ?>" value="<?php echo esc_attr( (string) ( $activity['minimum_age'] ?? '' ) ); ?>"> <input type="number" min="0" name="maximum_age" placeholder="<?php esc_attr_e( 'Maximum', 'pasat' ); ?>" value="<?php echo esc_attr( (string) ( $activity['maximum_age'] ?? '' ) ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Warning Acknowledgement', 'pasat' ); ?></th><td><label><input type="checkbox" name="requires_warning_ack" value="1" <?php checked( (int) ( $activity['requires_warning_ack'] ?? 0 ), 1 ); ?>> <?php esc_html_e( 'Require acknowledgement', 'pasat' ); ?></label><textarea class="large-text" name="warning_text" rows="3"><?php echo esc_textarea( $activity['warning_text'] ?? Helpers::setting( 'default_warning_text' ) ); ?></textarea></td></tr>
			</table>
			<?php submit_button( $id ? __( 'Save Activity', 'pasat' ) : __( 'Create Activity', 'pasat' ) ); ?>
		</form>
		<?php
	}

	private static function maybe_notify_cancellation( int $activity_id, ?array $before, string $new_status ): void {
		if ( 'cancelled' !== $new_status || ( $before && 'cancelled' === ( $before['status'] ?? '' ) ) ) {
			return;
		}

		$signups = new SignupsRepository();
		$count   = 0;
		foreach ( $signups->active_for_activity( $activity_id ) as $signup ) {
			if ( ! empty( $signup['email'] ) && Mailer::send_activity_cancellation( $signup ) ) {
				++$count;
			}
		}

		( new AuditLogRepository() )->log(
			'activity.cancellation_notice',
			'activity',
			$activity_id,
			sprintf( 'Sent %d activity cancellation notices', $count )
		);
	}

	private static function table(): void {
		$repo       = new ActivitiesRepository();
		$args       = array( 'limit' => 500 );
		if ( ! current_user_can( 'pasat_manage_all_activities' ) ) {
			$args['assigned_user_id'] = get_current_user_id();
		}
		$activities = $repo->list( $args );
		?>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Title', 'pasat' ); ?></th><th><?php esc_html_e( 'Starts', 'pasat' ); ?></th><th><?php esc_html_e( 'Venue', 'pasat' ); ?></th><th><?php esc_html_e( 'Capacity', 'pasat' ); ?></th><th><?php esc_html_e( 'Status', 'pasat' ); ?></th><th><?php esc_html_e( 'Actions', 'pasat' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $activities as $activity ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $activity['title'] ); ?></strong></td>
					<td><?php echo esc_html( Helpers::local_datetime( $activity['starts_at'] ) ); ?></td>
					<td><?php echo esc_html( $activity['venue_name'] ?? '' ); ?></td>
					<td><?php echo esc_html( (string) ( $activity['capacity'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( $activity['status'] ); ?></td>
					<td class="pasat-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=pasat-activities&action=edit&id=' . absint( $activity['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'pasat' ); ?></a>
						<?php self::row_action( (int) $activity['id'], 'publish', __( 'Publish', 'pasat' ) ); ?>
						<?php self::row_action( (int) $activity['id'], 'cancel', __( 'Cancel', 'pasat' ) ); ?>
						<?php self::row_action( (int) $activity['id'], 'archive', __( 'Archive', 'pasat' ) ); ?>
						<?php self::row_action( (int) $activity['id'], 'duplicate', __( 'Duplicate', 'pasat' ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $activities ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No activities yet.', 'pasat' ); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private static function row_action( int $id, string $action, string $label ): void {
		?>
		<form method="post" class="pasat-inline-form">
			<?php Nonces::field( 'activity' ); ?>
			<input type="hidden" name="activity_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<input type="hidden" name="pasat_action" value="<?php echo esc_attr( $action ); ?>">
			<button class="button-link" type="submit"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	private static function datetime_input( ?string $mysql_datetime ): string {
		if ( empty( $mysql_datetime ) ) {
			return '';
		}

		$timestamp = strtotime( $mysql_datetime . ' UTC' );
		return $timestamp ? wp_date( 'Y-m-d\TH:i', $timestamp ) : '';
	}

	private static function notice(): void {
		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Activity saved.', 'pasat' ) . '</p></div>';
		}
	}
}
