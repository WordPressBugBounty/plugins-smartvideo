<?php
/**
 * SmartVideo::module_classnames()
 *
 * @package Swarmify\Divi5\Modules\SmartVideo
 * @since 2.3.0
 */

namespace Swarmify\Divi5\Modules\SmartVideo\SmartVideoTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

trait ModuleClassnamesTrait {

	/**
	 * Module classnames function for SmartVideo.
	 *
	 * @since 2.3.0
	 *
	 * @param array $args {
	 *     @type object $classnamesInstance Classnames instance.
	 *     @type array  $attrs             Block attributes.
	 * }
	 */
	public static function module_classnames( $args ) {
		// SmartVideo does not add any custom classnames beyond the default.
	}
}
