<?php
/**
 * Plugin Name: SmartVideo
 * Description: SmartVideo makes building a beautiful, professional video experience for your site effortless.
 * Version: 2.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.3
 * Author: Swarmify
 * Author URI: https://swarmify.com/?smartvideo_wordpress_plugin
 * Developer: James Christensen
 * Developer URI: https://profiles.wordpress.org/chris10sen/
 * Text Domain: swarmify
 * Domain Path: /languages
 *
 * License: GNU Affero General Public License v3.0
 * License URI: https://www.gnu.org/licenses/agpl-3.0.en.html
 *
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SMARTVIDEO_PLUGIN_FILE' ) ) {
	define( 'SMARTVIDEO_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SWARMIFY_PLUGIN_VERSION' ) ) {
	define( 'SWARMIFY_PLUGIN_VERSION', '2.2.0' );
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

use Swarmify\Smartvideo as Smartvideo;


// phpcs:disable WordPress.Files.FileName


if ( ! function_exists( 'activate_smartvideo' ) ) {
	function activate_smartvideo() {
		Smartvideo\Activator::activate();
	}
}

register_activation_hook( __FILE__, 'activate_smartvideo' );


if ( ! class_exists( 'SmartVideo_Bootstrap' ) ) {
	/**
	 * The SmartVideo_Bootstrap class.
	 */
	class SmartVideo_Bootstrap {
		/**
		 * This class instance.
		 *
		 * @var \SmartVideo_Bootstrap single instance of this class.
		 */
		private static $instance;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// $plugin_name = dirname( plugin_basename( SMARTVIDEO_PLUGIN_FILE ) );
			// $plugin      = new Smartvideo\Swarmify( $plugin_name );

			$plugin      = new Smartvideo\Swarmify( 'SmartVideo' );
			$plugin->run();
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'swarmify' ), SWARMIFY_PLUGIN_VERSION );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'swarmify' ), SWARMIFY_PLUGIN_VERSION );
		}

		/**
		 * Gets the main instance.
		 *
		 * Ensures only one instance can be loaded.
		 *
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


/**
 *  Load page builder integrations.
 *
 *  Each integration is lazy-loaded only when its host plugin is active,
 *  so we don't parse files or register assets on every request for builders
 *  the site doesn't use.
 */

// Elementor — only load when Elementor has fired its init hook.
if ( ! function_exists( 'smartvideo_load_elementor' ) ) {
	function smartvideo_load_elementor() {
		if ( did_action( 'elementor/loaded' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/elementor/class-elementor-swarmify.php';
		}
	}
	add_action( 'init', 'smartvideo_load_elementor' );
}

// Gutenberg — core since WP 5.0, but only load block assets when block editor is available.
if ( ! function_exists( 'smartvideo_load_gutenberg' ) ) {
	function smartvideo_load_gutenberg() {
		if ( function_exists( 'register_block_type' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/gutenberg/src/init.php';
		}
	}
	add_action( 'init', 'smartvideo_load_gutenberg', 5 );
}

// Beaver Builder — only load when FL Builder is active.
if ( ! function_exists( 'smartvideo_load_beaver_builder' ) ) {
	function smartvideo_load_beaver_builder() {
		if ( class_exists( 'FLBuilder' ) ) {
			require plugin_dir_path( __FILE__ ) . 'includes/page-builders/beaverbuilder/class-beaverbuilder-smartvideo.php';
		}
	}
	add_action( 'init', 'smartvideo_load_beaver_builder' );
}

// Divi 5 — register module via the D5 dependency tree (fires before divi_extensions_init).
// The D4-era divi_extensions_init hook fires too late for D5's module registry,
// so we hook directly into the dependency tree action and load our files there.
if ( ! function_exists( 'smartvideo_load_divi5_builder' ) ) {
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

	// Also load on divi_extensions_init for VB asset enqueuing (styles/scripts).
	add_action( 'divi_extensions_init', function () {
		if ( function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled() ) {
			if ( ! defined( 'SMARTVIDEO_DIVI5_PATH' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/divi5-builder/divi5-smartvideo.php';
			}
		}
	}, 5 );
}

// Divi 4 — only load when Divi 5 is NOT enabled.
if ( ! function_exists( 'smartvideo_load_divi_builder' ) ) {
	function smartvideo_load_divi_builder() {
		if ( function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled() ) {
			return; // D5 module handles this.
		}
		require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/divi-builder/includes/DiviBuilder.php';
	}
	add_action( 'divi_extensions_init', 'smartvideo_load_divi_builder' );
}

// Bricks — only load when Bricks theme is active.
if ( ! function_exists( 'smartvideo_load_bricks' ) ) {
	function smartvideo_load_bricks() {
		if ( defined( 'BRICKS_VERSION' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/bricks/class-bricks-smartvideo.php';
		}
	}
	add_action( 'init', 'smartvideo_load_bricks', 9 );
}



/**
 * Initialize the plugin.
 *
 * @since 2.1.0
 */
function SmartVideo_init() {
	load_plugin_textdomain( 'swarmify', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	SmartVideo_Bootstrap::instance();
}

add_action( 'plugins_loaded', 'SmartVideo_init', 10 );
