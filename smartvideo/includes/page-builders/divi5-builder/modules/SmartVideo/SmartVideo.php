<?php
/**
 * Module: SmartVideo class.
 *
 * @package Swarmify\Divi5\Modules\SmartVideo
 * @since 2.3.0
 */

namespace Swarmify\Divi5\Modules\SmartVideo;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * SmartVideo Divi 5 module.
 *
 * Implements DependencyInterface for the Divi 5 dependency tree.
 * Uses traits for render callback, classnames, styles, and script data.
 *
 * @since 2.3.0
 */
class SmartVideo implements DependencyInterface {
	use SmartVideoTrait\RenderCallbackTrait;

	/**
	 * Loads SmartVideo and registers the render callback.
	 *
	 * @since 2.3.0
	 *
	 * @return void
	 */
	public function load() {
		$module_json_folder_path = SMARTVIDEO_DIVI5_JSON_PATH . 'smartvideo/';

		ModuleRegistration::register_module(
			$module_json_folder_path,
			[
				'render_callback' => [ self::class, 'render_callback' ],
			]
		);
	}
}
