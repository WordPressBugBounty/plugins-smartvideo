<?php

class SmartVideo extends FLBuilderModule {
	public function __construct() {
		parent::__construct(
			array(
				'name'            => __( 'SmartVideo', 'swarmify' ),
				'description'     => __( 'Effortless, unlimited video player', 'swarmify' ),
				// 'group'           => __( 'SmartVideo', 'swarmify' ),
				'category'        => __( 'Basic', 'swarmify' ),
				// 'icon'            => 'format-video.svg',
				'editor_export'   => true, // Defaults to true and can be omitted.
				'enabled'         => true, // Defaults to true and can be omitted.
				'partial_refresh' => false, // Defaults to false and can be omitted.
			)
		);
	}

	/**
	 * Migrate old source-type fields into the new video_url field.
	 * Runs before defaults are merged, so old saved data populates
	 * the new field in both the editor form and frontend render.
	 */
	public function filter_raw_settings_defaults( $settings, $defaults ) {
		if ( ! empty( $settings->video_url ) ) {
			return $settings;
		}

		$video_type = isset( $settings->video_type ) ? $settings->video_type : '';

		switch ( $video_type ) {
			case 'youtube':
				$settings->video_url = isset( $settings->youtube ) ? $settings->youtube : '';
				break;
			case 'vimeo':
				$settings->video_url = isset( $settings->vimeo ) ? $settings->vimeo : '';
				break;
			case 'swarmify_url':
				$settings->video_url = isset( $settings->swarmify_url ) ? $settings->swarmify_url : '';
				break;
			case 'other_source':
				$settings->video_url = isset( $settings->other_source ) ? $settings->other_source : '';
				break;
		}

		return $settings;
	}
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module(
	'SmartVideo',
	array(
		'general'       => array(
			'title'    => __( 'General', 'swarmify' ),
			'sections' => array(
				'general' => array(
					'title'  => '',
					'fields' => array(
						'video_url'       => array(
							'type'        => 'text',
							'label'       => __( 'Video URL', 'swarmify' ),
							'placeholder' => 'https://www.youtube.com/watch?v=... or any video URL',
							'help'        => __( 'Paste a YouTube, Vimeo, Swarmify, or direct video URL', 'swarmify' ),
						),
						'video'           => array(
							'type'        => 'video',
							'label'       => __( 'Choose from Media Library', 'swarmify' ),
							'help'        => __( 'A video in the MP4 format. Most modern browsers support this format.', 'swarmify' ),
							'show_remove' => true,
						),
						'poster'          => array(
							'type'    => 'select',
							'label'   => __( 'Add a poster', 'swarmify' ),
							'options' => array(
								'none'          => __( 'Automatic', 'swarmify' ),
								'media_library' => __( 'Media library', 'swarmify' ),
								'other_source'  => __( 'Other', 'swarmify' ),
							),
							'default' => 'none',
							'toggle'  => array(
								'media_library' => array(
									'fields' => array( 'poster_internal' ),
								),
								'other_source'  => array(
									'fields' => array( 'poster_external' ),
								),
							),
						),
						'poster_internal' => array(
							'type'        => 'photo',
							'show_remove' => true,
							'label'       => _x( 'Poster', 'Video preview/fallback image.', 'swarmify' ),
						),
						'poster_external' => array(
							'type'        => 'text',
							'label'       => 'Poster link',
							'placeholder' => 'https://example.com/poster.jpg',
						),
					),
				),

			),
		),
		'basic_options' => array(
			'title'    => 'Basic options',
			'sections' => array(
				'basic_options' => array(
					'fields' => array(
						'aspect_ratio' => array(
							'type'    => 'select',
							'label'   => __( 'Aspect Ratio', 'swarmify' ),
							'default' => '16:9',
							'options' => \Swarmify\Smartvideo\AspectRatio::get_options(),
							'toggle'  => array(
								'custom' => array(
									'fields' => array( 'width', 'height' ),
								),
							),
						),
						'height'     => array(
							'type'    => 'text',
							'label'   => __( 'Height', 'swarmify' ),
							'default' => '720',
							'class'   => 'height',
						),
						'width'      => array(
							'type'    => 'text',
							'label'   => __( 'Width', 'swarmify' ),
							'default' => '1280',
							'class'   => 'width',
						),
						'autoplay'   => array(
							'type'    => 'select',
							'label'   => __( 'Autoplay', 'swarmify' ),
							'help'    => __( "Automatically start playing when the video is visible. Most browsers require 'Muted' to be enabled.", 'swarmify' ),
							'default' => 'on' === get_option( 'swarmify_default_autoplay', 'off' ) ? '1' : '0',
							'options' => array(
								'0' => __( 'No', 'swarmify' ),
								'1' => __( 'Yes', 'swarmify' ),
							),
							'preview' => array(
								'type' => 'none',
							),
						),
						'muted'      => array(
							'type'    => 'select',
							'label'   => __( 'Muted', 'swarmify' ),
							'help'    => __( 'Start playback with audio muted.', 'swarmify' ),
							'default' => 'on' === get_option( 'swarmify_default_muted', 'off' ) ? '1' : '0',
							'options' => array(
								'0' => __( 'No', 'swarmify' ),
								'1' => __( 'Yes', 'swarmify' ),
							),
							'preview' => array(
								'type' => 'none',
							),
						),
						'loop'       => array(
							'type'    => 'select',
							'label'   => __( 'Loop', 'swarmify' ),
							'help'    => __( 'Restart the video automatically when it reaches the end.', 'swarmify' ),
							'default' => 'on' === get_option( 'swarmify_default_loop', 'off' ) ? '1' : '0',
							'options' => array(
								'0' => __( 'No', 'swarmify' ),
								'1' => __( 'Yes', 'swarmify' ),
							),
							'preview' => array(
								'type' => 'none',
							),
						),
						'controls'   => array(
							'type'    => 'select',
							'label'   => __( 'Controls', 'swarmify' ),
							'help'    => __( 'Show player controls (play, pause, volume, etc.).', 'swarmify' ),
							'default' => 'on' === get_option( 'swarmify_default_controls', 'on' ) ? '1' : '0',
							'options' => array(
								'0' => __( 'No', 'swarmify' ),
								'1' => __( 'Yes', 'swarmify' ),
							),
							'preview' => array(
								'type' => 'none',
							),
						),
					'inline'     => array(
							'type'    => 'select',
							'label'   => __( 'Play inline', 'swarmify' ),
							'help'    => __( 'Keep the video inline on iOS instead of opening in fullscreen.', 'swarmify' ),
							'default' => 'on' === get_option( 'swarmify_default_playsinline', 'off' ) ? '1' : '0',
							'options' => array(
								'0' => __( 'No', 'swarmify' ),
								'1' => __( 'Yes', 'swarmify' ),
							),
							'preview' => array(
								'type' => 'none',
							),
						),
						'responsive' => array(
							'type'    => 'select',
							'label'   => __( 'Responsive', 'swarmify' ),
							'help'    => __( 'Make the video responsive to fill its container width while maintaining aspect ratio.', 'swarmify' ),
							'default' => 'on' === get_option( 'swarmify_default_responsive', 'on' ) ? '1' : '0',
							'options' => array(
								'0' => __( 'No', 'swarmify' ),
								'1' => __( 'Yes', 'swarmify' ),
							),
							'preview' => array(
								'type' => 'none',
							),
						),
					),
				),
			),
		),
	)
);
