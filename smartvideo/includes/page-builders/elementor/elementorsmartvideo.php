<?php

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ElementorSmartvideo extends \Elementor\Widget_Base {

	/**
	 * Get the unique widget machine name used by Elementor.
	 *
	 * @return string Widget identifier.
	 */
	public function get_name() {
		return 'smartvideo';
	}

	/**
	 * Get the human-readable widget title shown in the Elementor panel.
	 *
	 * @return string Localized widget title.
	 */
	public function get_title() {
		return esc_html__( 'SmartVideo', 'swarmify' );
	}

	/**
	 * Get the Elementor panel icon CSS class for this widget.
	 *
	 * @return string Icon class name.
	 */
	public function get_icon() {
		return 'smartvideo-icon';
	}

	/**
	 * Get the categories the widget appears under in the Elementor panel.
	 *
	 * @return string[] Category slugs.
	 */
	public function get_categories() {
		return array( 'Smart_video', 'basic' );
	}

	/**
	 * Get search keywords for the Elementor widget panel.
	 *
	 * @return string[] Keyword list used to surface this widget via search.
	 */
	public function get_keywords() {
		return array( 'video', 'player', 'embed', 'youtube', 'vimeo', 'smartvideo' );
	}

	/**
	 * Declare frontend script dependencies for this Elementor widget.
	 *
	 * @return string[] Registered script handles.
	 */
	public function get_script_depends() {
		return array( 'smartvideo-elementor-frontend' );
	}

	/**
	 * Register editor controls (settings sections and fields) for this widget.
	 *
	 * @return void
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'section_video',
			array(
				'label' => __( 'Video', 'swarmify' ),
			)
		);

		// Legacy controls — HIDDEN so Elementor preserves old saved values
		// in the settings model for backward compat + JS migration.
		foreach ( array( 'video_type', 'youtube', 'vimeo', 'swarmify_url' ) as $legacy ) {
			$this->add_control( $legacy, array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			) );
		}
		$this->add_control( 'another_source', array(
			'type'    => Controls_Manager::HIDDEN,
			'default' => '',
		) );

		$this->add_control(
			'video_source_type',
			array(
				'label'   => __( 'Source', 'swarmify' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'url',
				'options' => array(
					'url'           => __( 'URL', 'swarmify' ),
					'media_library' => __( 'Media Library', 'swarmify' ),
				),
			)
		);

		$this->add_control(
			'video_url',
			array(
				'label'       => __( 'Video URL', 'swarmify' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'https://www.youtube.com/watch?v=... or any video URL', 'swarmify' ),
				'description' => __( 'YouTube, Vimeo, Swarmify, or direct video URL', 'swarmify' ),
				'label_block' => true,
				'dynamic'     => array(
					'active' => true,
				),
				'condition'   => array(
					'video_source_type' => 'url',
				),
			)
		);

		$this->add_control(
			'media_library',
			array(
				'label'      => __( 'Choose Video', 'swarmify' ),
				'type'       => Controls_Manager::MEDIA,
				'media_type' => 'video',
				'dynamic'    => array(
					'active' => true,
				),
				'condition'  => array(
					'video_source_type' => 'media_library',
				),
			)
		);

		// Poster options
		$this->add_control(
			'poster_options',
			array(
				'label'     => __( 'Poster', 'swarmify' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'poster',
			array(
				'label'   => __( 'Source', 'swarmify' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'           => __( 'Automatic', 'swarmify' ),
					'media_library'  => __( 'Media library', 'swarmify' ),
					'another_source' => __( 'Other', 'swarmify' ),
				),
			)
		);

		$this->add_control(
			'poster_media_library',
			array(
				'label'      => __( 'Choose Poster Image', 'swarmify' ),
				'type'       => Controls_Manager::MEDIA,
				'media_type' => 'image',
				'condition'  => array(
					'poster' => 'media_library',
				),
				'dynamic'    => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'poster_another_src',
			array(
				'label'         => __( 'Poster URL', 'swarmify' ),
				'type'          => Controls_Manager::URL,
				'autocomplete'  => false,
				'show_external' => false,
				'label_block'   => true,
				'show_label'    => false,
				'media_type'    => 'image',
				'placeholder'   => __( 'https://example.com/poster.jpg', 'swarmify' ),
				'condition'     => array(
					'poster' => 'another_source',
				),
				'dynamic'       => array(
					'active' => true,
				),
			)
		);

		// Aspect ratio & dimensions
		$this->add_control(
			'aspect_ratio',
			array(
				'label'     => __( 'Aspect Ratio', 'swarmify' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '16:9',
				'options'   => \Swarmify\Smartvideo\AspectRatio::get_options(),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'video_height',
			array(
				'label'     => __( 'Height', 'swarmify' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '720',
				'condition' => array(
					'aspect_ratio' => 'custom',
				),
			)
		);

		$this->add_control(
			'video_width',
			array(
				'label'     => __( 'Width', 'swarmify' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '1280',
				'condition' => array(
					'aspect_ratio' => 'custom',
				),
			)
		);

		$this->end_controls_section();

		// basic settings
		$this->start_controls_section(
			'Basic_setting',
			array(
				'label' => __( 'Basic options', 'swarmify' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'swarmify' ),
				'description'  => __( "Automatically start playing when the video is visible. Most browsers require 'Muted' to be enabled.", 'swarmify' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'swarmify' ),
				'label_off'    => __( 'No', 'swarmify' ),
				'return_value' => 'yes',
				'default'      => 'on' === get_option( 'swarmify_default_autoplay', 'off' ) ? 'yes' : 'no',
			)
		);

		$this->add_control(
			'muted',
			array(
				'label'        => __( 'Muted', 'swarmify' ),
				'description'  => __( 'Start playback with audio muted.', 'swarmify' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'swarmify' ),
				'label_off'    => __( 'No', 'swarmify' ),
				'return_value' => 'yes',
				'default'      => 'on' === get_option( 'swarmify_default_muted', 'off' ) ? 'yes' : 'no',
			)
		);
		$this->add_control(
			'loop',
			array(
				'label'        => __( 'Loop', 'swarmify' ),
				'description'  => __( 'Restart the video automatically when it reaches the end.', 'swarmify' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'swarmify' ),
				'label_off'    => __( 'No', 'swarmify' ),
				'return_value' => 'yes',
				'default'      => 'on' === get_option( 'swarmify_default_loop', 'off' ) ? 'yes' : 'no',
			)
		);

		$this->end_controls_section();

		// Advance options
		$this->start_controls_section(
			'advance_setting',
			array(
				'label' => __( 'Advanced options', 'swarmify' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'controls',
			array(
				'label'        => __( 'Controls', 'swarmify' ),
				'description'  => __( 'Show player controls (play, pause, volume, etc.).', 'swarmify' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'swarmify' ),
				'label_off'    => __( 'No', 'swarmify' ),
				'return_value' => 'yes',
				'default'      => 'on' === get_option( 'swarmify_default_controls', 'on' ) ? 'yes' : 'no',
			)
		);

		$this->add_control(
			'playsinline',
			array(
				'label'        => __( 'Play inline', 'swarmify' ),
				'description'  => __( 'Keep the video inline on iOS instead of opening in fullscreen.', 'swarmify' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'swarmify' ),
				'label_off'    => __( 'No', 'swarmify' ),
				'return_value' => 'yes',
				'default'      => 'on' === get_option( 'swarmify_default_playsinline', 'off' ) ? 'yes' : 'no',
			)
		);
		$this->add_control(
			'responsive',
			array(
				'label'        => __( 'Responsive', 'swarmify' ),
				'description'  => __( 'Make the video responsive to fill its container width while maintaining aspect ratio.', 'swarmify' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'swarmify' ),
				'label_off'    => __( 'No', 'swarmify' ),
				'return_value' => 'yes',
				'default'      => 'on' === get_option( 'swarmify_default_responsive', 'on' ) ? 'yes' : 'no',
			)
		);
		$this->end_controls_section();
	}
	/*
	 * End style section
	 * */
	/**
	 * Render the SmartVideo Elementor widget on the front end and in the editor.
	 *
	 * @return void
	 */
	protected function render() {
		// Disabled warning — only in Elementor editor, not on the frontend.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() &&
			( 'on' !== get_option( 'swarmify_status' ) || '' === get_option( 'swarmify_cdn_key', '' ) ) ) {
			printf(
				'<div style="background:#fcf0c0;border:1px solid #d4a72c;border-radius:4px;padding:8px 12px;margin-bottom:10px;font-size:13px;color:#6b5900">%s</div>',
				esc_html__( 'SmartVideo is currently disabled. Go to the SmartVideo settings page to enable it.', 'swarmify' )
			);
		}

		$settings     = $this->get_settings_for_display();
		$swarmify_url = '';

		// New 2-option source type (v2.4+).
		$source_type = $settings['video_source_type'] ?? '';
		if ( 'url' === $source_type && ! empty( $settings['video_url'] ) ) {
			$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['video_url'] );
		} elseif ( 'media_library' === $source_type && ! empty( $settings['media_library']['url'] ) ) {
			$swarmify_url = $settings['media_library']['url'];
		}

		// Backward compat: old 5-option video_type field.
		if ( empty( $swarmify_url ) && ! empty( $settings['video_type'] ) ) {
			$video_type = $settings['video_type'];
			if ( 'media_library' === $video_type && ! empty( $settings['media_library']['url'] ) ) {
				$swarmify_url = $settings['media_library']['url'];
			} elseif ( 'youtube' === $video_type && ! empty( $settings['youtube'] ) ) {
				$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['youtube'] );
			} elseif ( 'vimeo' === $video_type && ! empty( $settings['vimeo'] ) ) {
				$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['vimeo'] );
			} elseif ( 'swarmify_url' === $video_type && ! empty( $settings['swarmify_url'] ) ) {
				$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['swarmify_url'] );
			} elseif ( 'another_source' === $video_type && ! empty( $settings['another_source']['url'] ) ) {
				$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['another_source']['url'] );
			}
		}

		if ( empty( $swarmify_url ) ) {
			// Show the "No video selected" placeholder only in the Elementor
			// editor. Frontend visitors must not see authoring chrome on
			// empty widgets.
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static self-built markup; the only dynamic value is escaped via esc_html__() in empty_placeholder(), and wp_kses would lowercase the SVG viewBox.
				echo \Swarmify\Smartvideo\AspectRatio::empty_placeholder();
			}
			return;
		}

		// Elementor strips conditional control values from get_settings_for_display()
		// when their condition isn't met (video_width/video_height require
		// aspect_ratio=custom; switchers may also be absent on legacy saves), so
		// every read here needs a ?? default to avoid PHP 8 undefined-key warnings
		// and the 0×0 fallback that AspectRatio::resolve produces from null input.
		$aspect_ratio           = $settings['aspect_ratio'] ?? '';
		list( $width, $height ) = \Swarmify\Smartvideo\AspectRatio::resolve(
			$aspect_ratio,
			$settings['video_width'] ?? 1280,
			$settings['video_height'] ?? 720
		);
		$responsive             = 'yes' === ( $settings['responsive'] ?? '' ) ? 'class="swarm-fluid"' : '';
		$poster_url             = null;
		$poster_type            = $settings['poster'] ?? 'none';
		if ( 'none' !== $poster_type ) {
			$poster_url = 'media_library' === $poster_type
				? ( $settings['poster_media_library']['url'] ?? null )
				: ( $settings['poster_another_src']['url'] ?? null );
		}
		$poster = ! empty( $poster_url ) ? sprintf( 'poster="%s"', esc_url( $poster_url )) : '';

		\Swarmify\Smartvideo\SchemaCollector::add( $swarmify_url, $poster_url ?: '' );

		// Build attribute list — empty/disabled attrs are skipped so we never
		// emit double-space runs inside the tag. Canonical order:
		// src, poster, autoplay, muted, loop, controls, playsinline, width, height, class.
		$attrs   = array();
		$attrs[] = 'src="' . esc_url( $swarmify_url, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) ) . '"';
		if ( ! empty( $poster ) ) {
			$attrs[] = $poster;
		}
		if ( 'yes' === ( $settings['autoplay'] ?? '' ) ) {
			$attrs[] = 'autoplay';
		}
		if ( 'yes' === ( $settings['muted'] ?? '' ) ) {
			$attrs[] = 'muted';
		}
		if ( 'yes' === ( $settings['loop'] ?? '' ) ) {
			$attrs[] = 'loop';
		}
		if ( 'yes' === ( $settings['controls'] ?? '' ) ) {
			$attrs[] = 'controls';
		}
		if ( 'yes' === ( $settings['playsinline'] ?? '' ) ) {
			$attrs[] = 'playsinline';
		}
		$attrs[] = 'width="' . esc_attr( $width ) . '"';
		$attrs[] = 'height="' . esc_attr( $height ) . '"';
		if ( ! empty( $responsive ) ) {
			$attrs[] = $responsive;
		}

		$smartvideo = '<smartvideo ' . implode( ' ', $attrs ) . '></smartvideo>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped at construction (esc_url/esc_attr); the facade wrapper escapes its own markup; <smartvideo> is a custom element wp_kses_post would strip.
		echo \Swarmify\Smartvideo\Facade::wrap(
			$smartvideo,
			array(
				'src'      => $swarmify_url,
				'poster'   => $poster_url ?: '',
				'width'    => $width,
				'height'   => $height,
				'autoplay' => 'yes' === ( $settings['autoplay'] ?? '' ),
			)
		);
	}
}
