<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register SmartVideo element for Bricks Builder.
 */

// Register the element.
add_action( 'init', function () {
	if ( ! class_exists( '\Bricks\Elements' ) ) {
		return;
	}

	\Bricks\Elements::register_element(
		plugin_dir_path( __FILE__ ) . 'element-smartvideo.php',
		'smartvideo',
		'Smartvideo_Element_Bricks'
	);
}, 11 );

// Inject custom icon CSS for the Bricks panel.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'bricks_is_builder' ) || ! bricks_is_builder() ) {
		return;
	}

	$svg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 101.02 116.18'%3E%3Cpath d='M3 30.66v54.86l47.51 27.43 47.51-27.43V30.66L50.51 3.23zM39.59 76.9V39.27l32.58 18.82z' fill='%23ffde17'/%3E%3Cpath d='M100.27 28.49L51.26.2a1.49 1.49 0 0 0-1.5 0L.75 28.49a1.52 1.52 0 0 0-.75 1.3v56.59a1.51 1.51 0 0 0 .75 1.3l49 28.3a1.51 1.51 0 0 0 1.5 0l49-28.3a1.51 1.51 0 0 0 .75-1.3V29.79a1.52 1.52 0 0 0-.73-1.3zm-2.25 57l-47.51 27.46L3 85.52V30.66L50.51 3.23l47.51 27.43z' fill='currentColor'/%3E%3Cpath fill='currentColor' d='M39.58 76.91l32.59-18.82-32.59-18.81v37.63z'/%3E%3C/svg%3E";

	wp_add_inline_style( 'bricks-frontend', "
		.smartvideo-bricks-icon {
			display: inline-block;
			width: 1.4em;
			height: 1.4em;
			vertical-align: middle;
			background: url(\"$svg\") no-repeat center / contain;
		}
	" );
}, 20 );
