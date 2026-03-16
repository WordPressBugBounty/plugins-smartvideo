<?php
/**
 * Register all modules with dependency tree.
 *
 * @package Swarmify\Divi5\Modules
 * @since 2.3.0
 */

namespace Swarmify\Divi5\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// Dependency tree registration is handled by the main SmartVideo.php plugin
// file (smartvideo_load_divi5_builder) so the files are loaded early enough
// for D5's module registry. This file is kept as the autoloader entry point.
