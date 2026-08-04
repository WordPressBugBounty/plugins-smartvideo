<?php
/**
 * SmartVideo Gutenberg Block
 *
 * Registers the SmartVideo block for the Gutenberg editor.
 * Built with @wordpress/scripts (output in /build/).
 *
 * @since 1.0.0
 * @package SmartVideo
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the SmartVideo Gutenberg block.
 */
function smartvideo_register_gutenberg_block() {
	$script_asset_path = dirname( SMARTVIDEO_PLUGIN_FILE ) . '/build/gutenberg-block.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			'version'      => SWARMIFY_PLUGIN_VERSION,
		);

	// Register block editor script.
	wp_register_script(
		'smartvideo-gutenberg-block',
		plugins_url( '/build/gutenberg-block.js', SMARTVIDEO_PLUGIN_FILE ),
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	// Register block frontend + backend styles.
	wp_register_style(
		'smartvideo-gutenberg-block-style',
		plugins_url( '/build/style-gutenberg-block.css', SMARTVIDEO_PLUGIN_FILE ),
		array(),
		SWARMIFY_PLUGIN_VERSION
	);

	// Pass plugin status and global defaults to the block editor.
	wp_localize_script(
		'smartvideo-gutenberg-block',
		'smartvideoBlockData',
		array(
			'isActive' => ( 'on' === get_option( 'swarmify_status' ) && '' !== get_option( 'swarmify_cdn_key', '' ) ),
			'defaults' => array(
				'autoplay'    => 'on' === get_option( 'swarmify_default_autoplay', 'off' ),
				'muted'       => 'on' === get_option( 'swarmify_default_muted', 'off' ),
				'loop'        => 'on' === get_option( 'swarmify_default_loop', 'off' ),
				'controls'    => 'on' === get_option( 'swarmify_default_controls', 'on' ),
				'playsInline' => 'on' === get_option( 'swarmify_default_playsinline', 'off' ),
				'responsive'  => 'on' === get_option( 'swarmify_default_responsive', 'on' ),
			),
		)
	);

	// Localized, not fetched from REST — the settings endpoint requires
	// manage_options, which Author-role block editors don't have.
	add_action(
		'enqueue_block_editor_assets',
		function () {
			$editor_settings = new \Swarmify\Smartvideo\Settings( 'smartvideo', SWARMIFY_PLUGIN_VERSION );
			wp_localize_script(
				'smartvideo-gutenberg-block',
				'smartvideoEditorData',
				array(
					'accountTier'  => ( new \Swarmify\Smartvideo\AccountTier( $editor_settings ) )->get(),
					'legacyPlayer' => 'on' === $editor_settings->get( 'swarmify_toggle_legacy_player' ),
					'cdnKey'       => get_option( 'swarmify_cdn_key', '' ),
				)
			);
		}
	);

	// Enable JS translation loading for the block editor script.
	wp_set_script_translations( 'smartvideo-gutenberg-block', 'swarmify' );

	// Register the block type. The `style` handle applies in both editor and
	// frontend; we don't ship a separate editor stylesheet (build/style-* and
	// build/gutenberg-block.css are byte-identical, so registering both costs
	// an extra HTTP request for no visual difference).
	register_block_type(
		'smartvideo/block-smartvideo-guten',
		array(
			'style'         => 'smartvideo-gutenberg-block-style',
			'editor_script' => 'smartvideo-gutenberg-block',
		)
	);

	// Register the per-page disable sidebar panel.
	$sidebar_asset_path = dirname( SMARTVIDEO_PLUGIN_FILE ) . '/build/gutenberg-sidebar.asset.php';
	$sidebar_asset      = file_exists( $sidebar_asset_path )
		? require $sidebar_asset_path
		: array(
			'dependencies' => array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n' ),
			'version'      => SWARMIFY_PLUGIN_VERSION,
		);

	wp_register_script(
		'smartvideo-gutenberg-sidebar',
		plugins_url( '/build/gutenberg-sidebar.js', SMARTVIDEO_PLUGIN_FILE ),
		$sidebar_asset['dependencies'],
		$sidebar_asset['version'],
		true
	);

	// Enable JS translation loading for the sidebar script.
	wp_set_script_translations( 'smartvideo-gutenberg-sidebar', 'swarmify' );

	// Enqueue in the editor only.
	add_action( 'enqueue_block_editor_assets', function () {
		wp_enqueue_script( 'smartvideo-gutenberg-sidebar' );
	} );
}

add_action( 'init', 'smartvideo_register_gutenberg_block' );

/**
 * Register SmartVideo block patterns.
 */
function smartvideo_register_block_patterns() {
	register_block_pattern_category(
		'smartvideo',
		array( 'label' => __( 'SmartVideo', 'swarmify' ) )
	);

	// Pattern 1: Hero Video — full-width, autoplay, muted, loop, no controls.
	register_block_pattern(
		'smartvideo/hero-video',
		array(
			'title'       => __( 'Hero Video', 'swarmify' ),
			'description' => __( 'Full-width background-style video with autoplay, muted, and loop.', 'swarmify' ),
			'categories'  => array( 'smartvideo' ),
			'content'     => '<!-- wp:smartvideo/block-smartvideo-guten {"autoplay":true,"muted":true,"loop":true,"controls":false,"responsive":true,"align":"full"} -->
<div class="wp-block-smartvideo-block-smartvideo-guten alignfull"><smartvideo src="https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4" width="1280" height="720" class="swarm-fluid" autoplay muted loop></smartvideo></div>
<!-- /wp:smartvideo/block-smartvideo-guten -->',
		)
	);

	// Pattern 2: Video with Description — two columns.
	register_block_pattern(
		'smartvideo/video-with-description',
		array(
			'title'       => __( 'Video with Description', 'swarmify' ),
			'description' => __( 'Two-column layout with video on the left and text on the right.', 'swarmify' ),
			'categories'  => array( 'smartvideo' ),
			'content'     => '<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"60%"} -->
<div class="wp-block-column" style="flex-basis:60%"><!-- wp:smartvideo/block-smartvideo-guten {"controls":true,"responsive":true} -->
<div class="wp-block-smartvideo-block-smartvideo-guten"><smartvideo src="https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4" width="1280" height="720" class="swarm-fluid" controls></smartvideo></div>
<!-- /wp:smartvideo/block-smartvideo-guten --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">' . esc_html__( 'Video Title', 'swarmify' ) . '</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Add a description of your video here. Explain what viewers will learn or see.', 'swarmify' ) . '</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',
		)
	);

	// Pattern 3: Video Showcase — heading + video + description.
	register_block_pattern(
		'smartvideo/video-showcase',
		array(
			'title'       => __( 'Video Showcase', 'swarmify' ),
			'description' => __( 'Centered video with a heading above and description below.', 'swarmify' ),
			'categories'  => array( 'smartvideo' ),
			'content'     => '<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__( 'Watch Our Video', 'swarmify' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:smartvideo/block-smartvideo-guten {"controls":true,"responsive":true,"align":"wide"} -->
<div class="wp-block-smartvideo-block-smartvideo-guten alignwide"><smartvideo src="https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4" width="1280" height="720" class="swarm-fluid" controls></smartvideo></div>
<!-- /wp:smartvideo/block-smartvideo-guten -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">' . esc_html__( 'A brief description of the video content goes here.', 'swarmify' ) . '</p>
<!-- /wp:paragraph -->',
		)
	);
}

add_action( 'init', 'smartvideo_register_block_patterns' );
