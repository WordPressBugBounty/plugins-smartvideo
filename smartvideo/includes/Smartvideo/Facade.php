<?php
/**
 * Server-painted poster facade wrapped around each rendered SmartVideo.
 *
 * The poster <img> becomes the LCP element and paints well before the player
 * boots underneath; facade.js hands off to the player once it is interactive.
 *
 * @package Swarmify\Smartvideo
 * @since 2.4.0
 */

namespace Swarmify\Smartvideo;

class Facade {

	/** Per-request count of emitted facades; the first gets fetchpriority=high. */
	private static $count = 0;

	private const HEX_COLOR = '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i';

	/**
	 * Wrap a rendered <smartvideo> tag in the poster facade.
	 *
	 * Returns the input unchanged when the facade is disabled (toggle off /
	 * legacy player) or no poster can be resolved.
	 *
	 * @param string        $smartvideo_html The rendered <smartvideo>…</smartvideo> string.
	 * @param array         $ctx             src, poster, width, height, autoplay.
	 * @param Settings|null $settings        Plugin settings; constructed if omitted.
	 * @return string
	 */
	public static function wrap( $smartvideo_html, array $ctx, ?Settings $settings = null ) {
		if ( null === $settings ) {
			$settings = new Settings( 'smartvideo', defined( 'SWARMIFY_PLUGIN_VERSION' ) ? SWARMIFY_PLUGIN_VERSION : '' );
		}

		if ( 'on' !== $settings->get( 'swarmify_toggle_facade' ) ) {
			return $smartvideo_html;
		}
		if ( 'on' === $settings->get( 'swarmify_toggle_legacy_player' ) ) {
			return $smartvideo_html;
		}

		$poster = self::resolve_poster( $ctx['src'] ?? '', $ctx['poster'] ?? '' );
		if ( '' === $poster ) {
			return $smartvideo_html;
		}

		$tag = preg_replace( '/<smartvideo\b/i', '<smartvideo data-swarm-no-poster', $smartvideo_html, 1 );
		if ( null === $tag ) {
			return $smartvideo_html;
		}

		$autoplay = ! empty( $ctx['autoplay'] );
		$has_dims = is_numeric( $ctx['width'] ?? null ) && is_numeric( $ctx['height'] ?? null )
			&& (int) $ctx['width'] > 0 && (int) $ctx['height'] > 0;
		$width    = $has_dims ? (int) $ctx['width'] : 1280;
		$height   = $has_dims ? (int) $ctx['height'] : 720;
		$is_first = ( 0 === self::$count );
		++self::$count;

		$wrapper_class = 'sv-facade sv-facade--wp' . ( $autoplay ? ' sv-facade--autoplay' : '' );
		$style         = self::inline_style( $settings );
		if ( $has_dims ) {
			// facade-base.css reserves a 16:9 box; explicit dims must reserve the
			// true box or the player handoff shifts the layout the facade holds.
			$style .= ( '' !== $style ? ';' : '' ) . 'aspect-ratio:' . $width . '/' . $height;
		}

		$priority_attr = $is_first ? 'fetchpriority="high"' : 'loading="lazy"';
		$img           = sprintf(
			'<img class="sv-facade__poster" src="%s" width="%d" height="%d" %s alt="">',
			esc_url( $poster ),
			$width,
			$height,
			$priority_attr
		);

		$button = sprintf(
			'<button type="button" class="sv-facade-btn%s" aria-label="%s" data-sv-facade-play><span class="sv-facade-btn__glyph"></span></button>',
			self::shape_modifier( $settings ),
			esc_attr__( 'Play video', 'swarmify' )
		);

		return sprintf(
			'<div class="%s"%s><span class="sv-facade__layer">%s%s</span>%s</div>',
			esc_attr( $wrapper_class ),
			'' !== $style ? ' style="' . esc_attr( $style ) . '"' : '',
			$img,
			$button,
			$tag
		);
	}

	/**
	 * Resolve the poster URL: author poster, else YouTube, else Vimeo, else ''.
	 *
	 * @param string $src           Video source URL.
	 * @param string $author_poster Author-set poster (may be '').
	 * @return string
	 */
	private static function resolve_poster( $src, $author_poster ) {
		if ( '' !== $author_poster ) {
			return $author_poster;
		}
		// maxresdefault matches the player's poster quality; facade.js falls
		// back to hqdefault on a 404.
		if ( preg_match( VideoUrl::YT_REGEX, $src, $m ) ) {
			return 'https://i.ytimg.com/vi/' . $m[1] . '/maxresdefault.jpg';
		}
		return VideoThumbnail::vimeo( $src );
	}

	/**
	 * Build the CSS-variable inline style from the theme options that are set.
	 *
	 * @param Settings $settings Plugin settings.
	 * @return string Style declarations, or '' when none apply.
	 */
	public static function inline_style( Settings $settings ) {
		$vars = [];

		$primary = $settings->get( 'swarmify_theme_primarycolor' );
		if ( '' !== $primary && preg_match( self::HEX_COLOR, $primary ) ) {
			$vars[] = '--primary-color:' . $primary;
		}

		$secondary = $settings->get( 'swarmify_theme_secondarycolor' );
		if ( '' !== $secondary && preg_match( self::HEX_COLOR, $secondary ) ) {
			$vars[] = '--secondary-color:' . $secondary;
		}

		$radius = $settings->get( 'swarmify_theme_cornerradius' );
		if ( '' !== $radius && is_numeric( $radius ) ) {
			$vars[] = '--swarm-radius:' . (int) $radius . 'px';
		}

		$button_radius = $settings->get( 'swarmify_theme_button_radius' );
		if ( '' !== $button_radius && is_numeric( $button_radius ) ) {
			$vars[] = '--big-play-radius:' . (int) $button_radius . 'px';
		}

		return implode( ';', $vars );
	}

	/**
	 * Map the button-shape option to a facade modifier class suffix.
	 * Hexagon (the default) has no modifier.
	 *
	 * @param Settings $settings Plugin settings.
	 * @return string ' sv-facade-btn--rectangle', ' sv-facade-btn--circle', or ''.
	 */
	public static function shape_modifier( Settings $settings ) {
		$shape = $settings->get( 'swarmify_theme_button' );
		if ( 'rectangle' === $shape || 'circle' === $shape ) {
			return ' sv-facade-btn--' . $shape;
		}
		return '';
	}

	/**
	 * Reset per-request render state. Exists for test isolation; each real
	 * page load is a fresh PHP process.
	 */
	public static function reset() {
		self::$count = 0;
	}
}
