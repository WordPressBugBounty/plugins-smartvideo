<?php

namespace Swarmify\Smartvideo;

use Error;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://swarmify.com/?smartvideo_wordpress_plugin
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

	/**
	 * @var PostMeta
	 */
	protected $post_meta;

	/**
	 * Whether the frontend script should load on the current page.
	 * Set during template_redirect based on content analysis.
	 *
	 * @var bool|null null = not yet determined
	 */
	protected $should_load_script = null;

	protected $swarmdetect_handle = 'smartvideo_swarmdetect';

	// Resolved once in enqueue, reused by the script_loader_tag filter so the
	// URL and the fetchpriority attribute can never disagree.
	protected $use_beta_player = false;

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

		$this->settings  = new Settings( $this->plugin_name, $this->version );
		$this->post_meta = new PostMeta();

		// $this->log_debug_info();

		// enable upload accelerator (admin-only — plupload filters and AJAX handler)
		if ( is_admin() ) {
			UploadAccelerator::get_instance();
		}

		$this->load_config_from_constants();

		if ( is_admin() ) {
			$this->define_admin_hooks();
		}

		$this->define_public_hooks();

		add_shortcode( 'smartvideo', array( $this, 'smartvideo_shortcode' ) );

	}


	public function smartvideo_shortcode( $atts ) {
		$atts         = shortcode_atts(
			array(
				'src'          => '',
				'poster'       => '',
				'height'       => '',
				'width'        => '',
				'aspect_ratio' => '',
				'responsive'   => '',
				'autoplay'     => '',
				'muted'        => '',
				'loop'         => '',
				'controls'     => '',
				'playsinline'  => '',
				'preload'      => '',
			),
			$atts,
			'smartvideo'
		);
		$swarmify_url = $atts['src'];
		if ( empty( $swarmify_url ) ) {
			return AspectRatio::empty_placeholder();
		}
		$poster       = ( '' === $atts['poster'] ? '' : 'poster="' . esc_url($atts['poster']) . '"' );

		// Resolve dimensions from aspect ratio when not custom and no explicit dimensions.
		if ( '' !== $atts['aspect_ratio'] && 'custom' !== $atts['aspect_ratio'] ) {
			list( $width, $height ) = AspectRatio::resolve( $atts['aspect_ratio'] );
		} else {
			$height = ( '' !== $atts['height'] ? $atts['height'] : '' );
			$width  = ( '' !== $atts['width'] ? $atts['width'] : '' );
		}
		// Shortcodes use explicit, predictable defaults — not global settings.
		// Global defaults feed builder UI pre-selections, not raw shortcodes.
		$sc_bool = function ( $val, $default = false ) {
			if ( '' !== $val ) {
				return 'true' === $val;
			}
			return $default;
		};
		$autoplay     = $sc_bool( $atts['autoplay'] ) ? 'autoplay' : '';
		$muted        = $sc_bool( $atts['muted'] ) ? 'muted' : '';
		$loop         = $sc_bool( $atts['loop'] ) ? 'loop' : '';
		$controls     = $sc_bool( $atts['controls'], true ) ? 'controls' : '';
		$video_inline = $sc_bool( $atts['playsinline'] ) ? 'playsinline' : '';
		$unresponsive = $sc_bool( $atts['responsive'] ) ? 'swarm-fluid' : '';
		$preload_raw  = '' !== $atts['preload'] ? $atts['preload'] : 'auto';
		$preload      = in_array( $preload_raw, array( 'auto', 'metadata', 'none' ), true ) ? $preload_raw : 'auto';
		$preload_attr = ( 'auto' !== $preload ) ? 'preload="' . esc_attr( $preload ) . '"' : '';


		SchemaCollector::add( $swarmify_url, '' !== $atts['poster'] ? $atts['poster'] : '' );

		return '<smartvideo src="' . esc_url($swarmify_url) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" class="' . esc_attr($unresponsive) . '" ' . $poster . ' ' . esc_attr($autoplay) . ' ' . esc_attr($muted) . ' ' . esc_attr($loop) . ' ' . esc_attr($controls) . ' ' . esc_attr($video_inline) . ' ' . $preload_attr . '></smartvideo>';
	}

	/**
	 * Load any configuration defined by constants in the wp_config file
	 *
	 * @since    2.0.12
	 */
	private function load_config_from_constants() {

		// Check for configuration via globals in wp_config.php
		if ( defined( 'SWARMIFY_CDN_KEY' ) ) {
			$key = constant( 'SWARMIFY_CDN_KEY' );
			// Validate UUID format before writing to database.
			if ( is_string( $key ) && preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key ) ) {
				if ( get_option( 'swarmify_cdn_key', '' ) !== $key ) {
					update_option( 'swarmify_cdn_key', $key );
				}
			}
		}
	}


	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {
		$admin = new Admin( $this->plugin_name, $this->version, $this->settings );

		add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_classic_editor_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_classic_editor_scripts' ] );

		add_action( 'admin_enqueue_scripts', [ $admin, 'register_scripts' ] );
		add_action( 'admin_menu', [ $admin, 'register_page' ] );
		add_action( 'admin_init', [ $admin, 'activation_redirect' ] );
		add_action( 'admin_notices', [ $admin, 'admin_notices' ] );

		add_action( 'media_buttons', [ $admin, 'add_video_button' ], 15 );
		add_action( 'admin_footer', [ $admin, 'add_video_lightbox_html' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( SMARTVIDEO_PLUGIN_FILE ), [ $admin, 'plugin_action_links' ] );

		// Per-page disable toggle.
		add_action( 'init', [ $this->post_meta, 'register_meta' ] );
		add_action( 'add_meta_boxes', [ $this->post_meta, 'add_meta_box' ] );
		add_action( 'save_post', [ $this->post_meta, 'save_meta_box' ] );

		// Dashboard widget.
		$dashboard_widget = new DashboardWidget();
		add_action( 'wp_dashboard_setup', [ $dashboard_widget, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ $dashboard_widget, 'enqueue_styles' ] );
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_public_hooks() {
		add_action( 'wp_head', [ $this, 'add_preconnect_link' ], 2 );
		add_action( 'wp_footer', [ 'Swarmify\Smartvideo\SchemaCollector', 'output_schema' ], 20 );
		add_action( 'template_redirect', [ $this, 'check_should_load_script' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_swarmify_script' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_swarmify_script' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_async_swarmdetect_script_attributes' ], 10, 2 );

		// This should be an admin hook really, but REST API calls return false for is_admin()
		add_action( 'rest_api_init', [ $this->settings, 'register_plugin_settings_routes' ] );

		add_action( 'widgets_init', [ $this, 'load_widget' ] );

		add_filter( 'kses_allowed_protocols', [ $this, 'add_swarmify_url_protocol' ] );

		// Collect schema from Gutenberg static block output (no server-side render_callback).
		add_filter( 'render_block_smartvideo/block-smartvideo-guten', [ $this, 'collect_gutenberg_schema' ], 10, 2 );
	}



	/**
	 * Determine whether the current page needs the SmartVideo script.
	 * Runs on template_redirect so the queried object is available.
	 */
	public function check_should_load_script() {
		// Per-page disable only applies to singular pages (not archives).
		if ( is_singular() && PostMeta::is_disabled() ) {
			$this->should_load_script = false;
			return;
		}

		// Conditional loading modes:
		//   'off'      — always load (default)
		//   'standard' — load on pages with any video content
		//   'strict'   — load only when enabled features will act on the content
		$mode = $this->settings->get( 'swarmify_toggle_conditional_loading' );
		if ( 'off' === $mode ) {
			$this->should_load_script = true;
			return;
		}

		// Allow themes/plugins to force-load the script.
		if ( apply_filters( 'smartvideo_force_load_script', false ) ) {
			$this->should_load_script = true;
			return;
		}

		// Check for an active SmartVideo widget.
		if ( is_active_widget( false, false, 'smartvideo_widget' ) ) {
			$this->should_load_script = true;
			return;
		}

		// Build the list of content to scan — singular pages use the queried
		// object; archive/index pages check every post in the main query.
		$contents = array();
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$contents[] = $post->post_content;
			}
		} else {
			global $wp_query;
			if ( ! empty( $wp_query->posts ) ) {
				foreach ( $wp_query->posts as $p ) {
					$contents[] = $p->post_content;
				}
			}
		}

		// Feature flags — strict mode needs to know which content
		// types the player will convert, not just explicit SmartVideo tags.
		$auto_yt  = 'on' === $this->settings->get( 'swarmify_toggle_youtube' );
		$bg_video = 'on' === $this->settings->get( 'swarmify_toggle_bgvideo' );

		// --- Content scan ---
		// Both modes check for native SmartVideo markers.
		foreach ( $contents as $content ) {
			if ( has_shortcode( $content, 'smartvideo' ) ||
				 has_shortcode( $content, 'smartvideo_divi_module' ) ||
				 // strpos fallback — has_shortcode() needs the shortcode registered,
				 // which requires the builder theme to be active and loaded.
				 strpos( $content, '[smartvideo_divi_module' ) !== false ||
				 has_block( 'smartvideo/block-smartvideo-guten', $content ) ||
				 has_block( 'smartvideo/smartvideo', $content ) ||
				 preg_match( '/<smartvideo[\s>]/', $content ) ) {
				$this->should_load_script = true;
				return;
			}

			// Strict mode: also check for content the player will
			// auto-convert, based on which features are enabled.
			if ( 'strict' === $mode ) {
				if ( $auto_yt && preg_match( '/youtube\.com|youtu\.be|vimeo\.com/', $content ) ) {
					$this->should_load_script = true;
					return;
				}
				if ( $bg_video &&
					( preg_match( '/<video[\s>]/i', $content ) ||
					  has_block( 'divi/video', $content ) ||
					  has_shortcode( $content, 'et_pb_video' ) ||
					  strpos( $content, '[et_pb_video' ) !== false ) ) {
					$this->should_load_script = true;
					return;
				}
			}

			// Standard mode: load on any video content (safe default).
			if ( 'standard' === $mode ) {
				if ( preg_match( '/youtube\.com|youtu\.be|vimeo\.com/', $content ) ||
					 has_block( 'core/embed', $content ) ||
					 has_block( 'divi/video', $content ) ||
					 has_shortcode( $content, 'et_pb_video' ) ||
					 strpos( $content, '[et_pb_video' ) !== false ||
					 preg_match( '/<iframe[\s>]/i', $content ) ||
					 preg_match( '/<video[\s>]/i', $content ) ) {
					$this->should_load_script = true;
					return;
				}
			}
		}

		// --- Builder metadata scan ---
		// Page builders store content in postmeta, not post_content.
		// Each builder has its own format, so we check natively first,
		// then fall back to a concatenated string scan for video content.
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				$meta_content = '';

				// Elementor: JSON widget data in _elementor_data.
				if ( class_exists( '\Elementor\Plugin' ) ) {
					$elementor_data = (string) get_post_meta( $post_id, '_elementor_data', true );
					if ( $elementor_data ) {
						// Parsed natively below — don't add to $meta_content
						// to avoid false positives from default field values.
						$el_decoded = json_decode( $elementor_data, true );
						if ( is_array( $el_decoded ) &&
							 $this->scan_elementor_widgets( $el_decoded, $mode, $auto_yt, $bg_video ) ) {
							$this->should_load_script = true;
							return;
						}
					}
				}

				// Beaver Builder: serialized module data in _fl_builder_data.
				if ( class_exists( 'FLBuilder' ) ) {
					$bb_data = get_post_meta( $post_id, '_fl_builder_data', true );
					if ( is_array( $bb_data ) ) {
						$meta_content .= maybe_serialize( $bb_data );
						foreach ( $bb_data as $node ) {
							if ( ! isset( $node->type ) || 'module' !== $node->type || ! isset( $node->settings->type ) ) {
								continue;
							}
							// BB stores the module file slug, not the class name.
							if ( 'class-beaverbuilder-smartvideo' === $node->settings->type ) {
								$this->should_load_script = true;
								return;
							}
							// Native BB video module — check video_type against
							// enabled features, same pattern as Bricks.
							if ( 'video' === $node->settings->type ) {
								$vtype = $node->settings->video_type ?? '';
								if ( $bg_video && in_array( $vtype, [ 'media_library', '' ], true ) ) {
									$this->should_load_script = true;
									return;
								}
								if ( $auto_yt && 'embed' === $vtype ) {
									$this->should_load_script = true;
									return;
								}
								if ( 'standard' === $mode ) {
									$this->should_load_script = true;
									return;
								}
							}
						}
					}
				}

				// Bricks: element array in _bricks_page_content_2.
				if ( defined( 'BRICKS_VERSION' ) ) {
					$bricks_data = get_post_meta( $post_id, '_bricks_page_content_2', true );
					if ( is_array( $bricks_data ) ) {
						$meta_content .= maybe_serialize( $bricks_data );
						foreach ( $bricks_data as $element ) {
							if ( ! isset( $element['name'] ) ) {
								continue;
							}
							if ( 'smartvideo' === $element['name'] ) {
								$this->should_load_script = true;
								return;
							}
							// Native Bricks video element — stores YouTube ID
							// without a URL, so the string scan won't catch it.
							if ( 'video' === $element['name'] ) {
								$vtype = $element['settings']['videoType'] ?? '';
								if ( $bg_video && 'file' === $vtype ) {
									$this->should_load_script = true;
									return;
								}
								if ( $auto_yt && in_array( $vtype, [ 'youtube', 'vimeo' ], true ) ) {
									$this->should_load_script = true;
									return;
								}
								if ( 'standard' === $mode ) {
									$this->should_load_script = true;
									return;
								}
							}
						}
					}
				}

					if ( $meta_content ) {
					// Both modes: check for SmartVideo shortcodes/tags in
					// builder metadata (catches raw embeds in HTML blocks).
					if ( strpos( $meta_content, '[smartvideo' ) !== false ||
						 strpos( $meta_content, '<smartvideo' ) !== false ) {
						$this->should_load_script = true;
						return;
					}

					// Strict mode: check for auto-convert targets
					// based on enabled features.
					if ( 'strict' === $mode ) {
						if ( $auto_yt && preg_match( '/youtube\.com|youtu\.be|vimeo\.com/', $meta_content ) ) {
							$this->should_load_script = true;
							return;
						}
						if ( $bg_video &&
							stripos( $meta_content, '<video' ) !== false ) {
							$this->should_load_script = true;
							return;
						}
					}

					// Standard mode: load on any video content (safe default).
					if ( 'standard' === $mode ) {
						if ( preg_match( '/youtube\.com|youtu\.be|vimeo\.com/', $meta_content ) ||
							 stripos( $meta_content, '<video' ) !== false ||
							 stripos( $meta_content, '<iframe' ) !== false ) {
							$this->should_load_script = true;
							return;
						}
					}
				}
			}
		}

		$this->should_load_script = false;
	}

	/**
	 * Recursively scan Elementor widget tree for video content.
	 *
	 * Elementor stores default field values (e.g. a YouTube URL) even when the
	 * widget is set to a different video type, so raw string scanning produces
	 * false positives. This checks each widget's actual video_type setting.
	 *
	 * @param array  $elements Elementor elements array (sections/columns/widgets).
	 * @param string $mode     'standard' or 'strict'.
	 * @param bool   $auto_yt  Whether YouTube auto-conversion is on.
	 * @param bool   $bg_video Whether background video conversion is on.
	 * @return bool True if the script should load.
	 */
	private function scan_elementor_widgets( $elements, $mode, $auto_yt, $bg_video ) {
		foreach ( $elements as $element ) {
			$widget_type = $element['widgetType'] ?? '';

			if ( 'smartvideo' === $widget_type ) {
				return true;
			}

			if ( 'video' === $widget_type ) {
				// Elementor defaults to 'youtube' when video_type is unset.
				$video_type = $element['settings']['video_type'] ?? 'youtube';
				if ( in_array( $video_type, [ 'youtube', 'vimeo' ], true ) ) {
					if ( $auto_yt || 'standard' === $mode ) {
						return true;
					}
				}
				if ( 'hosted' === $video_type ) {
					if ( $bg_video || 'standard' === $mode ) {
						return true;
					}
				}
			}

			// HTML, shortcode, and text-editor widgets can contain arbitrary
			// video embeds. Scan their settings for video content strings.
			if ( in_array( $widget_type, [ 'html', 'shortcode', 'text-editor' ], true ) ) {
				$settings_text = wp_json_encode( $element['settings'] ?? [] );
				if ( strpos( $settings_text, '<smartvideo' ) !== false ||
					 strpos( $settings_text, '[smartvideo' ) !== false ) {
					return true;
				}
				if ( 'standard' === $mode || $auto_yt ) {
					if ( preg_match( '/youtube\.com|youtu\.be|vimeo\.com/', $settings_text ) ) {
						return true;
					}
				}
				if ( 'standard' === $mode || $bg_video ) {
					if ( stripos( $settings_text, '<video' ) !== false ||
						 stripos( $settings_text, '<iframe' ) !== false ) {
						return true;
					}
				}
			}

			// Section/column background video settings.
			if ( ! empty( $element['settings']['background_video_link'] ) ) {
				if ( $bg_video || 'standard' === $mode ) {
					return true;
				}
			}

			// Recurse into child elements (sections → columns → widgets).
			if ( ! empty( $element['elements'] ) ) {
				if ( $this->scan_elementor_widgets( $element['elements'], $mode, $auto_yt, $bg_video ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Enqueue the swarmdetect settings and script
	 */
	public function enqueue_swarmify_script() {
		$cdn_key            = $this->settings->get( 'swarmify_cdn_key' );
		$swarmify_status    = $this->settings->get( 'swarmify_status' );

		if ( 'on' === $swarmify_status && '' !== $cdn_key ) {

			// On the frontend, skip loading if the page doesn't need the script.
			// Admin pages and builder previews always load.
			if ( ! is_admin() && false === $this->should_load_script ) {
				// Builder edit modes load as frontend pages — always load the script.
				$in_builder = ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) ||
				              ( class_exists( '\FLBuilderModel' ) && \FLBuilderModel::is_builder_active() ) ||
				              ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) ||
				              ( class_exists( '\Elementor\Plugin' ) && isset( $_GET['elementor-preview'] ) );
				if ( ! $in_builder ) {
					return;
				}
			}

			$youtube            = $this->settings->get( 'swarmify_toggle_youtube' );
			$youtube_cc         = $this->settings->get( 'swarmify_toggle_youtube_cc' );
			$layout             = $this->settings->get( 'swarmify_toggle_layout' );
			$bgoptimize         = $this->settings->get( 'swarmify_toggle_bgvideo' );
			$theme_primarycolor = $this->settings->get( 'swarmify_theme_primarycolor' );
			$theme_button       = $this->settings->get( 'swarmify_theme_button' );
			$watermark          = $this->settings->get( 'swarmify_watermark' );
			$ads_vasturl        = $this->settings->get( 'swarmify_ads_vasturl' );

			// Configure `autoreplace` object
			$autoreplaceObject = new \stdClass();

			if ( 'on' === $youtube ) {
				$autoreplaceObject->youtube = true;
			} else {
				$autoreplaceObject->youtube = false;
			}
			
			if ('on' === $youtube_cc ) {
				$autoreplaceObject->youtubecaptions = true;
			} else {
				$autoreplaceObject->youtubecaptions = false;
			}
			
			if ('on' === $bgoptimize) {
				$autoreplaceObject->videotag = true;
			} else {
				$autoreplaceObject->videotag = false;
			}
			
			if ('on' === $layout) {
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
			if ('rectangle' === $theme_button || 'circle' === $theme_button) {
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

			$swarmoptions = array(
				'swarmcdnkey'        => $cdn_key,
				'autoreplace'        => $autoreplaceObject,
				'theme'              => $themeObject,
				'plugins'            => $pluginsObject,
				'iframeReplacement'  => $layout_status,
			);
			$swarmoptions_js = 'var swarmoptions = ' . wp_json_encode( $swarmoptions ) . ';';

			$this->use_beta_player = 'on' === $this->settings->get( 'swarmify_toggle_beta_player' );
			$script_src            = $this->use_beta_player
				? 'https://assets.swarmcdn.com/beta/swarmcdn.js'
				: 'https://assets.swarmcdn.com/cross/swarmdetect.js';

			wp_enqueue_script(
				$this->swarmdetect_handle,
				$script_src,
				array(),
				$this->version,
				false
			);

			wp_add_inline_script( $this->swarmdetect_handle, $swarmoptions_js, 'before' );

			// oEmbed renders YouTube/Vimeo iframes at fixed dimensions.
			// When swarmdetect keeps the iframe (iframeReplacement: "iframe"),
			// it stays at those small dimensions. Make it responsive across
			// all builder video wrappers.
			if ( 'on' === $youtube ) {
				wp_register_style( 'smartvideo-frontend', false );
				wp_enqueue_style( 'smartvideo-frontend' );
				wp_add_inline_style( 'smartvideo-frontend',
					'iframe.swarm-iframe'
					. '{ width: 100% !important; height: auto !important; aspect-ratio: auto 16/9; }'
				);
			}

		}
	}

	public function add_preconnect_link() {
		// Skip preconnect if the script won't load on this page.
		if ( false === $this->should_load_script ) {
			return;
		}
		$cdn_key         = $this->settings->get( 'swarmify_cdn_key' );
		$swarmify_status = $this->settings->get( 'swarmify_status' );
		if ( 'on' !== $swarmify_status || '' === $cdn_key ) {
			return;
		}
		echo '<link rel="preconnect" href="https://assets.swarmcdn.com">';
	}

	// This fn exists primarily to appease the QIT linter rules, since we have 
	// to use wp_enqueue_script, which lacks support for custom <script> attrs
	public function add_async_swarmdetect_script_attributes( $tag, $handle ) {
		// Add async and data-cfasync attributes for linter
		if ( $this->swarmdetect_handle === $handle ) {
			$src_replacement = $this->use_beta_player
				? ' async fetchpriority="high" src='
				: ' async src=';
			return str_replace(
				array( ' src=', '<script ' ),
				array( $src_replacement, '<script data-cfasync="false" ' ),
				$tag
			);
		}

		return $tag;
	}

	public function add_swarmify_url_protocol( $protocols ) {
		$protocols[] = 'swarmify';
		return $protocols;
	}

	/**
	 * Scan Gutenberg static block output for <smartvideo> tags and register
	 * them with SchemaCollector (since Gutenberg has no server render_callback).
	 */
	public function collect_gutenberg_schema( $block_content, $block ) {
		if ( preg_match( '/<smartvideo[^>]+src="([^"]*)"/', $block_content, $src_match ) ) {
			$poster = '';
			if ( preg_match( '/poster="([^"]*)"/', $block_content, $poster_match ) ) {
				$poster = $poster_match[1];
			}
			SchemaCollector::add( $src_match[1], $poster );
		}
		return $block_content;
	}

	public function load_widget() {
		register_widget( 'Swarmify\Smartvideo\AdminWidget' );
	}


	/**
	 * Public entry point — kept for backward compatibility with the main plugin file.
	 * Hooks are now registered directly in the constructor.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Hooks registered in constructor via define_admin_hooks / define_public_hooks.
	}

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
