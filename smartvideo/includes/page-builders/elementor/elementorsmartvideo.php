<?php

namespace Elementor;

class ElementorSmartvideo extends \Elementor\Widget_Base {

	public function get_name() {
		return 'smartvideo';
	}

	public function get_title() {
		return esc_html__( 'SmartVideo', 'swarmify' );
	}

	public function get_icon() {
		return 'smartvideo-icon';
	}

	public function get_categories() {
		return array( 'Smart_video', 'basic' );
	}

	public function get_keywords() {
		return array( 'video', 'player', 'embed', 'youtube', 'vimeo', 'smartvideo' );
	}

	public function get_script_depends() {
		return array( 'smartvideo-elementor-frontend' );
	}

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
			$this->add_control( $legacy, array( 'type' => Controls_Manager::HIDDEN, 'default' => '' ) );
		}
		$this->add_control( 'another_source', array( 'type' => Controls_Manager::HIDDEN, 'default' => '' ) );

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
				'placeholder' => 'https://www.youtube.com/watch?v=... or any video URL',
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
				$swarmify_url = $settings['vimeo'];
			} elseif ( 'swarmify_url' === $video_type && ! empty( $settings['swarmify_url'] ) ) {
				$swarmify_url = $settings['swarmify_url'];
			} elseif ( 'another_source' === $video_type && ! empty( $settings['another_source']['url'] ) ) {
				$swarmify_url = $settings['another_source']['url'];
			}
		}

		if ( empty( $swarmify_url ) ) {
			echo \Swarmify\Smartvideo\AspectRatio::empty_placeholder();
			return;
		}

		$aspect_ratio = isset( $settings['aspect_ratio'] ) ? $settings['aspect_ratio'] : '';
		list( $width, $height ) = \Swarmify\Smartvideo\AspectRatio::resolve(
			$aspect_ratio,
			$settings['video_width'],
			$settings['video_height']
		);
		$responsive = 'yes' === $settings['responsive'] ? 'class="' . esc_attr( 'swarm-fluid' ) . '"' : '';
		$poster_url = null;
		if ( 'none' !== $settings['poster'] ) {
			$poster_url = 'media_library' === $settings['poster']
				? ( $settings['poster_media_library']['url'] ?? null )
				: ( $settings['poster_another_src']['url'] ?? null );
		}
		$poster     = ! empty( $poster_url ) ? sprintf( 'poster="%s"', esc_url( $poster_url )) : '';

		$autoplay    = 'yes' === $settings['autoplay'] ? 'autoplay' : '';
		$muted       = 'yes' === $settings['muted'] ? 'muted' : '';
		$loop        = 'yes' === $settings['loop'] ? 'loop' : '';
		$controls    = 'yes' === $settings['controls'] ? 'controls' : '';
		$playsinline  = 'yes' === $settings['playsinline'] ? 'playsinline' : '';
		$preload_val  = isset( $settings['preload'] ) ? $settings['preload'] : 'auto';
		$preload_attr = ( 'auto' !== $preload_val ) ? sprintf( 'preload="%s"', esc_attr( $preload_val ) ) : '';

		\Swarmify\Smartvideo\SchemaCollector::add( $swarmify_url, $poster_url ?: '' );

		printf(
			'<smartvideo src="%s" width="%s" height="%s" %s %s %s %s %s %s %s %s></smartvideo>',
			esc_url( $swarmify_url ),
			esc_attr( $width ),
			esc_attr( $height ),
			$poster,
			$responsive,
			esc_attr( $autoplay ),
			esc_attr( $muted ),
			esc_attr( $loop ),
			esc_attr( $controls ),
			esc_attr( $playsinline ),
			$preload_attr
		);
	}

}
