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
	 * Unknown ratios — 'custom' and older content saved without one —
	 * fall back to the given width/height.
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
		return array( max( 0, (int) $width ), max( 0, (int) $height ) );
	}

	/**
	 * Placeholder shown when no video has been selected.
	 *
	 * @return string HTML placeholder.
	 */
	public static function empty_placeholder() {
		return '<div style="background:#2d2d2d;color:#888;display:flex;align-items:center;justify-content:center;aspect-ratio:16/9;max-width:100%;border-radius:4px;font-size:14px;gap:8px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif">'
			. '<svg width="40" height="40" viewBox="0 0 47 47" xmlns="http://www.w3.org/2000/svg"><path d="M23.04 0l21 11.52v23.04l-21 11.52-21-11.52V11.52L23.05 0z" fill="#ffde17"/><path d="M15.52 13.43c0-2.01 1.32-2.88 2.93-1.93l17.05 9.92c1.62.94 1.62 2.46 0 3.4l-17.05 9.93c-1.61.94-2.93.07-2.93-1.93v-19.4z" fill="#333"/></svg>'
			. esc_html__( 'No video selected', 'swarmify' )
			. '</div>';
	}

	/**
	 * Ratio options for aspect-ratio select fields.
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
