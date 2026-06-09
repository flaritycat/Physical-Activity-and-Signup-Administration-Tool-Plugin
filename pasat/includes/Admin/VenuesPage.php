<?php
namespace PASAT\Admin;

use PASAT\Database\AuditLogRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Security\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VenuesPage {
	public static function render(): void {
		if ( ! current_user_can( 'pasat_manage_venues' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage venues.', 'pasat' ) );
		}

		self::handle_post();
		$repo   = new VenuesRepository();
		$action = sanitize_key( $_GET['action'] ?? '' );
		$id     = absint( $_GET['id'] ?? 0 );

		echo '<div class="wrap pasat-admin"><h1>' . esc_html__( 'PASAT Venues', 'pasat' ) . ' <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=pasat-venues&action=new' ) ) . '">' . esc_html__( 'Add New', 'pasat' ) . '</a></h1>';
		self::notice();
		if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
			self::form( $id ? ( $repo->get( $id ) ?: array() ) : array() );
		} else {
			self::table();
		}
		echo '</div>';
	}

	private static function handle_post(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['pasat_action'] ) ) {
			return;
		}

		Nonces::verify( 'venue' );
		$repo   = new VenuesRepository();
		$audit  = new AuditLogRepository();
		$action = sanitize_key( $_POST['pasat_action'] );
		$id     = absint( $_POST['venue_id'] ?? 0 );

		if ( 'save' === $action ) {
			$id = $repo->save( wp_unslash( $_POST ), $id );
			$audit->log( 'venue.save', 'venue', $id, 'Saved venue' );
			wp_safe_redirect( admin_url( 'admin.php?page=pasat-venues&updated=1' ) );
			exit;
		}

		if ( 'delete' === $action && $id && ! $repo->is_used( $id ) ) {
			$repo->delete( $id );
			$audit->log( 'venue.delete', 'venue', $id, 'Deleted venue' );
			wp_safe_redirect( admin_url( 'admin.php?page=pasat-venues&deleted=1' ) );
			exit;
		}
	}

	private static function form( array $venue ): void {
		$id = absint( $venue['id'] ?? 0 );
		?>
		<form method="post" class="pasat-admin-form">
			<?php Nonces::field( 'venue' ); ?>
			<input type="hidden" name="pasat_action" value="save">
			<input type="hidden" name="venue_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<table class="form-table" role="presentation">
				<tr><th><label for="pasat-venue-name"><?php esc_html_e( 'Name', 'pasat' ); ?></label></th><td><input class="regular-text" id="pasat-venue-name" name="name" required value="<?php echo esc_attr( $venue['name'] ?? '' ); ?>"></td></tr>
				<tr><th><label for="pasat-venue-description"><?php esc_html_e( 'Description', 'pasat' ); ?></label></th><td><textarea class="large-text" id="pasat-venue-description" name="description" rows="4"><?php echo esc_textarea( $venue['description'] ?? '' ); ?></textarea></td></tr>
				<tr><th><label for="pasat-venue-address"><?php esc_html_e( 'Address', 'pasat' ); ?></label></th><td><textarea class="large-text" id="pasat-venue-address" name="address" rows="3"><?php echo esc_textarea( $venue['address'] ?? '' ); ?></textarea></td></tr>
				<tr><th><label for="pasat-venue-type"><?php esc_html_e( 'Type', 'pasat' ); ?></label></th><td><input id="pasat-venue-type" name="venue_type" value="<?php echo esc_attr( $venue['venue_type'] ?? '' ); ?>"></td></tr>
				<tr><th><label for="pasat-venue-capacity"><?php esc_html_e( 'Capacity', 'pasat' ); ?></label></th><td><input type="number" min="0" id="pasat-venue-capacity" name="capacity" value="<?php echo esc_attr( (string) ( $venue['capacity'] ?? '' ) ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Coordinates', 'pasat' ); ?></th><td><input name="latitude" placeholder="<?php esc_attr_e( 'Latitude', 'pasat' ); ?>" value="<?php echo esc_attr( (string) ( $venue['latitude'] ?? '' ) ); ?>"> <input name="longitude" placeholder="<?php esc_attr_e( 'Longitude', 'pasat' ); ?>" value="<?php echo esc_attr( (string) ( $venue['longitude'] ?? '' ) ); ?>"></td></tr>
			</table>
			<?php submit_button( $id ? __( 'Save Venue', 'pasat' ) : __( 'Create Venue', 'pasat' ) ); ?>
		</form>
		<?php
	}

	private static function table(): void {
		$repo   = new VenuesRepository();
		$venues = $repo->list();
		?>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Name', 'pasat' ); ?></th><th><?php esc_html_e( 'Type', 'pasat' ); ?></th><th><?php esc_html_e( 'Address', 'pasat' ); ?></th><th><?php esc_html_e( 'Capacity', 'pasat' ); ?></th><th><?php esc_html_e( 'Actions', 'pasat' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $venues as $venue ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $venue['name'] ); ?></strong></td>
					<td><?php echo esc_html( $venue['venue_type'] ?? '' ); ?></td>
					<td><?php echo esc_html( $venue['address'] ?? '' ); ?></td>
					<td><?php echo esc_html( (string) ( $venue['capacity'] ?? '' ) ); ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=pasat-venues&action=edit&id=' . absint( $venue['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'pasat' ); ?></a>
						<?php if ( ! $repo->is_used( (int) $venue['id'] ) ) : ?>
							<form method="post" class="pasat-inline-form"><?php Nonces::field( 'venue' ); ?><input type="hidden" name="pasat_action" value="delete"><input type="hidden" name="venue_id" value="<?php echo esc_attr( (string) $venue['id'] ); ?>"><button class="button-link-delete" type="submit"><?php esc_html_e( 'Delete', 'pasat' ); ?></button></form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $venues ) : ?><tr><td colspan="5"><?php esc_html_e( 'No venues yet.', 'pasat' ); ?></td></tr><?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private static function notice(): void {
		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Venue saved.', 'pasat' ) . '</p></div>';
		}
	}
}
