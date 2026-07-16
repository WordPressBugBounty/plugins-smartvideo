<?php

class SmartvideoDiviWidget extends ET_Builder_Module {

	public $slug       = 'smartvideo_divi_module';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => '',
		'author'     => 'Swarmify',
		'author_uri' => 'https://swarmify.com',
	);

	/**
	 * Initialize the Divi module by setting its display name.
	 *
	 * @return void
	 */
	public function init() {
		$this->name = esc_html__( 'SmartVideo', 'swarmify' );
	}

	/**
	 * Configure the Divi advanced fields panel for this module.
	 *
	 * @return array<string, mixed> Advanced field configuration consumed by Divi.
	 */
	public function get_advanced_fields_config() {
		return array(
			'background'   => array(
				'css'                  => array(
					'important' => false,
				),
				'use_background_video' => false,
			),
			'button'       => false,
			'fonts'        => false,
			'link_options' => false,
			'text'         => false,
			'text_shadow'  => false,
		);
	}

	/**
	 * Define the editor fields exposed by this Divi module.
	 *
	 * @return array<string, mixed> Divi field definitions.
	 */
	public function get_fields() {
		return array(
			'video_src'       => array(
				'label'            => esc_html__( 'Video source', 'swarmify' ),
				'description'      => esc_html__( 'Select `Another source` if your video is hosted somewhere else (like Amazon S3, Google Drive, Dropbox, etc.), paste the URL ending in ".mp4"', 'swarmify' ),
				'type'             => 'select',
				'options'          => array(
					'media_library'  => esc_html__( 'Media Library', 'swarmify' ),
					'youtube'        => esc_html__( 'Youtube', 'swarmify' ),
					'vimeo'          => esc_html__( 'Vimeo', 'swarmify' ),
					'another_source' => esc_html__( 'Another source', 'swarmify' ),
				),
				'default'          => 'media_library',
				'default_on_front' => 'media_library',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'source',
			),

			'media_library'   => array(
				'label'              => esc_html__( 'Video File', 'swarmify' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'data_type'          => 'video',
				'upload_button_text' => esc_attr__( 'Upload a video', 'swarmify' ),
				'choose_text'        => esc_attr__( 'Choose a Video File', 'swarmify' ),
				'update_text'        => esc_attr__( 'Set As Video', 'swarmify' ),
				'description'        => esc_html__( 'Upload the .WEBM version of your video here. All uploaded videos should be in both .MP4 .WEBM formats to ensure maximum compatibility in all browsers.', 'swarmify' ),
				'default'            => 'https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4',
				'computed_affects'   => array(
					'__video',
				),
				'show_if'            => array(
					'video_src' => 'media_library',
				),
				'toggle_slug'        => 'smartvideo',
				'sub_toggle'         => 'source',
			),

			'youtube'         => array(
				'label'           => esc_html__( 'Youtube link', 'swarmify' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'show_if'         => array(
					'video_src' => 'youtube',
				),
				'toggle_slug'     => 'smartvideo',
				'sub_toggle'      => 'source',
			),

			'vimeo'           => array(
				'label'           => esc_html__( 'Vimeo link', 'swarmify' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'show_if'         => array(
					'video_src' => 'vimeo',
				),
				'toggle_slug'     => 'smartvideo',
				'sub_toggle'      => 'source',
			),

			'another_source'  => array(
				'label'           => esc_html__( 'Video URL', 'swarmify' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Input the destination URL for your video.', 'swarmify' ),
				'show_if'         => array(
					'video_src' => 'another_source',
				),
				'toggle_slug'     => 'smartvideo',
				'sub_toggle'      => 'source',
			),

			// Not a video_src select option — hidden in the VB, kept only for old shortcode content.
			'swarmify_url'    => array(
				'label'           => esc_html__( 'Video URL', 'swarmify' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'show_if'         => array(
					'video_src' => 'swarmify_url',
				),
				'toggle_slug'     => 'smartvideo',
				'sub_toggle'      => 'source',
			),

			'poster_src'      => array(
				'label'            => esc_html__( 'Poster source', 'swarmify' ),
				'type'             => 'select',
				'options'          => array(
					'media_library'  => esc_html__( 'Media Library', 'swarmify' ),
					'another_source' => esc_html__( 'Another source', 'swarmify' ),
					'none'           => esc_html__( 'None', 'swarmify' ),
				),
				'default'          => 'none',
				'default_on_front' => 'none',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'source',
			),

			'internal_poster' => array(
				'label'              => esc_html__( 'Poster image', 'swarmify' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'data_type'          => 'image',
				'upload_button_text' => esc_attr__( 'Upload an image', 'swarmify' ),
				'choose_text'        => esc_attr__( 'Choose an image file', 'swarmify' ),
				'update_text'        => esc_attr__( 'Set as poster image', 'swarmify' ),
				'show_if'            => array(
					'poster_src' => 'media_library',
				),
				'toggle_slug'        => 'smartvideo',
				'sub_toggle'         => 'source',
			),

			'external_poster' => array(
				'label'           => esc_html__( 'Poster link', 'swarmify' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'show_if'         => array(
					'poster_src' => 'another_source',
				),
				'toggle_slug'     => 'smartvideo',
				'sub_toggle'      => 'source',
			),

			// basic options
			'video_height'    => array(
				'label'           => esc_html__( 'Height', 'swarmify' ),
				'type'            => 'range',
				'default'         => '720',
				'unitless'        => true,
				'range_settings'  => array(
					'min'  => '0',
					'max'  => '900',
					'step' => '1',
				),
				'option_category' => 'basic_option',
				'toggle_slug'     => 'smartvideo',
				'sub_toggle'      => 'basic',
			),

			'video_width'     => array(
				'label'           => esc_html__( 'Width', 'swarmify' ),
				'type'            => 'range',
				'default'         => '1280',
				'unitless'        => true,
				'range_settings'  => array(
					'min'  => '0',
					'max'  => '1920',
					'step' => '1',
				),
				'option_category' => 'basic_option',
				'toggle_slug'     => 'smartvideo',
				'sub_toggle'      => 'basic',
			),
			'autoplay'        => array(
				'label'            => esc_html__( 'Autoplay', 'swarmify' ),
				'type'             => 'yes_no_button',
				'options'          => array(
					'off' => esc_html__( 'No', 'swarmify' ),
					'on'  => esc_html__( 'Yes', 'swarmify' ),
				),
				'default_on_front' => 'off',
				'depends_show_if'  => 'on',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'basic',
			),
			'muted'           => array(
				'label'            => esc_html__( 'Muted', 'swarmify' ),
				'type'             => 'yes_no_button',
				'options'          => array(
					'off' => esc_html__( 'No', 'swarmify' ),
					'on'  => esc_html__( 'Yes', 'swarmify' ),
				),
				'default_on_front' => 'off',
				'depends_show_if'  => 'on',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'basic',
			),
			'loop'            => array(
				'label'            => esc_html__( 'Loop', 'swarmify' ),
				'type'             => 'yes_no_button',
				'options'          => array(
					'off' => esc_html__( 'No', 'swarmify' ),
					'on'  => esc_html__( 'Yes', 'swarmify' ),
				),
				'default_on_front' => 'off',
				'depends_show_if'  => 'on',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'basic',
			),

			// advanced options
			'controls'        => array(
				'label'            => esc_html__( 'Controls', 'swarmify' ),
				'type'             => 'yes_no_button',
				'options'          => array(
					'off' => esc_html__( 'No', 'swarmify' ),
					'on'  => esc_html__( 'Yes', 'swarmify' ),
				),
				'default_on_front' => 'on',
				'depends_show_if'  => 'on',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'advanced',
			),
			'playsinline'     => array(
				'label'            => esc_html__( 'Play inline', 'swarmify' ),
				'type'             => 'yes_no_button',
				'options'          => array(
					'off' => esc_html__( 'No', 'swarmify' ),
					'on'  => esc_html__( 'Yes', 'swarmify' ),
				),
				'default_on_front' => 'off',
				'depends_show_if'  => 'on',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'advanced',
			),
			'responsive'      => array(
				'label'            => esc_html__( 'Responsive', 'swarmify' ),
				'type'             => 'yes_no_button',
				'options'          => array(
					'off' => esc_html__( 'No', 'swarmify' ),
					'on'  => esc_html__( 'Yes', 'swarmify' ),
				),
				'default_on_front' => 'on',
				'depends_show_if'  => 'on',
				'toggle_slug'      => 'smartvideo',
				'sub_toggle'       => 'advanced',
			),
		);
	}

	/**
	 * Define the settings-modal toggles (tabs) for this Divi module.
	 *
	 * @return array<string, mixed> Toggle configuration for the Divi settings modal.
	 */
	public function get_settings_modal_toggles() {
		return array(
			'advanced' => array(
				'toggles' => array(
					'smartvideo' => array(
						'priority'          => 24,
						'sub_toggles'       => array(
							'source'   => array(
								'name' => __( 'Source', 'swarmify' ),
							),
							'basic'    => array(
								'name' => __( 'Basic', 'swarmify' ),
							),
							'advanced' => array(
								'name' => __( 'Advanced', 'swarmify' ),
							),
						),
						'tabbed_subtoggles' => true,
						'title'             => __( 'SmartVideo settings', 'swarmify' ),
					),
				),
			),
		);
	}

	/**
	 * Render the SmartVideo Divi module to a <smartvideo> HTML string.
	 *
	 * @param  array       $attrs       Module attributes from the builder.
	 * @param  string|null $content     Inner content (unused).
	 * @param  string|null $render_slug Render slug (unused).
	 * @return string|void Rendered markup, or nothing when no video URL is configured.
	 */
	public function render( $attrs, $content = null, $render_slug = null ) {
		// Resolve video URL from the selected source type
		$swarmify_url = '';
		if ( 'media_library' === $this->props['video_src'] && $this->props['media_library'] ) {
			$swarmify_url = $this->props['media_library'];
		} elseif ( 'youtube' === $this->props['video_src'] && $this->props['youtube'] ) {
			$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $this->props['youtube'] );
		} elseif ( 'vimeo' === $this->props['video_src'] && $this->props['vimeo'] ) {
			$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $this->props['vimeo'] );
		} elseif ( 'another_source' === $this->props['video_src'] && $this->props['another_source'] ) {
			$swarmify_url = \Swarmify\Smartvideo\VideoUrl::normalize( $this->props['another_source'] );
		} elseif ( 'swarmify_url' === $this->props['video_src'] && $this->props['swarmify_url'] ) {
			$swarmify_url = $this->props['swarmify_url'];
		}

		if ( empty( $swarmify_url ) ) {
			return;
		}

		$poster_url = 'media_library' === $this->props['poster_src'] ? $this->props['internal_poster'] : $this->props['external_poster'];
		$has_poster = 'none' !== $this->props['poster_src'] && ! empty( $poster_url );
		$width      = absint( $this->props['video_width'] );
		$height     = absint( $this->props['video_height'] );

		$schema_poster = 'none' !== $this->props['poster_src'] ? $poster_url : '';
		\Swarmify\Smartvideo\SchemaCollector::add( $swarmify_url, $schema_poster );

		// Build attribute list — empty/disabled attrs are skipped so we never
		// emit double-space runs inside the tag. Canonical order:
		// src, poster, autoplay, muted, loop, controls, playsinline, width, height, class.
		$attrs   = array();
		$attrs[] = 'src="' . esc_url( $swarmify_url, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) ) . '"';
		if ( $has_poster ) {
			$attrs[] = 'poster="' . esc_url( $poster_url ) . '"';
		}
		if ( 'on' === $this->props['autoplay'] ) {
			$attrs[] = 'autoplay';
		}
		if ( 'on' === $this->props['muted'] ) {
			$attrs[] = 'muted';
		}
		if ( 'on' === $this->props['loop'] ) {
			$attrs[] = 'loop';
		}
		if ( 'on' === $this->props['controls'] ) {
			$attrs[] = 'controls';
		}
		if ( 'on' === $this->props['playsinline'] ) {
			$attrs[] = 'playsinline';
		}
		$attrs[] = 'width="' . esc_attr( $width ) . '"';
		$attrs[] = 'height="' . esc_attr( $height ) . '"';
		if ( 'on' === $this->props['responsive'] ) {
			$attrs[] = 'class="swarm-fluid"';
		}

		return '<smartvideo ' . implode( ' ', $attrs ) . '></smartvideo>';
	}
}

new SmartvideoDiviWidget();
