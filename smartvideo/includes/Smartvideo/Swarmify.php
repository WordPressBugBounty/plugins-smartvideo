<?php

namespace Swarmify\Smartvideo;

use Error;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=48
 * @since      1.0.0
 *
 * @package    Swarmify
 * @subpackage Swarmify/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Swarmify
 * @subpackage Swarmify/includes
 */


class Swarmify {
	public const API_VERSION = 'v1';


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * @var Settings
	 */
	protected $settings;


	protected $swarmdetect_handle = 'smartvideo_swarmdetect';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name ) {
		if ( defined( 'SWARMIFY_PLUGIN_VERSION' ) ) {
			$this->version = SWARMIFY_PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = $plugin_name;

		$this->settings = new Settings( $this->plugin_name, $this->version );

		// $this->log_debug_info();

		// enable upload accelerator
		$swarmify_upload_accelerator = UploadAccelerator::get_instance();

		$this->loader = new Loader();
		$this->load_config_from_constants();

		if ( is_admin() ) {
			$this->define_admin_hooks();
		}

		$this->set_locale();
		$this->define_public_hooks();

		add_shortcode( 'smartvideo', array( $this, 'smartvideo_shortcode' ) );

	}


	public function smartvideo_shortcode( $atts ) {
		$atts         = shortcode_atts(
			array(
				'src'         => '',
				'poster'      => '',
				'height'      => '',
				'width'       => '',
				'responsive'  => '',
				'autoplay'    => '',
				'muted'       => '',
				'loop'        => '',
				'controls'    => '',
				'playsinline' => '',
			),
			$atts,
			'smartvideo'
		);
		$swarmify_url = $atts['src'];
		$poster       = ( '' === $atts['poster'] ? '' : 'poster="' . esc_url($atts['poster']) . '"' );
		$height       = ( '' !== $atts['height'] ? $atts['height'] : '' );
		$width        = ( '' !== $atts['width'] ? $atts['width'] : '' );
		$autoplay     = ( 'true' === $atts['autoplay'] ? 'autoplay' : '' );
		$muted        = ( 'true' === $atts['muted'] ? 'muted' : '' );
		$loop         = ( 'true' === $atts['loop'] ? 'loop' : '' );
		$controls     = ( 'true' === $atts['controls'] ? 'controls' : '' );
		$video_inline = ( 'true' === $atts['playsinline'] ? 'playsinline' : '' );
		$unresponsive = ( 'true' === $atts['responsive'] ? 'swarm-fluid' : '' );


		return '<smartvideo src="' . esc_url($swarmify_url) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" class="' . esc_attr($unresponsive) . '" ' . $poster . ' ' . esc_attr($autoplay) . ' ' . esc_attr($muted) . ' ' . esc_attr($loop) . ' ' . esc_attr($controls) . ' ' . esc_attr($video_inline) . '></smartvideo>';
	}

	/**
	 * Load any configuration defined by constants in the wp_config file
	 *
	 * @since    2.0.12
	 */
	private function load_config_from_constants() {

		// Check for configuration via globals in wp_config.php
		if ( defined( 'SWARMIFY_CDN_KEY' ) ) {
			update_option( 'swarmify_cdn_key', constant( 'SWARMIFY_CDN_KEY' ) );
		}
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new I18n();

		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {
		$admin = new Admin( $this->plugin_name, $this->version, $this->settings );

		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_classic_editor_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_classic_editor_scripts' );

		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'register_scripts' );
		$this->loader->add_action( 'admin_menu', $admin, 'register_page' );

		$this->loader->add_action( 'media_buttons', $admin, 'add_video_button', 15 );
		$this->loader->add_action( 'admin_footer', $admin, 'add_video_lightbox_html' );

		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( SMARTVIDEO_PLUGIN_FILE ), $admin, 'plugin_action_links' );
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_public_hooks() {
		$this->loader->add_action( 'wp_head', $this, 'add_preconnect_link', 2 );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_swarmify_script' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_swarmify_script' );
		$this->loader->add_filter( 'script_loader_tag', $this, 'add_async_swarmdetect_script_attributes', 10, 2);

		// This should be an admin hook really, but REST API calls return false for is_admin()
		$this->loader->add_action( 'rest_api_init', $this->settings, 'register_plugin_settings_routes' );

		$this->loader->add_action( 'widgets_init', $this, 'load_widget' );

		$this->loader->add_filter( 'kses_allowed_protocols' , $this, 'add_swarmify_url_protocol' );
	}



	/**
	 * Enqueue the swarmdetect settings and script
	 */
	public function enqueue_swarmify_script() {
		$cdn_key            = get_option( 'swarmify_cdn_key' );
		$swarmify_status    = get_option( 'swarmify_status' );

		if ( 'on' === $swarmify_status && '' !== $cdn_key ) {

			$youtube            = get_option( 'swarmify_toggle_youtube' );
			$youtube_cc         = get_option( 'swarmify_toggle_youtube_cc' );
			$layout             = get_option( 'swarmify_toggle_layout' );
			$bgoptimize         = get_option( 'swarmify_toggle_bgvideo' );
			$theme_primarycolor = get_option( 'swarmify_theme_primarycolor', '#ffde17' );
			$theme_button       = get_option( 'swarmify_theme_button' );
			$watermark          = get_option( 'swarmify_watermark' );
			$ads_vasturl        = get_option( 'swarmify_ads_vasturl' );

			// Configure `autoreplace` object
			$autoreplaceObject = new \stdClass();

			if ( 'on' == $youtube ) {
				$autoreplaceObject->youtube = true;
			} else {
				$autoreplaceObject->youtube = false;
			}
			
			if ('on' == $youtube_cc ) {
				$autoreplaceObject->youtubecaptions = true;
			} else {
				$autoreplaceObject->youtubecaptions = false;
			}
			
			if ('on' == $bgoptimize) {
				$autoreplaceObject->videotag = true;
			} else {
				$autoreplaceObject->videotag = false;
			}
			
			if ('on' == $layout) {
				$layout_status = 'iframe';
			} else {
				$layout_status = 'video';
			}

			// Configure `theme` object
			$themeObject = new \stdClass();

			if ( $theme_primarycolor ) {
				$themeObject->primaryColor = $theme_primarycolor;
			}

			// Limit button type to `no selection` which is hexagon, `rectangle`, or `circle`
			$button_type = null;
			if ('rectangle' == $theme_button) {
				$themeObject->button = $theme_button;
			}
			if ('circle' == $theme_button) {
				$themeObject->button = $theme_button;
			}

			// Configure `plugins` object
			$pluginsObject = new \stdClass();

			// Configure `plugins->swarmads` object
			if ( $ads_vasturl && '' !== $ads_vasturl ) {
				// Create the `swarmads` subobject
				$swarmadsObject           = new \stdClass();
				$swarmadsObject->adTagUrl = $ads_vasturl;

				// Store the `swarmadsObject` in the `pluginsObject`
				$pluginsObject->swarmads = $swarmadsObject;
			}

			// Configure `plugins->watermark` object
			if ( $watermark && '' !== $watermark ) {
				// Create the `swarmads` subobject
				$watermarkObject = new \stdClass();
				$watermarkObject->file = $watermark;
				$watermarkObject->opacity = 0.75;
				$watermarkObject->xpos    = 100;
				$watermarkObject->ypos    = 100;

				// Store the `watermarkObject` in the `pluginsObject`
				$pluginsObject->watermark = $watermarkObject;
			}

			$swarmoptions_js = '
				var swarmoptions = {
					swarmcdnkey: "' . $cdn_key . '",
					autoreplace: ' . json_encode( $autoreplaceObject ) . ',
					theme: ' . json_encode( $themeObject ) . ',
					plugins: ' . json_encode( $pluginsObject ) . ',
					iframeReplacement: "' . $layout_status . '"
				};
			';

			wp_enqueue_script( 
				$this->swarmdetect_handle, 
				'https://assets.swarmcdn.com/cross/swarmdetect.js', 
				array(), 
				$this->version, 
				false
			);
	
			wp_add_inline_script( $this->swarmdetect_handle, $swarmoptions_js, 'before' );          
		}
	}

	public function add_preconnect_link() {
		echo '<link rel="preconnect" href="https://assets.swarmcdn.com">';
	}

	// This fn exists primarily to appease the QIT linter rules, since we have 
	// to use wp_enqueue_script, which lacks support for custom <script> attrs
	public function add_async_swarmdetect_script_attributes( $tag, $handle ) {
		// Add async and data-cfasync attributes for linter
		if ( $this->swarmdetect_handle === $handle ) {
			return str_replace( 
				array( ' src=', '<script ' ), 
				array( ' async src=', '<script data-cfasync="false" ' ), 
				$tag 
			);
		}
	
		return $tag;
	}

	public function add_swarmify_url_protocol( $protocols ) {
		$protocols[] = 'swarmify';
		return $protocols;
	}

	public function load_widget() {
		register_widget( 'Swarmify\Smartvideo\AdminWidget' );
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	// public function get_plugin_name() {
	// return $this->plugin_name;
	// }

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	// public function get_loader() {
	// return $this->loader;
	// }

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	// public function get_version() {
	// return $this->version;
	// }

	public function log_debug_info() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php'; // needed for get_plugins()

		$info = var_export(
			array(
				'this->plugin_name'          => $this->plugin_name,
				'plugin_basename'            => plugin_basename( SMARTVIDEO_PLUGIN_FILE ),
				'plugin_dir_path'            => plugin_dir_path( SMARTVIDEO_PLUGIN_FILE ),
				'dirname(plugin_dir_path())' => dirname( plugin_dir_path( SMARTVIDEO_PLUGIN_FILE ) ),
				'dirname(plugin_basename())' => dirname( plugin_basename( SMARTVIDEO_PLUGIN_FILE ) ),
				'plugin_basename(dirname())' => plugin_basename( dirname( SMARTVIDEO_PLUGIN_FILE ) ),
				'plugin_dir_url'             => plugin_dir_url( SMARTVIDEO_PLUGIN_FILE ),
			// 'get_plugins' => get_plugins(),
			),
			true
		);

		error_log( $info );
	}

}
