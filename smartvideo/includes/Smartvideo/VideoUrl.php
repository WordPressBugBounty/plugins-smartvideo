<?php
/**
 * Video URL normalization helper.
 *
 * @package Swarmify\Smartvideo
 * @since 2.4.0
 */

namespace Swarmify\Smartvideo;

class VideoUrl {
	// Anchored so a YouTube URL buried in a larger string is not rewritten,
	// but tolerant of any subdomain, missing scheme, and mixed case.
	const YT_REGEX = '%^(?:https?:)?(?://)?(?:[A-Za-z0-9-]+\.)*(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts|live)/|.*[?&]v=)|youtu\.be/)([A-Za-z0-9_-]{11})%i';

	/**
	 * Converts YouTube watch/share URLs to embed format; all other URLs pass
	 * through unchanged.
	 *
	 * @param string $url Raw video URL.
	 * @return string Normalized URL.
	 */
	public static function normalize( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return '';
		}
		// Pasted whitespace would break the anchored match.
		$url = trim( $url );
		if ( preg_match( self::YT_REGEX, $url, $match ) ) {
			$embed = 'https://www.youtube.com/embed/' . $match[1];

			$query = wp_parse_url( $url, PHP_URL_QUERY );
			if ( $query ) {
				$params = wp_parse_args( $query );
				$raw_t  = $params['t'] ?? $params['start'] ?? '';
				$start  = self::parse_time( $raw_t );
				if ( $start > 0 ) {
					$embed .= '?start=' . $start;
				}
			}

			return $embed;
		}
		return $url;
	}

	/**
	 * Parse time offsets containing h, m, and s designators.
	 *
	 * @param string $str Raw time string.
	 * @return int Total seconds.
	 */
	public static function parse_time( $str ) {
		$str = trim( (string) $str );
		if ( '' === $str ) {
			return 0;
		}

		if ( ctype_digit( $str ) ) {
			return (int) $str;
		}

		if ( preg_match_all( '/(\d+)([hms])/i', $str, $matches, PREG_SET_ORDER ) ) {
			$seconds = 0;
			foreach ( $matches as $match ) {
				$val  = (int) $match[1];
				$unit = strtolower( $match[2] );
				if ( 'h' === $unit ) {
					$seconds += $val * 3600;
				} elseif ( 'm' === $unit ) {
					$seconds += $val * 60;
				} elseif ( 's' === $unit ) {
					$seconds += $val;
				}
			}
			return $seconds;
		}

		// Leading-digits match mirrors JS parseInt(s, 10) — a bare (int) cast
		// diverges on exponent strings ("1e3" → 1000 vs parseInt's 1).
		return preg_match( '/^[+-]?\d+/', $str, $m ) ? (int) $m[0] : 0;
	}
}
