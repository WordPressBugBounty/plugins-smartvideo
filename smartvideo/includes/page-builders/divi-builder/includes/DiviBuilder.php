<?php

class SMARTVIDEO_DiviBuilder extends DiviExtension {

	/**
	 * The gettext domain for the extension's translations.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $gettext_domain = 'swarmify';

	/**
	 * The extension's WP Plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'divi-builder';

	/**
	 * The extension's version
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * SMARTVIDEO_DiviBuilder constructor.
	 *
	 * @param string $name
	 * @param array  $args
	 */
	public function __construct( $name = 'divi-builder', $args = array() ) {
		$this->plugin_dir     = plugin_dir_path( __FILE__ );
		$this->plugin_dir_url = plugin_dir_url( $this->plugin_dir );

		parent::__construct( $name, $args );

		add_action( 'wp_head', array( $this, 'pass_status_to_builder' ), 1 );
	}

	/**
	 * Print SmartVideo status data into <head> so the Divi builder JS can read it.
	 *
	 * @return void
	 */
	public function pass_status_to_builder() {
		if ( ! et_core_is_fb_enabled() ) {
			return;
		}
		printf(
			'<script>window.smartvideoBlockData = Object.assign(window.smartvideoBlockData || {}, %s);</script>',
			wp_json_encode(
				array(
					'isActive' => ( 'on' === get_option( 'swarmify_status' ) && '' !== get_option( 'swarmify_cdn_key', '' ) ),
				),
				JSON_HEX_TAG
			)
		);
	}
}

new SMARTVIDEO_DiviBuilder();
