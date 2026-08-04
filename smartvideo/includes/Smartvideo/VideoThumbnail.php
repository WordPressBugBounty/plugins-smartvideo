<?php
/**
 * Shared thumbnail resolution for video source URLs.
 *
 * @package Swarmify\Smartvideo
 * @since 2.4.0
 */

namespace Swarmify\Smartvideo;

class VideoThumbnail {

	/**
	 * Resolve a Vimeo thumbnail URL via oEmbed, cached in a transient.
	 *
	 * Returns '' for non-Vimeo URLs and on any lookup failure.
	 *
	 * @param string $src Video source URL.
	 * @return string Thumbnail URL, or empty string.
	 */
	public static function vimeo( $src ) {
		if ( ! preg_match( '/(?:vimeo\.com\/(?:video\/)?)(\d+)/', $src, $m ) ) {
			return '';
		}

		$cache_key = 'sv_vimeo_thumb_' . $m[1];
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// wp_safe_remote_get blocks SSRF to private IPs.
		$response = wp_safe_remote_get(
			'https://vimeo.com/api/oembed.json?url=' . rawurlencode( 'https://vimeo.com/' . $m[1] ),
			[ 'timeout' => 3 ]
		);

		// Cache failures briefly: a host that blocks outbound HTTP would
		// otherwise pay the full timeout on every pageview, and the short
		// TTL lets a transient outage recover on its own.
		if ( is_wp_error( $response ) ) {
			set_transient( $cache_key, '', 15 * MINUTE_IN_SECONDS );
			return '';
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// Vimeo reported a real error (private or removed video, rate
		// limit) — cache the miss for a day so we don't hammer it.
		if ( $code >= 400 ) {
			set_transient( $cache_key, '', DAY_IN_SECONDS );
			return '';
		}

		if ( 200 === $code ) {
			$data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( is_object( $data ) && ! empty( $data->thumbnail_url ) ) {
				$thumb_url = (string) $data->thumbnail_url;
				$parts     = wp_parse_url( $thumb_url );
				// Persist only a clean https URL — the cached value feeds
				// straight into the page's JSON-LD.
				if ( is_array( $parts )
					&& isset( $parts['scheme'], $parts['host'] )
					&& 'https' === $parts['scheme']
					&& '' !== $parts['host']
					&& ! isset( $parts['user'] )
				) {
					set_transient( $cache_key, $thumb_url, WEEK_IN_SECONDS );
					return $thumb_url;
				}
			}
		}

		// Anything else (bad body, rejected thumbnail URL): cache a
		// day-long miss.
		set_transient( $cache_key, '', DAY_IN_SECONDS );
		return '';
	}
}
