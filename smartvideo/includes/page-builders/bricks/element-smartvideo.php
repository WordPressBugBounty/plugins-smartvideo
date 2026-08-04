<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Smartvideo_Element_Bricks extends \Bricks\Element {

	public $category = 'basic';
	public $name     = 'smartvideo';
	public $icon     = 'smartvideo-bricks-icon';

	/**
	 * Get the human-readable label shown in the Bricks element picker.
	 *
	 * @return string Localized element label.
	 */
	public function get_label() {
		return esc_html__( 'SmartVideo', 'swarmify' );
	}

	/**
	 * Get search keywords for the Bricks element picker.
	 *
	 * @return string[] Keyword list used to surface this element via search.
	 */
	public function get_keywords() {
		return array( 'video', 'player', 'embed', 'youtube', 'vimeo', 'smartvideo' );
	}

	/**
	 * Define control groups (tabs/sections) for this Bricks element.
	 *
	 * @return void
	 */
	public function set_control_groups() {
		$this->control_groups['source'] = array(
			'title' => esc_html__( 'Video source', 'swarmify' ),
			'tab'   => 'content',
		);

		$this->control_groups['poster'] = array(
			'title' => esc_html__( 'Poster', 'swarmify' ),
			'tab'   => 'content',
		);

		$this->control_groups['dimensions'] = array(
			'title' => esc_html__( 'Dimensions', 'swarmify' ),
			'tab'   => 'content',
		);

		$this->control_groups['playback'] = array(
			'title' => esc_html__( 'Playback', 'swarmify' ),
			'tab'   => 'content',
		);
	}

	/**
	 * Define individual editor controls (fields) for this Bricks element.
	 *
	 * @return void
	 */
	public function set_controls() {

		// -- Video Source --

		$this->controls['video_url'] = array(
			'tab'         => 'content',
			'group'       => 'source',
			'label'       => esc_html__( 'Video URL', 'swarmify' ),
			'type'        => 'text',
			'placeholder' => esc_html__( 'https://www.youtube.com/watch?v=... or any video URL', 'swarmify' ),
			'description' => esc_html__( 'Paste a YouTube, Vimeo, Swarmify, or direct video URL', 'swarmify' ),
		);

		$this->controls['media_library'] = array(
			'tab'   => 'content',
			'group' => 'source',
			'label' => esc_html__( 'Choose from Media Library', 'swarmify' ),
			'type'  => 'video',
		);

		// -- Poster --

		$this->controls['poster_source'] = array(
			'tab'       => 'content',
			'group'     => 'poster',
			'label'     => esc_html__( 'Poster source', 'swarmify' ),
			'type'      => 'select',
			'options'   => array(
				'none'           => esc_html__( 'Automatic', 'swarmify' ),
				'media_library'  => esc_html__( 'Media library', 'swarmify' ),
				'another_source' => esc_html__( 'Other', 'swarmify' ),
			),
			'default'   => 'none',
			'clearable' => false,
		);

		$this->controls['poster_image'] = array(
			'tab'      => 'content',
			'group'    => 'poster',
			'label'    => esc_html__( 'Poster image', 'swarmify' ),
			'type'     => 'image',
			'required' => array( 'poster_source', '=', 'media_library' ),
		);

		$this->controls['poster_url'] = array(
			'tab'         => 'content',
			'group'       => 'poster',
			'label'       => esc_html__( 'Poster URL', 'swarmify' ),
			'type'        => 'text',
			'placeholder' => 'https://example.com/poster.jpg',
			'required'    => array( 'poster_source', '=', 'another_source' ),
		);

		// -- Dimensions --

		$this->controls['aspect_ratio'] = array(
			'tab'       => 'content',
			'group'     => 'dimensions',
			'label'     => esc_html__( 'Aspect Ratio', 'swarmify' ),
			'type'      => 'select',
			'options'   => \Swarmify\Smartvideo\AspectRatio::get_options(),
			'default'   => '16:9',
			'clearable' => false,
		);

		$this->controls['video_width'] = array(
			'tab'      => 'content',
			'group'    => 'dimensions',
			'label'    => esc_html__( 'Width', 'swarmify' ),
			'type'     => 'number',
			'default'  => 1280,
			'required' => array( 'aspect_ratio', '=', 'custom' ),
		);

		$this->controls['video_height'] = array(
			'tab'      => 'content',
			'group'    => 'dimensions',
			'label'    => esc_html__( 'Height', 'swarmify' ),
			'type'     => 'number',
			'default'  => 720,
			'required' => array( 'aspect_ratio', '=', 'custom' ),
		);

		// -- Playback --

		$this->controls['autoplay'] = array(
			'tab'         => 'content',
			'group'       => 'playback',
			'label'       => esc_html__( 'Autoplay', 'swarmify' ),
			'type'        => 'checkbox',
			'default'     => 'on' === get_option( 'swarmify_default_autoplay', 'off' ),
			'description' => esc_html__( "Automatically start playing when the video is visible. Most browsers require 'Muted' to be enabled.", 'swarmify' ),
		);

		$this->controls['muted'] = array(
			'tab'         => 'content',
			'group'       => 'playback',
			'label'       => esc_html__( 'Muted', 'swarmify' ),
			'type'        => 'checkbox',
			'default'     => 'on' === get_option( 'swarmify_default_muted', 'off' ),
			'description' => esc_html__( 'Start playback with audio muted.', 'swarmify' ),
		);

		$this->controls['loop'] = array(
			'tab'         => 'content',
			'group'       => 'playback',
			'label'       => esc_html__( 'Loop', 'swarmify' ),
			'type'        => 'checkbox',
			'default'     => 'on' === get_option( 'swarmify_default_loop', 'off' ),
			'description' => esc_html__( 'Restart the video automatically when it reaches the end.', 'swarmify' ),
		);

		$this->controls['show_controls'] = array(
			'tab'         => 'content',
			'group'       => 'playback',
			'label'       => esc_html__( 'Controls', 'swarmify' ),
			'type'        => 'checkbox',
			'default'     => 'on' === get_option( 'swarmify_default_controls', 'on' ),
			'description' => esc_html__( 'Show player controls (play, pause, volume, etc.).', 'swarmify' ),
		);

		$this->controls['playsinline'] = array(
			'tab'         => 'content',
			'group'       => 'playback',
			'label'       => esc_html__( 'Play inline', 'swarmify' ),
			'type'        => 'checkbox',
			'default'     => 'on' === get_option( 'swarmify_default_playsinline', 'off' ),
			'description' => esc_html__( 'Keep the video inline on iOS instead of opening in fullscreen.', 'swarmify' ),
		);

		$this->controls['responsive'] = array(
			'tab'         => 'content',
			'group'       => 'playback',
			'label'       => esc_html__( 'Responsive', 'swarmify' ),
			'type'        => 'checkbox',
			'default'     => 'on' === get_option( 'swarmify_default_responsive', 'on' ),
			'description' => esc_html__( 'Make the video responsive to fill its container width while maintaining aspect ratio.', 'swarmify' ),
		);
	}

	/**
	 * Render the SmartVideo element on the front end and in the Bricks editor.
	 *
	 * @return void
	 */
	public function render() {
		$settings = $this->settings;

		// Disabled warning — only in Bricks editor, not on the frontend.
		if ( bricks_is_builder() &&
			( 'on' !== get_option( 'swarmify_status' ) || '' === get_option( 'swarmify_cdn_key', '' ) ) ) {
			printf(
				'<div style="background:#fcf0c0;border:1px solid #d4a72c;border-radius:4px;padding:8px 12px;margin-bottom:10px;font-size:13px;color:#6b5900">%s</div>',
				esc_html__( 'SmartVideo is currently disabled. Go to the SmartVideo settings page to enable it.', 'swarmify' )
			);
		}

		// New single URL field (v2.4+).
		$video_url = '';
		if ( ! empty( $settings['video_url'] ) ) {
			$raw_url   = $this->render_dynamic_data( $settings['video_url'] );
			$video_url = \Swarmify\Smartvideo\VideoUrl::normalize( $raw_url );
		}

		// Backward compat: old source-type fields.
		if ( empty( $video_url ) ) {
			$source = isset( $settings['video_source'] ) ? $settings['video_source'] : 'media_library';
			if ( 'media_library' === $source ) {
				$video_url = isset( $settings['media_library']['url'] ) ? $settings['media_library']['url'] : '';
			} elseif ( 'youtube' === $source && ! empty( $settings['youtube_url'] ) ) {
				$video_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['youtube_url'] );
			} elseif ( 'vimeo' === $source && ! empty( $settings['vimeo_url'] ) ) {
				$video_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['vimeo_url'] );
			} elseif ( 'swarmify_url' === $source && ! empty( $settings['swarmify_url'] ) ) {
				$video_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['swarmify_url'] );
			} elseif ( 'another_source' === $source && ! empty( $settings['another_source_url'] ) ) {
				$video_url = \Swarmify\Smartvideo\VideoUrl::normalize( $settings['another_source_url'] );
			}
		}

		// Media library fallback (new modules where user picks from library).
		if ( empty( $video_url ) && ! empty( $settings['media_library']['url'] ) ) {
			$video_url = $settings['media_library']['url'];
		}

		if ( empty( $video_url ) ) {
			// Show the "No video selected" placeholder only in the Bricks
			// builder. Frontend visitors must not see authoring chrome on
			// empty elements.
			if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static self-built markup; the only dynamic value is escaped via esc_html__() in empty_placeholder(), and wp_kses would lowercase the SVG viewBox.
				echo \Swarmify\Smartvideo\AspectRatio::empty_placeholder();
			}
			return;
		}

		// Resolve poster URL.
		$poster_source = isset( $settings['poster_source'] ) ? $settings['poster_source'] : 'none';
		$poster_url    = '';

		if ( 'media_library' === $poster_source && ! empty( $settings['poster_image']['id'] ) ) {
			$size       = ! empty( $settings['poster_image']['size'] ) ? $settings['poster_image']['size'] : 'full';
			$poster_url = wp_get_attachment_image_url( $settings['poster_image']['id'], $size );
		} elseif ( 'another_source' === $poster_source && ! empty( $settings['poster_url'] ) ) {
			$poster_url = $this->render_dynamic_data( $settings['poster_url'] );
		}

		// Dimensions — resolve from aspect ratio preset.
		$aspect_ratio           = isset( $settings['aspect_ratio'] ) ? $settings['aspect_ratio'] : '';
		list( $width, $height ) = \Swarmify\Smartvideo\AspectRatio::resolve(
			$aspect_ratio,
			isset( $settings['video_width'] ) ? $settings['video_width'] : 1280,
			isset( $settings['video_height'] ) ? $settings['video_height'] : 720
		);

		\Swarmify\Smartvideo\SchemaCollector::add( $video_url, $poster_url ?: '' );

		// Build attribute list — empty/disabled attrs are skipped so we never
		// emit double-space runs inside the tag. Canonical order:
		// src, poster, autoplay, muted, loop, controls, playsinline, width, height, class.
		$attrs   = array();
		$attrs[] = 'src="' . esc_url( $video_url, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) ) . '"';
		if ( ! empty( $poster_url ) ) {
			$attrs[] = 'poster="' . esc_url( $poster_url ) . '"';
		}
		if ( ! empty( $settings['autoplay'] ) ) {
			$attrs[] = 'autoplay';
		}
		if ( ! empty( $settings['muted'] ) ) {
			$attrs[] = 'muted';
		}
		if ( ! empty( $settings['loop'] ) ) {
			$attrs[] = 'loop';
		}
		if ( ! empty( $settings['show_controls'] ) ) {
			$attrs[] = 'controls';
		}
		if ( ! empty( $settings['playsinline'] ) ) {
			$attrs[] = 'playsinline';
		}
		$attrs[] = 'width="' . esc_attr( $width ) . '"';
		$attrs[] = 'height="' . esc_attr( $height ) . '"';
		if ( ! empty( $settings['responsive'] ) ) {
			$attrs[] = 'class="swarm-fluid"';
		}

		// Render.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Bricks::render_attributes() output is already escaped by Bricks; wp_kses_post would drop framework data-* attributes.
		echo "<div {$this->render_attributes( '_root' )}>";
		$smartvideo = '<smartvideo ' . implode( ' ', $attrs ) . '></smartvideo>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped at construction (esc_url/esc_attr); the facade wrapper escapes its own markup; <smartvideo> is a custom element wp_kses_post would strip.
		echo \Swarmify\Smartvideo\Facade::wrap(
			$smartvideo,
			array(
				'src'      => $video_url,
				'poster'   => $poster_url ?: '',
				'width'    => $width,
				'height'   => $height,
				'autoplay' => ! empty( $settings['autoplay'] ),
			)
		);
		echo '</div>';
	}
}
