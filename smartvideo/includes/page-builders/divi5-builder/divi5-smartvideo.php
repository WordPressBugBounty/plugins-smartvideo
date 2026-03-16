<?php
/**
 * Divi 5 SmartVideo Module Extension.
 *
 * Registers the SmartVideo module for Divi 5's Visual Builder.
 *
 * @package Swarmify\Divi5
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

define( 'SMARTVIDEO_DIVI5_PATH', plugin_dir_path( __FILE__ ) );
define( 'SMARTVIDEO_DIVI5_JSON_PATH', SMARTVIDEO_DIVI5_PATH . 'modules-json/' );

/**
 * Requires Autoloader.
 */
require SMARTVIDEO_DIVI5_PATH . 'vendor/autoload.php';
require SMARTVIDEO_DIVI5_PATH . 'modules/Modules.php';

/**
 * Enqueue Visual Builder scripts and styles.
 *
 * @since 2.3.0
 */
function smartvideo_divi5_enqueue_vb_scripts() {
	if ( et_builder_d5_enabled() && et_core_is_fb_enabled() ) {
		$plugin_dir_url = plugin_dir_url( __FILE__ );

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'    => 'smartvideo-divi5-builder-bundle-script',
				'version' => SWARMIFY_PLUGIN_VERSION,
				'script'  => [
					'src'                => "{$plugin_dir_url}scripts/bundle.js",
					'deps'               => [
						'divi-module-library',
						'divi-vendor-wp-hooks',
					],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
			[
				'name'    => 'smartvideo-divi5-builder-vb-bundle-style',
				'version' => SWARMIFY_PLUGIN_VERSION,
				'style'   => [
					'src'                => "{$plugin_dir_url}styles/vb-bundle.css",
					'deps'               => [],
					'enqueue_top_window' => false,
					'enqueue_app_window' => true,
				],
			]
		);
	}
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'smartvideo_divi5_enqueue_vb_scripts' );

/**
 * Enqueue frontend styles.
 *
 * @since 2.3.0
 */
function smartvideo_divi5_enqueue_frontend_scripts() {
	$plugin_dir_url = plugin_dir_url( __FILE__ );
	wp_enqueue_style(
		'smartvideo-divi5-builder-bundle-style',
		"{$plugin_dir_url}styles/bundle.css",
		array(),
		SWARMIFY_PLUGIN_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'smartvideo_divi5_enqueue_frontend_scripts' );

/**
 * Inject SmartVideo activation status for the Visual Builder.
 *
 * Same data as the D4 module's pass_status_to_builder(), ensuring the
 * VB can check whether SmartVideo is active and show a warning if not.
 *
 * @since 2.3.0
 */
function smartvideo_divi5_pass_status_to_builder() {
	if ( ! et_core_is_fb_enabled() ) {
		return;
	}
	printf(
		'<script>window.smartvideoBlockData = %s;</script>',
		wp_json_encode( array(
			'isActive' => ( 'on' === get_option( 'swarmify_status' ) && '' !== get_option( 'swarmify_cdn_key', '' ) ),
		) )
	);
}
add_action( 'wp_head', 'smartvideo_divi5_pass_status_to_builder', 1 );
