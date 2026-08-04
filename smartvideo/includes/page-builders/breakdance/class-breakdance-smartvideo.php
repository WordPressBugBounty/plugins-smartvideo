<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Breakdance binds its own element loader to breakdance_loaded at plugin-include
// time, so anything later than priority 10 registers after the element glob has
// already run.
add_action(
	'breakdance_loaded',
	function () {
		\Breakdance\ElementStudio\registerSaveLocation(
			\Breakdance\Util\getDirectoryPathRelativeToPluginFolder( __DIR__ ) . '/elements',
			'Swarmify\\Breakdance',
			'element',
			'SmartVideo',
			false,
			// Element Studio's Save button writes element.php/html.twig back into
			// whichever save location it was opened from — ours is plugin source.
			true
		);
	},
	5
);
