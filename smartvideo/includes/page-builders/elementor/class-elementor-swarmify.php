<?php
/*
 *  smartvideo elementor support
 * */
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Plugin;
class ElementorSwarmify {

	const VERSION                   = '1.0';
	const MINIMUM_ELEMENTOR_VERSION = '3.5';
	const MINIMUM_PHP_VERSION       = '7.4';

	private static $_instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return ElementorSwarmify The shared singleton.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}



	/**
	 * Boot the Elementor integration.
	 *
	 * This file is loaded at 'init' after Elementor is confirmed active,
	 * so plugins_loaded has already fired. Calls init() directly.
	 */
	public function __construct() {
		// This file is loaded at 'init' after Elementor is confirmed active,
		// so plugins_loaded has already fired. Call init() directly.
		$this->init();
	}

	/**
	 * Verify Elementor / PHP version requirements and register Elementor hooks.
	 *
	 * @return void
	 */
	public function init() {
		// load_plugin_textdomain( 'kd-elementor-addons' );

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}
		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}

		// Add Plugin actions
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'SmartVideo' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'swarmify_elementor_assets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_frontend_scripts' ) );
	}

	/**
	 * Enqueue editor-side CSS/JS used by the SmartVideo Elementor widget.
	 *
	 * @return void
	 */
	public function swarmify_elementor_assets() {
		wp_enqueue_style(
			'swarmify-elementor-css',
			plugins_url( '/css/swarmify-elementor.css', __FILE__ ),
			array(),
			SWARMIFY_PLUGIN_VERSION
		);
		wp_enqueue_script(
			'swarmify-elementor-editor',
			plugins_url( '/js/smartvideo-editor.js', __FILE__ ),
			array(),
			SWARMIFY_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Register the "Smart Video" category in the Elementor element panel.
	 *
	 * @param  \Elementor\Elements_Manager $manager Elementor elements manager.
	 * @return void
	 */
	public function SmartVideo( $manager ) {
		$manager->add_category(
			'Smart_video',
			array(
				'title' => __( 'SmartVideo', 'swarmify' ),
				'icon'  => 'fa fa-video',
			)
		);
	}

	/**
	 * Register the SmartVideo frontend script with Elementor.
	 *
	 * @return void
	 */
	public function register_frontend_scripts() {
		wp_register_script(
			'smartvideo-elementor-frontend',
			plugins_url( '/js/smartvideo-frontend.js', __FILE__ ),
			array( 'jquery' ),
			SWARMIFY_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Register the SmartVideo widget with Elementor's widget manager.
	 *
	 * @param  \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		include plugin_dir_path( __FILE__ ) . 'elementorsmartvideo.php';
		$class_name = __NAMESPACE__ . '\ElementorSmartvideo';
		$widgets_manager->register( new $class_name() );
	}
	/**
	 * Print an admin notice when Elementor is not installed/active.
	 *
	 * @return void
	 */
	public function admin_notice_missing_main_plugin() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core sets this query var after plugin activation; not processing user form data.
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'SmartVideo requires Elementor to be installed and activated.', 'swarmify' )
		);
	}

	/**
	 * Print an admin notice when the active Elementor version is below the minimum.
	 *
	 * @return void
	 */
	public function admin_notice_minimum_elementor_version() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core sets this query var after plugin activation; not processing user form data.
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name, 2: Dependency name, 3: Minimum version. */
				esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'swarmify' ),
				'SmartVideo',
				'Elementor',
				esc_html( self::MINIMUM_ELEMENTOR_VERSION )
			)
		);
	}

	/**
	 * Print an admin notice when the running PHP version is below the minimum.
	 *
	 * @return void
	 */
	public function admin_notice_minimum_php_version() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WordPress core sets this query var after plugin activation; not processing user form data.
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name, 2: Dependency name, 3: Minimum version. */
				esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'swarmify' ),
				'SmartVideo',
				'PHP',
				esc_html( self::MINIMUM_PHP_VERSION )
			)
		);
	}
}
ElementorSwarmify::instance();
