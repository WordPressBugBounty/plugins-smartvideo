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

	$svg = "data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 47 47' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M23.04 0l21 11.52v23.04l-21 11.52-21-11.52V11.52L23.05 0z' fill='%23ffde17'/%3E%3Cpath d='M15.52 13.43c0-2.01 1.32-2.88 2.93-1.93l17.05 9.92c1.62.94 1.62 2.46 0 3.4l-17.05 9.93c-1.61.94-2.93.07-2.93-1.93v-19.4z' fill='%23333'/%3E%3C/svg%3E";

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
