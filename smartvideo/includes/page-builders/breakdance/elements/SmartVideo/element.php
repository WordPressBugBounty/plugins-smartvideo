<?php
/**
 * SmartVideo element for Breakdance Page Builder.
 *
 * @package Swarmify\Breakdance
 */

namespace Swarmify\Breakdance;

use function Breakdance\Elements\c;

class SmartVideo extends \Breakdance\Elements\Element {

	/**
	 * Node type Breakdance stores in the saved tree.
	 *
	 * @return string
	 */
	public static function slug() {
		return __CLASS__;
	}

	/**
	 * Label shown in the Breakdance add panel.
	 *
	 * @return string
	 */
	public static function name() {
		return 'SmartVideo';
	}

	/**
	 * Add-panel bucket. Only Breakdance's eight registered slugs are visible.
	 *
	 * @return string
	 */
	public static function category() {
		return 'basic';
	}

	/**
	 * Base CSS class the per-node design-control selector is built from.
	 *
	 * @return string
	 */
	public static function className() {
		return 'bde-smartvideo';
	}

	/**
	 * Add-panel icon. A string that is not a built-in icon component name is
	 * treated as raw SVG (Element Studio labels this field "SVG code for icon").
	 *
	 * @return string
	 */
	public static function uiIcon() {
		return '<svg viewBox="0 0 47 47" xmlns="http://www.w3.org/2000/svg"><path d="M23.04 0l21 11.52v23.04l-21 11.52-21-11.52V11.52L23.05 0z" fill="#ffdd18"/><path d="M15.52 13.43c0-2.01 1.32-2.88 2.93-1.93l17.05 9.92c1.62.94 1.62 2.46 0 3.4l-17.05 9.93c-1.61.94-2.93.07-2.93-1.93v-19.4z" fill="#333"/></svg>';
	}

