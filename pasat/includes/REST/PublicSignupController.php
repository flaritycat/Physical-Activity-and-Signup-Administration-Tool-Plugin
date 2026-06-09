<?php
namespace PASAT\REST;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\AuditLogRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Email\Mailer;
use PASAT\Helpers;
use PASAT\Security\RateLimiter;
use PASAT\Security\Tokens;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PublicSignupController {
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = self::process_signup( $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 201 );
	}

	public function cancel( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$token  = sanitize_text_field( (string) $request->get_param( 'token' ) );
		$result = self::process_cancellation( $token );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result );
	}

	public static function process_signup( array $input ): array|WP_Error {
		$limited = RateLimiter::check( 'public_signup', 8, 300 );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$activity_id = absint( $input['activity_id'] ?? 0 );
		$first_name  = sanitize_text_field( $input['first_name'] ?? '' );
		$last_name   = sanitize_text_field( $input['last_name'] ?? '' );
		$email       = strtolower( sanitize_email( $input['email'] ?? '' ) );
		$age         = isset( $input['age'] ) && '' !== $input['age'] ? absint( $input['age'] ) : null;
		$settings    = Helpers::settings();

		if ( ! $activity_id || '' === $first_name || '' === $last_name || ! is_email( $email ) ) {
			return new WP_Error( 'pasat_invalid_signup', __( 'Please provide a valid activity, name, and e-mail address.', 'pasat' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $settings['require_consent'] ) && empty( $input['consent_given'] ) ) {
			return new WP_Error( 'pasat_consent_required', __( 'Consent is required before signup.', 'pasat' ), array( 'status' => 400 ) );
		}

		$activities = new ActivitiesRepository();
		$activity   = $activities->get_with_venue( $activity_id );
		if ( ! $activity ) {
			return new WP_Error( 'pasat_activity_not_found', __( 'Activity not found.', 'pasat' ), array( 'status' => 404 ) );
		}

		if ( ! $activities->is_public_signup_open( $activity ) ) {
			return new WP_Error( 'pasat_signup_closed', __( 'Signup is not open for this activity.', 'pasat' ), array( 'status' => 409 ) );
		}

		if ( null !== $age ) {
			if ( isset( $activity['minimum_age'] ) && '' !== (string) $activity['minimum_age'] && $age < (int) $activity['minimum_age'] ) {
				return new WP_Error( 'pasat_age_restricted', __( 'The participant does not meet the minimum age for this activity.', 'pasat' ), array( 'status' => 400 ) );
			}
			if ( isset( $activity['maximum_age'] ) && '' !== (string) $activity['maximum_age'] && $age > (int) $activity['maximum_age'] ) {
				return new WP_Error( 'pasat_age_restricted', __( 'The participant exceeds the maximum age for this activity.', 'pasat' ), array( 'status' => 400 ) );
			}
		}

		if ( ! empty( $activity['requires_warning_ack'] ) && empty( $input['warning_acknowledged'] ) ) {
			return new WP_Error( 'pasat_warning_required', __( 'Please acknowledge the activity warning before signing up.', 'pasat' ), array( 'status' => 400 ) );
		}

		$signups = new SignupsRepository();
		if ( ! $signups->acquire_activity_lock( $activity_id ) ) {
			return new WP_Error( 'pasat_signup_busy', __( 'Signup is busy for this activity. Please try again in a moment.', 'pasat' ), array( 'status' => 503 ) );
		}

		try {
			if ( empty( $settings['allow_duplicate_email_per_activity'] ) && $signups->duplicate_active_by_email( $activity_id, $email ) ) {
				return new WP_Error( 'pasat_duplicate_signup', __( 'This e-mail address already has an active signup for this activity.', 'pasat' ), array( 'status' => 409 ) );
			}

			$capacity = $signups->capacity_snapshot( $activity );
			if ( ! $capacity['is_full'] ) {
				$status = 'confirmed';
			} elseif ( ! empty( $activity['waitlist_enabled'] ) ) {
				$status = 'waitlisted';
			} else {
				return new WP_Error( 'pasat_activity_full', __( 'This activity is full and the waitlist is not enabled.', 'pasat' ), array( 'status' => 409 ) );
			}

			$participants   = new ParticipantsRepository();
			$participant_id = $participants->create_or_update_from_signup(
				array(
					'first_name'      => $first_name,
					'last_name'       => $last_name,
					'nickname'        => sanitize_text_field( $input['nickname'] ?? '' ),
					'email'           => $email,
					'phone'           => sanitize_text_field( $input['phone'] ?? '' ),
					'age'             => $age,
					'consent_given'   => ! empty( $input['consent_given'] ) ? 1 : 0,
					'consent_version' => PASAT_VERSION,
				)
			);

			$signup_id = $signups->create(
				$activity_id,
				$participant_id,
				$status,
				array(
					'source'               => 'public',
					'warning_acknowledged' => ! empty( $input['warning_acknowledged'] ),
				)
			);
			$token = Tokens::generate_for_signup( $signup_id );
			$signups->update_token_hash( $signup_id, $token['hash'] );
			$signup = $signups->get_with_details( $signup_id );
		} finally {
			$signups->release_activity_lock( $activity_id );
		}

		$mail_sent = $signup ? Mailer::send_signup_confirmation( $signup, $token['token'] ) : false;
		if ( ! $mail_sent && ! empty( $settings['pasat_strict_email_delivery'] ) ) {
			$signups->delete( $signup_id );
			return new WP_Error( 'pasat_mail_failed', __( 'Signup could not be completed because the confirmation e-mail failed.', 'pasat' ), array( 'status' => 503 ) );
		}

		( new AuditLogRepository() )->log( 'public.signup_create', 'signup', $signup_id, 'Public signup created' );

		return array(
			'signup_id'               => $signup_id,
			'activity_id'             => $activity_id,
			'status'                  => $status,
			'confirmation_email_sent' => $mail_sent,
		);
	}

	public static function process_cancellation( string $token ): array|WP_Error {
		$limited = RateLimiter::check( 'public_cancel', 10, 300 );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$signup_id = Tokens::extract_signup_id( $token );
		if ( ! $signup_id ) {
			return new WP_Error( 'pasat_invalid_token', __( 'Invalid cancellation link.', 'pasat' ), array( 'status' => 400 ) );
		}

		$signups = new SignupsRepository();
		$signup  = $signups->get_with_details( $signup_id );
		if ( ! $signup || empty( $signup['cancellation_token_hash'] ) || ! Tokens::verify( $token, (string) $signup['cancellation_token_hash'] ) ) {
			return new WP_Error( 'pasat_invalid_token', __( 'Invalid or expired cancellation link.', 'pasat' ), array( 'status' => 400 ) );
		}

		$result = $signups->cancel( $signup_id, __( 'Cancelled by participant link.', 'pasat' ) );
		Mailer::send_cancellation_confirmation( $signup );

		if ( ! empty( $result['promoted_signup_id'] ) ) {
			$promoted = $signups->get_with_details( (int) $result['promoted_signup_id'] );
			if ( $promoted ) {
				$new_token = Tokens::generate_for_signup( (int) $promoted['id'] );
				$signups->update_token_hash( (int) $promoted['id'], $new_token['hash'] );
				Mailer::send_waitlist_promotion( $promoted, $new_token['token'] );
			}
		}

		( new AuditLogRepository() )->log( 'public.signup_cancel', 'signup', $signup_id, 'Public signup cancelled by token' );

		return array(
			'signup_id'           => $signup_id,
			'status'              => 'cancelled',
			'promoted_signup_id'  => (int) ( $result['promoted_signup_id'] ?? 0 ),
		);
	}
}
