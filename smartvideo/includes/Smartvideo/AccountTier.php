<?php

namespace Swarmify\Smartvideo;

/**
 * Resolves the customer's account tier from their CDN key via a Swarmify
 * cloud function, cached in a transient.
 *
 * @since 2.4.0
 */
class AccountTier {

	const TRANSIENT        = 'smartvideo_account_tier';
	const UNKNOWN          = 'unknown';
	const DEFAULT_ENDPOINT = 'https://us-central1-deft-computing-220.cloudfunctions.net/plugin-tier';

	private const ALLOWED = [ 'startup', 'growth', 'pro' ];

	/** @var Settings */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	private function endpoint() {
		return defined( 'SMARTVIDEO_TIER_ENDPOINT' ) ? SMARTVIDEO_TIER_ENDPOINT : self::DEFAULT_ENDPOINT;
	}

	/**
	 * @return string|null 'startup', 'growth', or 'pro', or null when unknown.
	 */
	public function get() {
		$key = $this->settings->get( 'swarmify_cdn_key' );
		if ( ! is_string( $key ) || '' === $key ) {
			return null;
		}

		$cached = get_transient( self::TRANSIENT );
		if ( self::UNKNOWN === $cached ) {
			return null;
		}
		if ( in_array( $cached, self::ALLOWED, true ) ) {
			return $cached;
		}

		// The key goes in a header because the endpoint's platform logs
		// record request URLs.
		$response = wp_remote_get(
			$this->endpoint(),
			[
				'timeout' => 3,
				'headers' => [ 'X-Swarm-Key' => $key ],
			]
		);

		$tier = $this->parse( $response );
		if ( null === $tier ) {
			// Fail open: a lookup failure must never lock a customer out of features.
			set_transient( self::TRANSIENT, self::UNKNOWN, MINUTE_IN_SECONDS );
			return null;
		}

		// Short TTL — a plan upgrade must reach the plugin within minutes.
		set_transient( self::TRANSIENT, $tier, 4 * MINUTE_IN_SECONDS );
		return $tier;
	}

	/**
	 * Cached-only tier read for frontend render paths — never performs HTTP.
	 * An expired or sentinel cache reads as null (fail-open) until an admin
	 * page view refreshes it via get().
	 *
	 * @return string|null Tier when a valid cached value exists, null otherwise.
	 */
	public function get_cached() {
		$cached = get_transient( self::TRANSIENT );
		return in_array( $cached, self::ALLOWED, true ) ? $cached : null;
	}

	private function parse( $response ) {
		if ( is_wp_error( $response ) ) {
			return null;
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! isset( $data['tier'] ) ) {
			return null;
		}
		return in_array( $data['tier'], self::ALLOWED, true ) ? $data['tier'] : null;
	}

	/**
	 * Drops the cached tier when the CDN key changes.
	 */
	public static function flush_cache() {
		delete_transient( self::TRANSIENT );
	}
}
