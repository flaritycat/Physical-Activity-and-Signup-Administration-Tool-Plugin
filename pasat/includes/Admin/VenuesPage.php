<?php
namespace PASAT\Admin;

use PASAT\Database\AuditLogRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Map\Geocoder;
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
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// These GET values select read-only admin views. Mutations are handled by nonce-protected POST actions.
		$action = sanitize_key( wp_unslash( $_GET['action'] ?? '' ) );
		$id     = absint( wp_unslash( $_GET['id'] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

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
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== $request_method ) {
			return;
		}

		check_admin_referer( Nonces::action( 'venue' ), '_pasat_nonce' );
		$repo   = new VenuesRepository();
		$audit  = new AuditLogRepository();
		$action = sanitize_key( wp_unslash( $_POST['pasat_action'] ?? '' ) );
		if ( '' === $action ) {
			return;
		}
		$id     = absint( $_POST['venue_id'] ?? 0 );

		if ( 'save' === $action ) {
			$id = $repo->save( wp_unslash( $_POST ), $id );
			$audit->log( 'venue.save', 'venue', $id, 'Saved venue' );
			wp_safe_redirect( admin_url( 'admin.php?page=pasat-venues&updated=1' ) );
			exit;
		}

		if ( 'geocode' === $action && $id ) {
			$result = ( new Geocoder() )->geocode_venue( $id );
			if ( is_wp_error( $result ) ) {
				$audit->log( 'venue.geocode_failed', 'venue', $id, $result->get_error_message() );
				wp_safe_redirect( add_query_arg( 'geocode_error', rawurlencode( $result->get_error_message() ), admin_url( 'admin.php?page=pasat-venues' ) ) );
				exit;
			}

			$audit->log( 'venue.geocode', 'venue', $id, 'Geocoded venue address' );
			wp_safe_redirect( admin_url( 'admin.php?page=pasat-venues&geocoded=1' ) );
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
				<?php if ( $id ) : ?>
					<tr><th><?php esc_html_e( 'Geocoding Status', 'pasat' ); ?></th><td><?php echo esc_html( self::geocoding_status_label( $venue ) ); ?></td></tr>
				<?php endif; ?>
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
			<thead><tr><th><?php esc_html_e( 'Name', 'pasat' ); ?></th><th><?php esc_html_e( 'Type', 'pasat' ); ?></th><th><?php esc_html_e( 'Address', 'pasat' ); ?></th><th><?php esc_html_e( 'Coordinates', 'pasat' ); ?></th><th><?php esc_html_e( 'Geocoding', 'pasat' ); ?></th><th><?php esc_html_e( 'Capacity', 'pasat' ); ?></th><th><?php esc_html_e( 'Actions', 'pasat' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $venues as $venue ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $venue['name'] ); ?></strong></td>
					<td><?php echo esc_html( $venue['venue_type'] ?? '' ); ?></td>
					<td><?php echo esc_html( $venue['address'] ?? '' ); ?></td>
					<td><?php echo esc_html( self::coordinates_label( $venue ) ); ?></td>
					<td><?php echo esc_html( self::geocoding_status_label( $venue ) ); ?></td>
					<td><?php echo esc_html( (string) ( $venue['capacity'] ?? '' ) ); ?></td>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=pasat-venues&action=edit&id=' . absint( $venue['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'pasat' ); ?></a>
						<?php if ( ! empty( $venue['address'] ) ) : ?>
							<?php self::row_action( (int) $venue['id'], 'geocode', __( 'Geocode Address', 'pasat' ) ); ?>
						<?php endif; ?>
						<?php if ( $repo->has_coordinates( $venue ) ) : ?>
							<a href="<?php echo esc_url( $repo->map_url( $venue ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Map', 'pasat' ); ?></a>
						<?php endif; ?>
						<?php if ( ! $repo->is_used( (int) $venue['id'] ) ) : ?>
							<form method="post" class="pasat-inline-form"><?php Nonces::field( 'venue' ); ?><input type="hidden" name="pasat_action" value="delete"><input type="hidden" name="venue_id" value="<?php echo esc_attr( (string) $venue['id'] ); ?>"><button class="button-link-delete" type="submit"><?php esc_html_e( 'Delete', 'pasat' ); ?></button></form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $venues ) : ?><tr><td colspan="7"><?php esc_html_e( 'No venues yet.', 'pasat' ); ?></td></tr><?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private static function row_action( int $id, string $action, string $label ): void {
		?>
		<form method="post" class="pasat-inline-form">
			<?php Nonces::field( 'venue' ); ?>
			<input type="hidden" name="pasat_action" value="<?php echo esc_attr( $action ); ?>">
			<input type="hidden" name="venue_id" value="<?php echo esc_attr( (string) $id ); ?>">
			<button class="button-link" type="submit"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	private static function coordinates_label( array $venue ): string {
		if ( '' === (string) ( $venue['latitude'] ?? '' ) || '' === (string) ( $venue['longitude'] ?? '' ) ) {
			return __( 'Missing', 'pasat' );
		}

		return (string) $venue['latitude'] . ', ' . (string) $venue['longitude'];
	}

	private static function geocoding_status_label( array $venue ): string {
		$status = sanitize_key( (string) ( $venue['geocoding_status'] ?? 'not_geocoded' ) );
		$labels = array(
			'not_geocoded' => __( 'Not geocoded', 'pasat' ),
			'geocoded'     => __( 'Geocoded', 'pasat' ),
			'failed'       => __( 'Failed', 'pasat' ),
			'manual'       => __( 'Manual coordinates', 'pasat' ),
		);
		$label = $labels[ $status ] ?? $status;

		if ( 'failed' === $status && ! empty( $venue['geocoding_error'] ) ) {
			$label .= ': ' . $venue['geocoding_error'];
		}

		return $label;
	}

	private static function notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only admin notice flag after a nonce-protected POST redirect.
		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Venue saved.', 'pasat' ) . '</p></div>';
		}
		if ( ! empty( $_GET['geocoded'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Venue address geocoded.', 'pasat' ) . '</p></div>';
		}
		if ( ! empty( $_GET['geocode_error'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['geocode_error'] ) ) ) ) . '</p></div>';
		}
	}
}
