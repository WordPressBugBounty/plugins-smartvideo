<?php
/**
 * Video URL normalization helper.
 *
 * @package Swarmify\Smartvideo
 * @since 2.4.0
 */

namespace Swarmify\Smartvideo;

class VideoUrl {
	const YT_REGEX = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

	/**
	 * Normalize a video URL. Converts YouTube watch/share URLs to embed format.
	 * All other URLs pass through unchanged.
	 *
	 * @param string $url Raw video URL.
	 * @return string Normalized URL.
	 */
	public static function normalize( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		if ( preg_match( self::YT_REGEX, $url, $match ) ) {
			return 'https://www.youtube.com/embed/' . $match[1];
		}
		return $url;
	}
}
