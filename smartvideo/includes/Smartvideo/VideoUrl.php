<?php
/**
 * Video URL normalization helper.
 *
 * @package Swarmify\Smartvideo
 * @since 2.4.0
 */

namespace Swarmify\Smartvideo;

class VideoUrl {
	// Anchored so a YouTube URL buried inside an unrelated string is not
	// rewritten, but tolerant of what users actually have stored: any
	// subdomain, protocol-relative and scheme-less forms, and mixed case.
	const YT_REGEX = '%^(?:https?:)?(?://)?(?:[A-Za-z0-9-]+\.)*(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts|live)/|.*[?&]v=)|youtu\.be/)([A-Za-z0-9_-]{11})%i';

	/**
	 * Normalize a video URL. Converts YouTube watch/share URLs to embed format.
	 * All other URLs pass through unchanged.
	 *
	 * @param string $url Raw video URL.
	 * @return string Normalized URL.
	 */
	public static function normalize( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return '';
		}
		// Stored builder fields routinely carry pasted whitespace, and the
		// pattern is anchored.
		$url = trim( $url );
		if ( preg_match( self::YT_REGEX, $url, $match ) ) {
			$embed = 'https://www.youtube.com/embed/' . $match[1];

			$query = wp_parse_url( $url, PHP_URL_QUERY );
			if ( $query ) {
				$params = wp_parse_args( $query );
				$start  = absint( $params['t'] ?? $params['start'] ?? 0 );
				if ( $start > 0 ) {
					$embed .= '?start=' . $start;
				}
			}

			return $embed;
		}
		return $url;
	}
}
