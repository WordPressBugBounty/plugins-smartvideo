<?php

namespace Swarmify\Smartvideo;

/**
 * Shared helper to build and sanitize CTA overlay markup and payloads.
 *
 * @since 2.4.0
 */
class OverlayMarkup {

	/**
	 * Build the overlay settings payload.
	 *
	 * @param  array  $args        Raw attributes.
	 * @param  bool   $legacy_mode Whether the legacy player is active.
	 * @param  string $cached_tier The cached account tier.
	 * @return array|null The overlay config payload or null if disabled/suppressed.
	 */
	public static function build( array $args, $legacy_mode, $cached_tier ) {
		if ( $legacy_mode ) {
			return null;
		}

		// Growth/Pro/unknown/absent allowed. Startup suppressed.
		if ( 'startup' === $cached_tier ) {
			return null;
		}

		$enabled = isset( $args['overlayEnabled'] ) && (bool) $args['overlayEnabled'];
		if ( ! $enabled ) {
			return null;
		}

		$text = isset( $args['overlayText'] ) ? (string) $args['overlayText'] : '';
		$text = trim( $text );
		if ( '' === $text ) {
			return null;
		}
		if ( mb_strlen( $text ) > 200 ) {
			$text = mb_substr( $text, 0, 200 );
		}
		$escaped_text = esc_html( $text );

		$url = isset( $args['overlayUrl'] ) ? (string) $args['overlayUrl'] : '';
		$url = trim( $url );
		if ( '' !== $url ) {
			// Whitelist-only: anything that is neither absolute http(s) nor a
			// promotable bare domain is dropped (javascript:, data:, etc.).
			if ( preg_match( '/[\s\p{Z}]/u', $url ) ) {
				$url = '';
			} elseif ( ! preg_match( '/^https?:\/\//i', $url ) ) {
				if ( preg_match( '/^[\w\-]+(?:\.[\w\-]+)+\S*$/', $url ) ) {
					$url = 'https://' . $url;
				} else {
					$url = '';
				}
			}
		}

		if ( '' !== $url ) {
			if ( mb_strlen( $url ) > 500 ) {
				$url = mb_substr( $url, 0, 500 );
			}
			$url = esc_url_raw( $url );
			$url = str_replace( [ "'", '"' ], [ '%27', '%22' ], $url );
		}

		$color = isset( $args['overlayColor'] ) ? (string) $args['overlayColor'] : '';
		if ( ! preg_match( '/^#[0-9a-f]{6}$/i', $color ) ) {
			$color = '#ffffff';
		}

		$bg_enabled = ! isset( $args['overlayBg'] ) || (bool) $args['overlayBg'];
		$bg_color   = isset( $args['overlayBgColor'] ) ? (string) $args['overlayBgColor'] : '';
		if ( ! preg_match( '/^#[0-9a-f]{6}$/i', $bg_color ) ) {
			$bg_color = '#000000';
		}

		$bg_opacity = ( isset( $args['overlayBgOpacity'] ) && '' !== $args['overlayBgOpacity'] ) ? (int) $args['overlayBgOpacity'] : 75;
		if ( $bg_opacity < 0 || $bg_opacity > 100 ) {
			$bg_opacity = 75;
		}

		$style = "color:{$color};font:600 14px/1.4 system-ui,sans-serif;display:inline-block;";
		if ( $bg_enabled ) {
			list( $r, $g, $b ) = sscanf( $bg_color, '#%02x%02x%02x' );
			// number_format: plugin supports PHP 7.3, where interpolated
			// float-to-string honors LC_NUMERIC (a comma-decimal locale set by
			// another plugin would render 0,75 → malformed rgba()). Trim
			// trailing zeros so output matches the old cast ("1", "0.5").
			$alpha  = rtrim( rtrim( number_format( $bg_opacity / 100, 2, '.', '' ), '0' ), '.' );
			$style .= "background:rgba({$r},{$g},{$b},{$alpha});padding:8px 16px;border-radius:4px;";
		} else {
			$style .= 'text-shadow:0 1px 3px rgba(0,0,0,.6);';
		}

		$is_valid_url = ( '' !== $url && preg_match( '/^https?:\/\//i', $url ) );
		if ( $is_valid_url ) {
			$content = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="text-decoration:none;cursor:pointer;' . $style . '">' . $escaped_text . '</a>';
		} else {
			$content = '<span style="' . $style . '">' . $escaped_text . '</span>';
		}

		$align          = isset( $args['overlayAlign'] ) ? (string) $args['overlayAlign'] : 'bottom';
		$allowed_aligns = [ 'top', 'top-left', 'top-right', 'bottom', 'bottom-left', 'bottom-right' ];
		if ( ! in_array( $align, $allowed_aligns, true ) ) {
			$align = 'bottom';
		}

		$start_input = ( isset( $args['overlayStart'] ) && '' !== $args['overlayStart'] ) ? $args['overlayStart'] : 3;
		$start       = ( 'play' === $start_input ) ? 'play' : (int) $start_input;
		if ( 'play' !== $start && ! in_array( $start, [ 3, 5, 10 ], true ) ) {
			$start = 3;
		}

		$overlay = [
			'content'        => $content,
			'align'          => $align,
			'start'          => $start,
			'showBackground' => false,
		];

		if ( isset( $args['overlayEnd'] ) && '' !== $args['overlayEnd'] ) {
			$end = (int) $args['overlayEnd'];
			if ( in_array( $end, [ 10, 15, 20 ], true ) && ( 'play' === $start || $end > $start ) ) {
				$overlay['end'] = $end;
			}
		}

		return [
			'plugins' => [
				'overlay' => [
					'overlays' => [ $overlay ],
				],
			],
		];
	}
}
