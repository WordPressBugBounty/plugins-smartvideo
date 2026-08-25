<?php

namespace Swarmify\Smartvideo;

/**
 * Carries the <smartvideo> element through WordPress's kses sanitiser.
 *
 * Saves by anyone without `unfiltered_html` run post_content through
 * `wp_filter_post_kses`, which deletes unknown elements outright. That emptied
 * the Gutenberg block's body, leaving an invalid block and a blank front end.
 *
 * @since 2.4.4
 */
class Kses {

	/**
	 * Every attribute the Gutenberg block's current and deprecated save()
	 * functions can persist. Missing one drops just that attribute and the
	 * block still fails validation, so this list must stay exhaustive.
	 */
	const TAG_ATTRIBUTES = array(
		'src'         => true,
		'width'       => true,
		'height'      => true,
		'class'       => true,
		'poster'      => true,
		'autoplay'    => true,
		'muted'       => true,
		'loop'        => true,
		'controls'    => true,
		'playsinline' => true,
		'preload'     => true,
	);

	/**
	 * Scheme stand-in. `.invalid` is reserved by RFC 2606 and never resolves.
	 */
	const STAND_IN_PREFIX = 'https://swarmify.invalid/';

	/**
	 * Matches through the opening quote of a <smartvideo> src. content_save_pre
	 * content is slashed, so the quote may arrive escaped; case-sensitive because
	 * normalising an author's capitalisation would desync stored markup from save().
	 */
	const SRC_OPEN = '(<smartvideo\b[^>]*?\ssrc=\\\\?")';

	public static function allow_smartvideo( $tags, $context ) {
		if ( 'post' === $context ) {
			$tags['smartvideo'] = self::TAG_ATTRIBUTES;
		}
		return $tags;
	}

	/**
	 * Kses checks protocols against one global list, so the alternative -
	 * registering swarmify:// site-wide - would also let author-written
	 * <a href="swarmify://..."> through. Standing the URL up as https for the
	 * duration of the sanitise keeps that guarantee intact.
	 */
	public static function protect( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, 'swarmify://' ) ) {
			return $content;
		}
		// Safe to skip: no protect() means no stand-in, and restore() ignores content without one.
		if ( ! has_filter( 'content_save_pre', 'wp_filter_post_kses' ) ) {
			return $content;
		}

		$protected = preg_replace(
			'#' . self::SRC_OPEN . 'swarmify://#',
			'${1}' . self::STAND_IN_PREFIX,
			$content
		);

		return $protected ?? $content;
	}

	public static function restore( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, self::STAND_IN_PREFIX ) ) {
			return $content;
		}

		$restored = preg_replace(
			'#' . self::SRC_OPEN . preg_quote( self::STAND_IN_PREFIX, '#' ) . '#',
			'${1}swarmify://',
			$content
		);

		return $restored ?? $content;
	}
}
