<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * Assets enqueued:
 * 1. blocks.style.build.css - Frontend + Backend.
 * 2. blocks.build.js - Backend.
 * 3. blocks.editor.build.css - Backend.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-block-editor} for WP editor styles.
 * @since 1.0.0
 */
function smartvideo_guten_cgb_block_assets() { // phpcs:ignore
	// Register block styles for both frontend + backend.
	wp_register_style(
		'smartvideo_guten-cgb-style-css', // Handle.
		plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), // Block style CSS.
		array(), // Dependency to include the CSS after it.
		'2.1.0'
	);

	// Register block editor script for backend.
	wp_register_script(
		'smartvideo_guten-cgb-block-js', // Handle.
		plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
		array( 'wp-block-editor', 'wp-blocks', 'wp-element', 'wp-i18n' ), // Dependencies, defined above.
		'2.1.0',
		true // Enqueue the script in the footer.
	);

	// Register block editor styles for backend.
	wp_register_style(
		'smartvideo_guten-cgb-block-editor-css', // Handle.
		plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), // Block editor CSS.
		array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
		'2.1.0'
	);

	// WP Localized globals. Use dynamic PHP stuff in JavaScript via `cgbGlobal` object.
	wp_localize_script(
		'smartvideo_guten-cgb-block-js',
		'cgbGlobal', // Array containing dynamic data for a JS Global.
		array(
			'pluginDirPath' => plugin_dir_path( __DIR__ ),
			'pluginDirUrl'  => plugin_dir_url( __DIR__ ),
			// Add more data here that you want to access from `cgbGlobal` object.
		)
	);

	/**
	 * Register Gutenberg block on server-side.
	 *
	 * Register the block on server-side to ensure that the block
	 * scripts and styles for both frontend and backend are
	 * enqueued when the editor loads.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type#enqueuing-block-scripts
	 * @since 1.16.0
	 */
	register_block_type(
		'cgb/block-smartvideo-guten',
		array(
			// Enqueue blocks.style.build.css on both frontend & backend.
			'style'         => 'smartvideo_guten-cgb-style-css',
			// Enqueue blocks.build.js in the editor only.
			'editor_script' => 'smartvideo_guten-cgb-block-js',
			// Enqueue blocks.editor.build.css in the editor only.
			'editor_style'  => 'smartvideo_guten-cgb-block-editor-css',
		)
	);
}

// Only install blocks when version is new enough for Gutenberg
if ( function_exists( 'register_block_type' ) ) {
	// Hook: Block assets.
	add_action( 'init', 'smartvideo_guten_cgb_block_assets' );
}
