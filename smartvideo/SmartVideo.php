<?php
/**
 * Plugin Name: SmartVideo
 * Description: SmartVideo makes building a beautiful, professional video experience for your site effortless.
 * Version: 2.4.2
 * Requires at least: 6.6
 * Requires PHP: 7.3
 * Author: Swarmify
 * Author URI: https://swarmify.com/?smartvideo_wordpress_plugin
 * Developer: James Christensen
 * Developer URI: https://profiles.wordpress.org/chris10sen/
 * Text Domain: swarmify
 * Domain Path: /languages
 *
 * License: AGPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 *
 * @package SmartVideo
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SMARTVIDEO_PLUGIN_FILE' ) ) {
	define( 'SMARTVIDEO_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SWARMIFY_PLUGIN_VERSION' ) ) {
	define( 'SWARMIFY_PLUGIN_VERSION', '2.4.2' );
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use Swarmify\Smartvideo;


// phpcs:disable WordPress.Files.FileName


if ( ! function_exists( 'activate_smartvideo' ) ) {
	function activate_smartvideo() {
		Smartvideo\Activator::activate();
	}
}

register_activation_hook( __FILE__, 'activate_smartvideo' );

if ( ! function_exists( 'deactivate_smartvideo' ) ) {
	function deactivate_smartvideo() {
		delete_transient( 'smartvideo_activation_redirect_' . get_current_user_id() );
	}
}

register_deactivation_hook( __FILE__, 'deactivate_smartvideo' );


if ( ! class_exists( 'SmartVideo_Bootstrap' ) ) {
	class SmartVideo_Bootstrap {
		/**
		 * @var \SmartVideo_Bootstrap single instance of this class.
		 */
		private static $instance;

		public function __construct() {
			new Smartvideo\Swarmify( 'SmartVideo' );
		}

		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'swarmify' ), esc_html( SWARMIFY_PLUGIN_VERSION ) );
		}

		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'swarmify' ), esc_html( SWARMIFY_PLUGIN_VERSION ) );
		}

		/**
		 * @return \SmartVideo_Bootstrap
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
}


if ( ! function_exists( 'smartvideo_load_elementor' ) ) {
	function smartvideo_load_elementor() {
		if ( did_action( 'elementor/loaded' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/elementor/class-elementor-swarmify.php';
		}
	}
	add_action( 'init', 'smartvideo_load_elementor' );
}

if ( ! function_exists( 'smartvideo_load_gutenberg' ) ) {
	function smartvideo_load_gutenberg() {
		if ( function_exists( 'register_block_type' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/gutenberg/src/init.php';
		}
	}
	add_action( 'init', 'smartvideo_load_gutenberg', 5 );
}

if ( ! function_exists( 'smartvideo_load_beaver_builder' ) ) {
	function smartvideo_load_beaver_builder() {
		if ( class_exists( 'FLBuilder' ) ) {
			require plugin_dir_path( __FILE__ ) . 'includes/page-builders/beaverbuilder/class-beaverbuilder-smartvideo.php';
		}
	}
	add_action( 'init', 'smartvideo_load_beaver_builder' );
}

// Divi 5 modules must be registered from the dependency-tree action —
// divi_extensions_init fires too late for D5's module registry.
if ( ! function_exists( 'smartvideo_load_divi5_builder' ) ) {
	/**
	 * @param object $dependency_tree The dependency tree.
	 */
	function smartvideo_load_divi5_builder( $dependency_tree ) {
		if ( ! function_exists( 'et_builder_d5_enabled' ) || ! et_builder_d5_enabled() ) {
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/divi5-builder/divi5-smartvideo.php';
		$dependency_tree->add_dependency(
			new \Swarmify\Divi5\Modules\SmartVideo\SmartVideo()
		);
	}
	add_action( 'divi_module_library_modules_dependency_tree', 'smartvideo_load_divi5_builder', 10 );

	// Load again on divi_extensions_init so the Visual Builder's script/style enqueues get registered.
	add_action(
		'divi_extensions_init',
		function () {
			if ( function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled() ) {
				if ( ! defined( 'SMARTVIDEO_DIVI5_PATH' ) ) {
					require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/divi5-builder/divi5-smartvideo.php';
				}
			}
		},
		5
	);
}

if ( ! function_exists( 'smartvideo_load_divi_builder' ) ) {
	function smartvideo_load_divi_builder() {
		if ( function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled() ) {
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/divi-builder/includes/DiviBuilder.php';
	}
	add_action( 'divi_extensions_init', 'smartvideo_load_divi_builder' );
}

if ( ! function_exists( 'smartvideo_load_bricks' ) ) {
	function smartvideo_load_bricks() {
		if ( defined( 'BRICKS_VERSION' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/bricks/class-bricks-smartvideo.php';
		}
	}
	add_action( 'init', 'smartvideo_load_bricks', 9 );
}

// before_breakdance_loaded, not init: Breakdance globs its element save
// locations during breakdance_loaded, which fires on plugins_loaded.
if ( ! function_exists( 'smartvideo_load_breakdance' ) ) {
	function smartvideo_load_breakdance() {
		if ( defined( '__BREAKDANCE_VERSION' ) && defined( 'BREAKDANCE_MODE' ) && class_exists( '\Breakdance\Elements\Element' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/breakdance/class-breakdance-smartvideo.php';
		}
	}
	add_action( 'before_breakdance_loaded', 'smartvideo_load_breakdance' );
}



/**
 * @since 2.1.0
 */
function smart_video_init() {
	load_plugin_textdomain( 'swarmify', false, plugin_basename( __DIR__ ) . '/languages' );

	SmartVideo_Bootstrap::instance();
}

add_action( 'plugins_loaded', 'smart_video_init', 10 );
