<?php
/**
 * Video URL normalization helper.
 *
 * @package Swarmify\Smartvideo
 * @since 2.4.0
 */

namespace Swarmify\Smartvideo;

class VideoUrl {
	const YT_REGEX = '%^(?:https?://)?(?:(?:www|m|music)\.)?(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts|live)/|.*[?&]v=)|youtu\.be/)([A-Za-z0-9_-]{11})%';

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
