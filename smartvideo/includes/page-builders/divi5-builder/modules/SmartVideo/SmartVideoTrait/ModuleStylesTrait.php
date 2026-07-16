<?php
/**
 * SmartVideo::module_styles()
 *
 * @package Swarmify\Divi5\Modules\SmartVideo
 * @since 2.3.0
 */

namespace Swarmify\Divi5\Modules\SmartVideo\SmartVideoTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\FrontEnd\Module\Style;

trait ModuleStylesTrait {

	/**
	 * SmartVideo style components.
	 *
	 * @since 2.3.0
	 *
	 * @param array $args {
	 *     @type string $id       Module ID.
	 *     @type string $name     Module name.
	 *     @type array  $attrs    Module attributes.
	 *     @type object $elements ModuleElements instance.
	 *     @type array  $settings Custom settings.
	 *     @type string $state    Attributes state.
	 *     @type string $mode     Style mode.
	 * }
	 */
	public static function module_styles( $args ) {
		$elements                    = $args['elements'];
		$settings                    = $args['settings'] ?? [];
		$default_printed_style_attrs = $args['defaultPrintedStyleAttrs'] ?? [];

		Style::add(
			[
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => [
					$elements->style(
						[
							'attrName'   => 'module',
							'styleProps' => [
								'defaultPrintedStyleAttrs' => $default_printed_style_attrs['module']['decoration'] ?? [],
								'disabledOn'               => [
									'disabledModuleVisibility' => $settings['disabledModuleVisibility'] ?? null,
								],
							],
						]
					),
				],
			]
		);
	}
}