	/**
	 * Twig source (not a path) for the element's inner HTML.
	 *
	 * @return string
	 */
	public static function template() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Breakdance reads element templates off disk this way; WP_Filesystem is not initialized this early.
		return file_get_contents( __DIR__ . '/html.twig' );
	}

	/**
	 * Twig source for the per-node stylesheet the design controls drive.
	 *
	 * @return string
	 */
	public static function cssTemplate() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Breakdance reads element templates off disk this way; WP_Filesystem is not initialized this early.
		return file_get_contents( __DIR__ . '/css.twig' );
	}

	/**
	 * Content-tab controls. Saved at properties.content.<section>.<slug>.
	 *
	 * @return array
	 */
	public static function contentControls() {
		$items = array();
		foreach ( \Swarmify\Smartvideo\AspectRatio::get_options() as $value => $label ) {
			$items[] = array(
				'text'  => $label,
				'value' => $value,
			);
		}

		$custom_ratio_only = array(
			'path'    => 'content.smartvideo.aspect_ratio',
			'operand' => 'equals',
			'value'   => 'custom',
		);

		return array(
			c(
				'smartvideo',
				__( 'SmartVideo', 'swarmify' ),
				array(
					c(
						'url',
						__( 'Video URL', 'swarmify' ),
						array(),
						array(
							'type'        => 'text',
							'layout'      => 'vertical',
							'placeholder' => 'https://www.youtube.com/watch?v=...',
						),
						false,
						false,
						array(),
						array(
							'accepts' => 'string',
							'proOnly' => false,
						)
					),
					c(
						'media',
						__( 'Choose from Media Library', 'swarmify' ),
						array(),
						array(
							'type'         => 'wpmedia',
							'layout'       => 'vertical',
							'mediaOptions' => array(
								'acceptedFileTypes' => array( 'video' ),
								'multiple'          => false,
							),
						),
						false,
						false,
						array(),
						array(
							'accepts' => 'video',
							'proOnly' => false,
						)
					),
					c(
						'poster',
						__( 'Poster', 'swarmify' ),
						array(),
						array(
							'type'         => 'wpmedia',
							'layout'       => 'vertical',
							'mediaOptions' => array(
								'acceptedFileTypes' => array( 'image' ),
								'multiple'          => false,
							),
						),
						false,
						false,
						array(),
						array(
							'accepts' => 'image_url',
							'proOnly' => false,
						)
					),
					c(
						'poster_url',
						__( 'Poster URL', 'swarmify' ),
						array(),
						array(
							'type'        => 'text',
							'layout'      => 'vertical',
							'placeholder' => 'https://example.com/poster.jpg',
						),
						false,
						false,
						array(),
						array(
							'accepts' => 'string',
							'proOnly' => false,
						)
					),
					c(
						'aspect_ratio',
						__( 'Aspect ratio', 'swarmify' ),
						array(),
						array(
							'type'   => 'dropdown',
							'layout' => 'inline',
							'items'  => $items,
						),
						false,
						false,
						array()
					),
					c(
						'width',
						__( 'Width', 'swarmify' ),
						array(),
						array(
							'type'      => 'number',
							'layout'    => 'inline',
							'condition' => $custom_ratio_only,
						),
						false,
						false,
						array()
					),
					c(
						'height',
						__( 'Height', 'swarmify' ),
						array(),
						array(
							'type'      => 'number',
							'layout'    => 'inline',
							'condition' => $custom_ratio_only,
						),
						false,
						false,
						array()
					),
				),
				array(
					'type'   => 'section',
					'layout' => 'vertical',
				),
				false,
				false,
				array()
			),
			c(
				'playback',
				__( 'Playback', 'swarmify' ),
				array(
					self::toggle( 'autoplay', __( 'Autoplay', 'swarmify' ) ),
					self::toggle( 'muted', __( 'Muted', 'swarmify' ) ),
					self::toggle( 'loop', __( 'Loop', 'swarmify' ) ),
					self::toggle( 'controls', __( 'Controls', 'swarmify' ) ),
					self::toggle( 'playsinline', __( 'Play inline', 'swarmify' ) ),
					self::toggle( 'responsive', __( 'Responsive', 'swarmify' ) ),
				),
				array(
					'type'   => 'section',
					'layout' => 'vertical',
				),
				false,
				false,
				array()
			),
		);
	}

	/**
	 * Design-tab controls.
	 *
	 * @return array
	 */
	public static function designControls() {
		return array(
			c(
				'container',
				__( 'Container', 'swarmify' ),
				array(
					self::unit( 'width', __( 'Width', 'swarmify' ) ),
					c(
						'align',
						__( 'Align', 'swarmify' ),
						array(),
						array(
							'type'   => 'dropdown',
							'layout' => 'inline',
							'items'  => array(
								array(
									'text'  => __( 'Left', 'swarmify' ),
									'value' => 'left',
								),
								array(
									'text'  => __( 'Center', 'swarmify' ),
									'value' => 'center',
								),
								array(
									'text'  => __( 'Right', 'swarmify' ),
									'value' => 'right',
								),
							),
						),
						true,
						false,
						array()
					),
				),
				array( 'type' => 'section' ),
				false,
				false,
				array()
			),
			c(
				'spacing',
				__( 'Spacing', 'swarmify' ),
				array(
					self::unit( 'margin_top', __( 'Margin top', 'swarmify' ) ),
					self::unit( 'margin_bottom', __( 'Margin bottom', 'swarmify' ) ),
				),
				array(
					'type'           => 'section',
					'sectionOptions' => array( 'type' => 'popout' ),
				),
				false,
				false,
				array()
			),
		);
	}

	/**
	 * Property values a freshly dropped element starts with.
	 *
	 * Defaults are snapshotted at drop time, so changing the site-wide settings
	 * later leaves placed elements alone.
	 *
	 * @return array
	 */
	public static function defaultProperties() {
		return array(
			'content' => array(
				'smartvideo' => array(
					'aspect_ratio' => '16:9',
					'width'        => 1280,
					'height'       => 720,
				),
				'playback'   => array(
					'autoplay'    => 'on' === get_option( 'swarmify_default_autoplay', 'off' ),
					'muted'       => 'on' === get_option( 'swarmify_default_muted', 'off' ),
					'loop'        => 'on' === get_option( 'swarmify_default_loop', 'off' ),
					'controls'    => 'on' === get_option( 'swarmify_default_controls', 'on' ),
					'playsinline' => 'on' === get_option( 'swarmify_default_playsinline', 'off' ),
					'responsive'  => 'on' === get_option( 'swarmify_default_responsive', 'on' ),
				),
			),
		);
	}

	/**
	 * Property changes that re-run ssr.php in the builder canvas.
	 *
	 * @return array
	 */
	public static function propertyPathsToSsrElementWhenValueChanges() {
		return array( 'content.smartvideo', 'content.playback' );
	}

	/**
	 * Build one inline playback toggle.
	 *
	 * @param  string $slug  Property slug under content.playback.
	 * @param  string $label Control label.
	 * @return array
	 */
	private static function toggle( $slug, $label ) {
		return c(
			$slug,
			$label,
			array(),
			array(
				'type'   => 'toggle',
				'layout' => 'inline',
			),
			false,
			false,
			array()
		);
	}

	/**
	 * Build one breakpoint-aware unit control for the design tab.
	 *
	 * @param  string $slug  Property slug.
	 * @param  string $label Control label.
	 * @return array
	 */
	private static function unit( $slug, $label ) {
		return c(
			$slug,
			$label,
			array(),
			array(
				'type'   => 'unit',
				'layout' => 'inline',
			),
			true,
			false,
			array()
		);
	}
}
