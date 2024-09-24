<?php
/**
 * Plugin Name: SmartVideo
 * Description: SmartVideo makes building a beautiful, professional video experience for your site effortless.
 * Version: 2.1.1
 * Requires at least: 3.0.1
 * Requires PHP: 7.3
 * Author: Swarmify
 * Author URI: https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=48
 * Developer: Matthew Davidson
 * Developer URI: https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=48
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

define( 'SWARMIFY_PLUGIN_VERSION', '2.1.1' );

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';

use Swarmify\Smartvideo as Smartvideo;


// phpcs:disable WordPress.Files.FileName


if ( ! function_exists( 'activate_smartvideo' ) ) {
	function activate_smartvideo() {
		Smartvideo\Activator::activate();
	}
}

/**
 * The code that runs during plugin deactivation.
 */
if ( ! function_exists( 'deactivate_smartvideo' ) ) {
	function deactivate_smartvideo() {
		Smartvideo\Deactivator::deactivate();
	}
}

register_activation_hook( __FILE__, 'activate_smartvideo' );
register_deactivation_hook( __FILE__, 'deactivate_smartvideo' );


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
			_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'swarmify' ), $this->version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'swarmify' ), $this->version );
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
 *  Load page builders
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/elementor/class-elementor-swarmify.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/gutenberg/src/init.php';

if ( ! function_exists( 'smartvideo_load_beaver_builder' ) ) {
	function smartvideo_load_beaver_builder() {
		if ( class_exists( 'FLBuilder' ) ) {
			require plugin_dir_path( __FILE__ ) . 'includes/page-builders/beaverbuilder/class-beaverbuilder-smartvideo.php';
		}
	}
	add_action( 'init', 'smartvideo_load_beaver_builder' );
} else {
	error_log( 'Smartvideo: Unable to initialize Beaver Builder integration' );
}

if ( ! function_exists( 'smartvideo_load_divi_builder' ) ) {
	function smartvideo_load_divi_builder() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/page-builders/divi-builder/includes/DiviBuilder.php';
	}

	add_action( 'divi_extensions_init', 'smartvideo_load_divi_builder' );
} else {
	error_log( 'Smartvideo: Unable to initialize Divi integration' );
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
