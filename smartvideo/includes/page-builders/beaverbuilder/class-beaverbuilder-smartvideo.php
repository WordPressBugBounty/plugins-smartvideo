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
						'video_type'      => array(
							'type'    => 'select',
							'label'   => __( 'Video source', 'swarmify' ),
							'default' => 'media_library',
							'options' => array(
								'media_library' => __( 'Media library', 'swarmify' ),
								'youtube'       => __( 'YouTube', 'swarmify' ),
								'vimeo'         => __( 'Vimeo', 'swarmify' ),
								'other_source'  => __( 'Other source', 'swarmify' ),
							),
							'toggle'  => array(
								'media_library' => array(
									'fields' => array( 'video' ),
								),
								'youtube'       => array(
									'fields' => array( 'youtube' ),
								),
								'vimeo'         => array(
									'fields' => array( 'vimeo' ),
								),
								'other_source'  => array(
									'fields' => array( 'other_source' ),
								),
							),
						),
						'video'           => array(
							'type'        => 'video',
							'label'       => __( 'Video (MP4)', 'swarmify' ),
							'help'        => __( 'A video in the MP4 format. Most modern browsers support this format.', 'swarmify' ),
							'show_remove' => true,
						),
						'youtube'         => array(
							'type'          => 'link',
							'label'         => 'YouTube link',
							'show_target'   => false,
							'show_nofollow' => false,
						),
						'vimeo'           => array(
							'type'          => 'link',
							'label'         => 'Vimeo link',
							'show_target'   => false,
							'show_nofollow' => false,
						),
						'other_source'    => array(
							'type'          => 'link',
							'label'         => 'Video link',
							'show_target'   => false,
							'show_nofollow' => false,
						),
						'poster'          => array(
							'type'    => 'select',
							'label'   => __( 'Add a poster', 'swarmify' ),
							'options' => array(
								'media_library' => __( 'Media library', 'swarmify' ),
								'other_source'  => __( 'Other source', 'swarmify' ),
								'none'          => __( 'None', 'swarmify' ),
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
							'type'          => 'link',
							'label'         => 'Poster link',
							'show_target'   => false,
							'show_nofollow' => false,
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
							'default' => '0',
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
							'default' => '0',
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
							'default' => '0',
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
							'default' => '1',
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
							'label'   => __( 'Play video inline', 'swarmify' ),
							'default' => '0',
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
							'default' => '1',
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
