<?php
/*
 *  smartvideo elementor support
 * */
namespace Elementor;

use \Elementor\Plugin as plugin;
class ElementorSwarmify {

	const VERSION                   = '1.0';
	const MINIMUM_ELEMENTOR_VERSION = '2.0';
	const MINIMUM_PHP_VERSION       = '5.6';

	private static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}



	public function __construct() {
		// This file is loaded at 'init' after Elementor is confirmed active,
		// so plugins_loaded has already fired. Call init() directly.
		$this->init();
	}

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

	public function swarmify_elementor_assets() {
		wp_enqueue_style(
			'swarmify-elementor-css',
			plugins_url( '/css/swarmify-elementor.css', __FILE__ ),
			array(),
			'2.1.0'
		);
		wp_enqueue_script(
			'swarmify-elementor-editor',
			plugins_url( '/js/smartvideo-editor.js', __FILE__ ),
			array(),
			'2.4.0',
			true
		);
	}

	public function SmartVideo( $manager ) {
		$manager->add_category(
			'Smart_video',
			array(
				'title' => __( 'Smart Video', 'swarmify' ),
				'icon'  => 'fa fa-video',
			)
		);
	}

	public function register_frontend_scripts() {
		wp_register_script(
			'smartvideo-elementor-frontend',
			plugins_url( '/js/smartvideo-frontend.js', __FILE__ ),
			array( 'jquery' ),
			'2.4.0',
			true
		);
	}

	public function register_widgets( $widgets_manager ) {
		include plugin_dir_path( __FILE__ ) . 'elementorsmartvideo.php';
		$class_name = __NAMESPACE__ . '\ElementorSmartvideo';
		$widgets_manager->register( new $class_name() );
	}
	// Check for elementor required php version
	public function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'SmartVideo requires Elementor to be installed and activated.', 'swarmify' )
		);
	}

	// Elementor version check/
	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
		printf(
			'<div class="notice notice-warning is-dismissible"><p>"%1$s" requires "<strong>%2$s</strong>" version <strong>%3$s</strong> or greater.</p></div>', 
			esc_html__( 'Smart Video Elementor', 'swarmify' ),
			esc_html__( 'Elementor', 'swarmify' ),
			esc_html( self::MINIMUM_ELEMENTOR_VERSION )
		); 
	}

	// Check for elementor required php version
	public function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
		printf( 
			'<div class="notice notice-warning is-dismissible"><p>"<strong>%1$s</strong>" requires "<strong>%2$s</strong>" version %3$s or greater.</p></div>', 
			esc_html__( 'Elementor Test Extension', 'swarmify' ),
			esc_html__( 'PHP', 'swarmify' ),
			esc_html( self::MINIMUM_PHP_VERSION )
		);
	}

}
ElementorSwarmify::instance();
