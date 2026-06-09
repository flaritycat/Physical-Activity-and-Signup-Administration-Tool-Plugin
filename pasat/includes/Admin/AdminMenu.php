<?php
namespace PASAT\Admin;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\HostsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {
	public static function register(): void {
		add_menu_page(
			__( 'PASAT', 'pasat' ),
			__( 'PASAT', 'pasat' ),
			'pasat_view_signups',
			'pasat',
			array( self::class, 'dashboard' ),
			'dashicons-calendar-alt',
			26
		);

		add_submenu_page( 'pasat', __( 'Dashboard', 'pasat' ), __( 'Dashboard', 'pasat' ), 'pasat_view_signups', 'pasat', array( self::class, 'dashboard' ) );
		add_submenu_page( 'pasat', __( 'Activities', 'pasat' ), __( 'Activities', 'pasat' ), 'pasat_manage_assigned_activities', 'pasat-activities', array( ActivitiesPage::class, 'render' ) );
		add_submenu_page( 'pasat', __( 'Venues', 'pasat' ), __( 'Venues', 'pasat' ), 'pasat_manage_venues', 'pasat-venues', array( VenuesPage::class, 'render' ) );
		add_submenu_page( 'pasat', __( 'Signups', 'pasat' ), __( 'Signups', 'pasat' ), 'pasat_view_signups', 'pasat-signups', array( SignupsPage::class, 'render' ) );
		add_submenu_page( 'pasat', __( 'Participants', 'pasat' ), __( 'Participants', 'pasat' ), 'pasat_view_participants', 'pasat-participants', array( ParticipantsPage::class, 'render' ) );
		add_submenu_page( 'pasat', __( 'Hosts', 'pasat' ), __( 'Hosts', 'pasat' ), 'pasat_manage_hosts', 'pasat-hosts', array( HostsPage::class, 'render' ) );
		add_submenu_page( 'pasat', __( 'Settings', 'pasat' ), __( 'Settings', 'pasat' ), 'pasat_manage_settings', 'pasat-settings', array( SettingsPage::class, 'render' ) );
		add_submenu_page( 'pasat', __( 'Privacy', 'pasat' ), __( 'Privacy', 'pasat' ), 'pasat_run_privacy_tools', 'pasat-privacy', array( PrivacyPage::class, 'render' ) );
	}

	public static function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'pasat' ) ) {
			return;
		}

		wp_enqueue_style( 'pasat-admin', PASAT_PLUGIN_URL . 'assets/css/admin.css', array(), PASAT_VERSION );
		wp_enqueue_script( 'pasat-admin', PASAT_PLUGIN_URL . 'assets/js/admin.js', array(), PASAT_VERSION, true );
	}

	public static function dashboard(): void {
		if ( ! current_user_can( 'pasat_view_signups' ) ) {
			wp_die( esc_html__( 'You do not have permission to view PASAT.', 'pasat' ) );
		}

		$activities = new ActivitiesRepository();
		$signups    = new SignupsRepository();
		$host_ids   = current_user_can( 'pasat_manage_all_activities' ) ? null : ( new HostsRepository() )->activity_ids_for_user( get_current_user_id() );
		$counts     = $activities->counts();
		$totals     = $signups->totals();
		$upcoming   = $activities->list( array_filter( array( 'upcoming' => true, 'limit' => 8, 'assigned_user_id' => current_user_can( 'pasat_manage_all_activities' ) ? 0 : get_current_user_id() ) ) );
		$recent     = $signups->list( null === $host_ids ? array() : array( 'activity_ids' => $host_ids ) );
		$recent     = array_slice( $recent, 0, 8 );
		$settings   = Helpers::settings();
		$page_id    = absint( $settings['public_page_id'] ?? 0 );
		?>
		<div class="wrap pasat-admin">
			<h1><?php esc_html_e( 'PASAT Dashboard', 'pasat' ); ?></h1>
			<?php if ( ! $page_id ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'No public PASAT page is selected. Add a page with [pasat_activity_list] and choose it in Settings.', 'pasat' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! get_option( 'pasat_mail_last_test_at', '' ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'No successful PASAT test e-mail has been recorded. Send one from PASAT Settings before opening public signups.', 'pasat' ); ?></p></div>
			<?php endif; ?>
			<div class="pasat-admin-metrics">
				<div><strong><?php echo esc_html( array_sum( $counts ) ); ?></strong><span><?php esc_html_e( 'Activities', 'pasat' ); ?></span></div>
				<div><strong><?php echo esc_html( (string) ( $totals['confirmed'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Confirmed', 'pasat' ); ?></span></div>
				<div><strong><?php echo esc_html( (string) ( $totals['waitlisted'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Waitlisted', 'pasat' ); ?></span></div>
			</div>
			<h2><?php esc_html_e( 'Upcoming Activities', 'pasat' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Title', 'pasat' ); ?></th><th><?php esc_html_e( 'Starts', 'pasat' ); ?></th><th><?php esc_html_e( 'Venue', 'pasat' ); ?></th><th><?php esc_html_e( 'Status', 'pasat' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $upcoming as $activity ) : ?>
					<tr>
						<td><?php echo esc_html( $activity['title'] ); ?></td>
						<td><?php echo esc_html( Helpers::local_datetime( $activity['starts_at'] ) ); ?></td>
						<td><?php echo esc_html( $activity['venue_name'] ?? '' ); ?></td>
						<td><?php echo esc_html( $activity['status'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $upcoming ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No upcoming activities yet.', 'pasat' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'Recent Signups', 'pasat' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Participant', 'pasat' ); ?></th><th><?php esc_html_e( 'Activity', 'pasat' ); ?></th><th><?php esc_html_e( 'Status', 'pasat' ); ?></th><th><?php esc_html_e( 'Created', 'pasat' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $recent as $signup ) : ?>
					<tr>
						<td><?php echo esc_html( trim( $signup['first_name'] . ' ' . $signup['last_name'] ) ); ?></td>
						<td><?php echo esc_html( $signup['activity_title'] ); ?></td>
						<td><?php echo esc_html( $signup['status'] ); ?></td>
						<td><?php echo esc_html( Helpers::local_datetime( $signup['created_at'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $recent ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No signups yet.', 'pasat' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
