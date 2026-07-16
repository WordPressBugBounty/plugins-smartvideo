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

if ( ! defined( 'SMARTVIDEO_DIVI5_PATH' ) ) {
	define( 'SMARTVIDEO_DIVI5_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SMARTVIDEO_DIVI5_JSON_PATH' ) ) {
	define( 'SMARTVIDEO_DIVI5_JSON_PATH', SMARTVIDEO_DIVI5_PATH . 'modules-json/' );
}

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
 * Register frontend assets.
 *
 * @since 2.3.0
 */
function smartvideo_divi5_register_frontend_assets() {
	$plugin_dir_url = plugin_dir_url( __FILE__ );
	wp_register_style(
		'smartvideo-divi5-builder-bundle-style',
		"{$plugin_dir_url}styles/bundle.css",
		array(),
		SWARMIFY_PLUGIN_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'smartvideo_divi5_register_frontend_assets' );

/**
 * Inject SmartVideo activation status + module metadata for the Visual Builder.
 *
 * The activation status (smartvideoBlockData) is the same data as the D4
 * module's pass_status_to_builder(), ensuring the VB can check whether
 * SmartVideo is active and show a warning if not.
 *
 * The metadata (smartvideoDivi5Metadata) is the module.json contents, injected
 * at runtime so it doesn't bloat the JS bundle. Reads the JSON once from disk.
 *
 * @since 2.3.0
 */
function smartvideo_divi5_pass_status_to_builder() {
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
	$json_dir   = SMARTVIDEO_DIVI5_JSON_PATH . 'smartvideo/';
	$json_files = array(
		'smartvideoDivi5Metadata'          => 'module.json',
		'smartvideoDivi5Placeholder'       => 'placeholder-content.json',
		'smartvideoDivi5ConversionOutline' => 'conversion-outline.json',
	);
	foreach ( $json_files as $global => $file ) {
		$path = $json_dir . $file;
		if ( file_exists( $path ) ) {
			$raw     = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$decoded = json_decode( $raw );
			if ( null !== $decoded ) {
				printf(
					'<script>window.%s=%s;</script>',
					esc_js( $global ),
					wp_json_encode( $decoded, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
				);
			}
		}
	}
}
add_action( 'wp_head', 'smartvideo_divi5_pass_status_to_builder', 1 );
