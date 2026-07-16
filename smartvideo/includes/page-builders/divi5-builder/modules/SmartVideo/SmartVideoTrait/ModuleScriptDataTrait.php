<?php
/**
 * SmartVideo::module_script_data()
 *
 * @package Swarmify\Divi5\Modules\SmartVideo
 * @since 2.3.0
 */

namespace Swarmify\Divi5\Modules\SmartVideo\SmartVideoTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\Module\Options\Element\ElementScriptData;

trait ModuleScriptDataTrait {

	/**
	 * Set script data of used module options.
	 *
	 * @since 2.3.0
	 *
	 * @param array $args {
	 *     @type string $id       Module id.
	 *     @type string $selector Module selector.
	 *     @type array  $attrs    Module attributes.
	 * }
	 */
	public static function module_script_data( $args ) {
		$id             = $args['id'] ?? '';
		$selector       = $args['selector'] ?? '';
		$attrs          = $args['attrs'] ?? [];
		$store_instance = $args['storeInstance'] ?? null;

		$module_decoration_attrs = $attrs['module']['decoration'] ?? [];

		ElementScriptData::set(
			[
				'id'            => $id,
				'selector'      => $selector,
				'attrs'         => $module_decoration_attrs,
				'storeInstance' => $store_instance,
			]
		);
	}
}
