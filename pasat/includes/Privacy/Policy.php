<?php
namespace PASAT\Privacy;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Policy {
	public static function register(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		wp_add_privacy_policy_content(
			__( 'Physical Activity Signup and Administration Tool', 'pasat' ),
			wp_kses_post( self::content() )
		);
	}

	private static function content(): string {
		$settings     = Helpers::settings();
		$organization = trim( (string) ( $settings['organization_name'] ?? '' ) );
		$retention    = max( 1, absint( $settings['retention_period_days'] ?? 365 ) );
		$erasure_mode = 'delete' === ( $settings['erasure_mode'] ?? 'anonymize' )
			? __( 'deleted', 'pasat' )
			: __( 'anonymized', 'pasat' );

		$content  = '<h2>' . esc_html__( 'Activity Signups', 'pasat' ) . '</h2>';
		$content .= '<p>' . esc_html(
			sprintf(
				/* translators: %s is the organization name configured in PASAT settings. */
				__( 'When you sign up for an activity, %s collects the information needed to administer that signup.', 'pasat' ),
				'' !== $organization ? $organization : get_bloginfo( 'name' )
			)
		) . '</p>';
		$content .= '<p>' . esc_html__( 'This may include your first name, last name, optional nickname, e-mail address, optional phone number, optional age, consent state, signup status, activity details, cancellation state, and waitlist status.', 'pasat' ) . '</p>';
		$content .= '<p>' . esc_html__( 'PASAT stores hashed request metadata for abuse prevention and audit purposes instead of storing raw IP addresses or raw user-agent strings.', 'pasat' ) . '</p>';
		$content .= '<p>' . esc_html__( 'PASAT uses WordPress mail to send signup confirmations, cancellation confirmations, waitlist promotion notices, activity cancellation notices, and private signup lookup links.', 'pasat' ) . '</p>';
		$content .= '<p>' . esc_html(
			sprintf(
				/* translators: 1: retention period in days, 2: configured erasure mode. */
				__( 'Signup records are retained for about %1$d days after they are no longer needed, then participant data is %2$s according to this site\'s PASAT privacy settings.', 'pasat' ),
				$retention,
				$erasure_mode
			)
		) . '</p>';
		$content .= '<p>' . esc_html__( 'PASAT integrates with the WordPress personal data export and erasure tools, so site administrators can process verified privacy requests for participant signup data.', 'pasat' ) . '</p>';

		return $content;
	}
}
