<?php

namespace Swarmify\Smartvideo;

/**
 * Shared aspect ratio presets for all page builders.
 *
 * @since 2.4.0
 */
class AspectRatio {

	const PRESETS = array(
		'16:9' => array( 640, 360 ),
		'4:3'  => array( 640, 480 ),
		'21:9' => array( 640, 274 ),
		'9:16' => array( 360, 640 ),
		'1:1'  => array( 640, 640 ),
	);

	/**
	 * Resolve width/height from an aspect ratio preset.
	 *
	 * Returns preset dimensions if the ratio is known, otherwise
	 * falls back to the provided width/height (for "custom" or
	 * backward-compatible content with no aspect_ratio field).
	 *
	 * @param string $ratio  Aspect ratio key (e.g. '16:9', 'custom', '').
	 * @param int    $width  Fallback width.
	 * @param int    $height Fallback height.
	 * @return array [ width, height ]
	 */
	public static function resolve( $ratio, $width = 1280, $height = 720 ) {
		if ( isset( self::PRESETS[ $ratio ] ) ) {
			return self::PRESETS[ $ratio ];
		}
		return array( (int) $width, (int) $height );
	}

	/**
	 * Charcoal placeholder for unconfigured video blocks.
	 *
	 * @return string HTML placeholder.
	 */
	public static function empty_placeholder() {
		return '<div style="background:#2d2d2d;color:#888;display:flex;align-items:center;justify-content:center;aspect-ratio:16/9;max-width:100%;border-radius:4px;font-size:14px;gap:8px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif">'
			. '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>'
			. esc_html__( 'No video selected', 'swarmify' )
			. '</div>';
	}

	/**
	 * Get options array for use in select fields.
	 *
	 * @return array Associative array of ratio => label.
	 */
	public static function get_options() {
		return array(
			'16:9'   => __( '16:9 (Standard)', 'swarmify' ),
			'4:3'    => __( '4:3 (Legacy)', 'swarmify' ),
			'21:9'   => __( '21:9 (Ultrawide)', 'swarmify' ),
			'9:16'   => __( '9:16 (Vertical)', 'swarmify' ),
			'1:1'    => __( '1:1 (Square)', 'swarmify' ),
			'custom' => __( 'Custom', 'swarmify' ),
		);
	}
}
