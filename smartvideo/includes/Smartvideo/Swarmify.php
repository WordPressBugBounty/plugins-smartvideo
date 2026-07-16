<?php

namespace Swarmify\Smartvideo;

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

	/**
	 * Regex to detect YouTube/Vimeo iframes with a bare `src=` attribute.
	 *
	 * Uses `\s` before `src=` instead of `\b` so that lazy-loading attributes
	 * like `data-src=` and `data-lazy-src=` are not matched (the word boundary
	 * `\b` fires between `-` and `s`, which still matches those prefixed attrs).
	 */
	private const VIDEO_IFRAME_RE = '/<iframe[^>]*\ssrc=["\'][^"\']*(?:youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)[^"\']*["\']/';

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

	// Resolved once in enqueue to select stable vs beta script URL.
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

		// enable upload accelerator (admin + cron — plupload filters, AJAX handler, and chunk cleanup)
		if ( is_admin() || defined( 'DOING_CRON' ) ) {
			UploadAccelerator::get_instance();
		}

		$this->load_config_from_constants();

		if ( is_admin() ) {
			$this->define_admin_hooks();
		}

		$this->define_public_hooks();

		add_shortcode( 'smartvideo', array( $this, 'smartvideo_shortcode' ) );
	}


	/**
	 * Render the [smartvideo] shortcode into a <smartvideo> HTML tag.
	 *
	 * @param  array $atts Shortcode attributes (src, poster, dimensions, autoplay, etc.).
	 * @return string Rendered <smartvideo> markup, or an empty placeholder when src is missing.
	 */
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
			// Shortcode has no editor context — never render authoring
			// chrome to frontend visitors when src is empty.
			return '';
		}
		// Resolve dimensions from aspect ratio when not custom and no explicit dimensions.
		if ( '' !== $atts['aspect_ratio'] && 'custom' !== $atts['aspect_ratio'] ) {
			list( $width, $height ) = AspectRatio::resolve( $atts['aspect_ratio'] );
		} else {
			$width  = $atts['width'];
			$height = $atts['height'];
		}

		// Pull the 7 global plugin video defaults — used when the shortcode
		// attribute is omitted. Hardcoded fallbacks match Settings::DEFAULTS
		// in case the option row is missing entirely.
		$default_autoplay    = 'on' === $this->settings->get( 'swarmify_default_autoplay' );
		$default_muted       = 'on' === $this->settings->get( 'swarmify_default_muted' );
		$default_loop        = 'on' === $this->settings->get( 'swarmify_default_loop' );
		$default_controls    = 'on' === $this->settings->get( 'swarmify_default_controls' );
		$default_playsinline = 'on' === $this->settings->get( 'swarmify_default_playsinline' );
		$default_responsive  = 'on' === $this->settings->get( 'swarmify_default_responsive' );

		$sc_bool     = function ( $val, $default = false ) {
			return '' !== $val ? 'true' === $val : $default;
		};
		$preload_raw = '' !== $atts['preload'] ? $atts['preload'] : 'auto';
		$preload     = in_array( $preload_raw, array( 'auto', 'metadata', 'none' ), true ) ? $preload_raw : 'auto';

		// Build attributes -- only include non-empty values.
		$tag_attrs = [ 'src' => esc_url( $swarmify_url, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) ) ];
		if ( '' !== $width ) {
			$tag_attrs['width'] = esc_attr( $width );
		}
		if ( '' !== $height ) {
			$tag_attrs['height'] = esc_attr( $height );
		}
		if ( $sc_bool( $atts['responsive'], $default_responsive ) ) {
			$tag_attrs['class'] = 'swarm-fluid';
		}
		if ( '' !== $atts['poster'] ) {
			$tag_attrs['poster'] = esc_url( $atts['poster'] );
		}

		$booleans = [];
		if ( $sc_bool( $atts['autoplay'], $default_autoplay ) ) {
			$booleans[] = 'autoplay';
		}
		if ( $sc_bool( $atts['muted'], $default_muted ) ) {
			$booleans[] = 'muted';
		}
		if ( $sc_bool( $atts['loop'], $default_loop ) ) {
			$booleans[] = 'loop';
		}
		if ( $sc_bool( $atts['controls'], $default_controls ) ) {
			$booleans[] = 'controls';
		}
		if ( $sc_bool( $atts['playsinline'], $default_playsinline ) ) {
			$booleans[] = 'playsinline';
		}
		if ( 'auto' !== $preload ) {
			$tag_attrs['preload'] = esc_attr( $preload );
		}

		$parts = [];
		foreach ( $tag_attrs as $key => $val ) {
			$parts[] = $key . '="' . $val . '"';
		}
		$parts = array_merge( $parts, $booleans );

		SchemaCollector::add( $swarmify_url, '' !== $atts['poster'] ? $atts['poster'] : '' );

		return '<smartvideo ' . implode( ' ', $parts ) . '></smartvideo>';
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

		// Per-page disable toggle. register_meta is registered in public hooks
		// (REST API requests are not is_admin()) — only the classic-editor
		// meta box + save handler belong here.
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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_swarmify_script_admin' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_async_swarmdetect_script_attributes' ], 10, 2 );

		// This should be an admin hook really, but REST API calls return false for is_admin()
		add_action( 'rest_api_init', [ $this->settings, 'register_plugin_settings_routes' ] );

		// Per-page disable toggle: register_post_meta needs to fire on REST requests
		// too (Gutenberg sidebar reads/writes _smartvideo_disabled via the REST API).
		add_action( 'init', [ $this->post_meta, 'register_meta' ] );

		add_action( 'widgets_init', [ $this, 'load_widget' ] );

		// Collect schema from Gutenberg static block output (no server-side render_callback).
		add_filter( 'render_block_smartvideo/block-smartvideo-guten', [ $this, 'collect_gutenberg_schema' ], 10, 2 );
		add_filter( 'render_block_smartvideo/smartvideo', [ $this, 'collect_gutenberg_schema' ], 10, 2 );
	}



	/**
	 * Determine whether the current page needs the SmartVideo script.
	 * Runs on template_redirect so the queried object is available.
	 *
	 * Gate of record for frontend assets: any new frontend enqueue must
	 * consult $this->should_load_script (see enqueue_swarmify_script()).
	 */
	public function check_should_load_script() {
		$is_singular = is_singular();
		// Per-page disable only applies to singular pages (not archives).
		$is_disabled = $is_singular && PostMeta::is_disabled();

		// Conditional loading modes:
		//   'off'      — always load (default)
		//   'standard' — load on pages with any video content
		//   'strict'   — load only when enabled features will act on the content
		$mode = $this->settings->get( 'swarmify_toggle_conditional_loading' );

		// Feature flags — strict mode needs to know which content
		// types the player will convert, not just explicit SmartVideo tags.
		$auto_yt  = 'on' === $this->settings->get( 'swarmify_toggle_youtube' );
		$bg_video = 'on' === $this->settings->get( 'swarmify_toggle_bgvideo' );

		// Build the list of content to scan — singular pages use the queried
		// object; archive/index pages check every post in the main query.
		$contents = array();
		$post_id  = 0;
		if ( $is_singular ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$contents[] = get_post_field( 'post_content', $post->ID );
			}
			$post_id = get_queried_object_id();
		} else {
			global $wp_query;
			if ( ! empty( $wp_query->posts ) ) {
				foreach ( $wp_query->posts as $p ) {
					$contents[] = $p->post_content;
				}
			}
		}

		// A page using a synced pattern carries only `wp:block {"ref":N}` in its
		// raw content — resolve the referenced patterns so they get scanned too.
		$contents = self::expand_block_refs( $contents );

		// Page builders store content in postmeta, not post_content. Gather the
		// raw builder data here (only when its builder is active) and let
		// evaluate_should_load() make the decision.
		$elementor_data = '';
		$bb_data        = null;
		$bricks_data    = null;
		if ( $is_singular && $post_id ) {
			if ( class_exists( '\Elementor\Plugin' ) ) {
				$elementor_data = (string) get_post_meta( $post_id, '_elementor_data', true );
			}
			if ( class_exists( 'FLBuilder' ) ) {
				$bb_raw  = get_post_meta( $post_id, '_fl_builder_data', true );
				$bb_data = is_array( $bb_raw ) ? $bb_raw : null;
			}
			if ( defined( 'BRICKS_VERSION' ) ) {
				$bricks_raw  = get_post_meta( $post_id, '_bricks_page_content_2', true );
				$bricks_data = is_array( $bricks_raw ) ? $bricks_raw : null;
			}
		}

		$this->should_load_script = $this->evaluate_should_load(
			$contents,
			$mode,
			$auto_yt,
			$bg_video,
			$is_disabled,
			$elementor_data,
			$bb_data,
			$bricks_data
		);
	}

	/**
	 * Append the content of synced patterns (wp:block refs) to the scan list.
	 *
	 * Patterns can nest, so newly-resolved content is queued for another pass;
	 * the seen-set and cap bound the work on pathological content.
	 *
	 * @param string[] $contents Post-content strings gathered for the scan.
	 * @return string[] Original contents plus each referenced pattern's content.
	 */
	protected static function expand_block_refs( array $contents ) {
		$seen  = array();
		$queue = $contents;
		while ( $queue ) {
			$content = array_shift( $queue );
			if ( ! is_string( $content ) ||
				! preg_match_all( '/wp:block\s+{[^}]*"ref"\s*:\s*(\d+)/', $content, $matches ) ) {
				continue;
			}
			foreach ( $matches[1] as $ref_id ) {
				if ( isset( $seen[ $ref_id ] ) || count( $seen ) >= 20 ) {
					continue;
				}
				$seen[ $ref_id ] = true;
				$ref_content     = get_post_field( 'post_content', (int) $ref_id );
				if ( is_string( $ref_content ) && '' !== $ref_content ) {
					$contents[] = $ref_content;
					$queue[]    = $ref_content;
				}
			}
		}
		return $contents;
	}

	/**
	 * Decide whether the swarmdetect script should load for the current request,
	 * given already-gathered context. Extracted from check_should_load_script()
	 * so the decision is directly callable and unit-testable without the main
	 * query or instance state; behavior matches the original hook.
	 *
	 * @param string[]   $contents       Post-content strings to scan.
	 * @param string     $mode           Conditional-loading mode: '', 'off', 'standard', 'strict'.
	 * @param bool       $auto_yt        YouTube auto-convert feature enabled.
	 * @param bool       $bg_video       Background-video auto-convert feature enabled.
	 * @param bool       $is_disabled    Per-page disable toggle (singular pages only).
	 * @param string     $elementor_data Raw _elementor_data JSON ('' when none / Elementor inactive).
	 * @param array|null $bb_data        _fl_builder_data node array (null when none / Beaver inactive).
	 * @param array|null $bricks_data    _bricks_page_content_2 element array (null when none / Bricks inactive).
	 * @return bool
	 */
	protected function evaluate_should_load( array $contents, $mode, $auto_yt, $bg_video, $is_disabled, $elementor_data = '', $bb_data = null, $bricks_data = null ) {
		// Per-page disable wins over everything.
		if ( $is_disabled ) {
			return false;
		}

		if ( 'off' === $mode ) {
			return true;
		}

		// Allow themes/plugins to force-load the script.
		if ( apply_filters( 'smartvideo_force_load_script', false ) ) {
			return true;
		}

		// Check for an active SmartVideo widget.
		if ( is_active_widget( false, false, 'smartvideo_widget' ) ) {
			return true;
		}

		// --- Content scan ---
		// Combined regex for SmartVideo markers (covers shortcodes, blocks, and custom element).
		// Single preg_match replaces 7 separate strpos/has_shortcode/has_block calls per post.
		static $smartvideo_marker_re = '/\[smartvideo[\s\]]|\[smartvideo_divi_module|wp:smartvideo\/|<smartvideo[\s>]/';
		static $video_url_re         = '/youtube\.com|youtu\.be|vimeo\.com/';
		static $video_element_re     = '/<video[\s>]/i';
		static $divi_video_re        = '/\[et_pb_video|wp:divi\/video/';
		// Standard-mode union: collapses the 5-OR preg_match chain to one engine
		// pass. /i is safe — all sub-patterns are already lowercase markers or
		// hostnames where case-insensitivity only adds matches (never removes).
		static $standard_union_re = '/youtube\.com|youtu\.be|vimeo\.com|wp:core\/embed|\[et_pb_video|wp:divi\/video|<iframe[^>]*\ssrc=["\'][^"\']*(?:youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)[^"\']*["\']|<video[\s>]/i';

		foreach ( $contents as $content ) {
			if ( preg_match( $smartvideo_marker_re, $content ) ) {
				return true;
			}

			// Strict mode: also check for content the player will
			// auto-convert, based on which features are enabled.
			if ( 'strict' === $mode ) {
				if ( $auto_yt && preg_match( $video_url_re, $content ) ) {
					return true;
				}
				if ( $bg_video &&
					( preg_match( $video_element_re, $content ) ||
						preg_match( $divi_video_re, $content ) ) ) {
					return true;
				}
			}

			// Standard mode: load on any video content (safe default).
			if ( 'standard' === $mode ) {
				if ( preg_match( $standard_union_re, $content ) ) {
					return true;
				}
				// A post-content block (query loops) renders OTHER posts'
				// content, which can't be scanned here — load, safe default.
				if ( preg_match( '/wp:post-content/', $content ) ) {
					return true;
				}
			}
		}

		// --- Builder metadata scan ---
		// Page builders store content in postmeta, not post_content. The raw
		// data was gathered by the caller (only for active builders); here we
		// check natively first, then fall back to a concatenated string scan.
		$fallback_data = array();

		// Elementor: JSON widget data from _elementor_data.
		if ( $elementor_data ) {
			// Parsed natively — don't add to $fallback_data to avoid false
			// positives from default field values.
			$el_decoded = json_decode( $elementor_data, true );
			if ( is_array( $el_decoded ) &&
				$this->scan_elementor_widgets( $el_decoded, $mode, $auto_yt, $bg_video ) ) {
				return true;
			}
		}

		// Beaver Builder: serialized module data from _fl_builder_data.
		if ( is_array( $bb_data ) ) {
			$fallback_data[] = $bb_data;
			foreach ( $bb_data as $node ) {
				if ( ! isset( $node->type ) || 'module' !== $node->type || ! isset( $node->settings->type ) ) {
					continue;
				}
				// BB may store either the file slug or the class name,
				// depending on the BB version and when the data was saved.
				if ( in_array( $node->settings->type, [ 'class-beaverbuilder-smartvideo', 'SmartVideo' ], true ) ) {
					return true;
				}
				// Native BB video module — check video_type against
				// enabled features, same pattern as Bricks.
				if ( 'video' === $node->settings->type ) {
					$vtype = $node->settings->video_type ?? '';
					if ( $bg_video && in_array( $vtype, [ 'media_library', '' ], true ) ) {
						return true;
					}
					if ( $auto_yt && 'embed' === $vtype ) {
						return true;
					}
					if ( 'standard' === $mode ) {
						return true;
					}
				}
			}
		}

		// Bricks: element array from _bricks_page_content_2.
		if ( is_array( $bricks_data ) ) {
			$fallback_data[] = $bricks_data;
			foreach ( $bricks_data as $element ) {
				if ( ! isset( $element['name'] ) ) {
					continue;
				}
				if ( 'smartvideo' === $element['name'] ) {
					return true;
				}
				// Native Bricks video element — stores YouTube ID
				// without a URL, so the string scan won't catch it.
				if ( 'video' === $element['name'] ) {
					$vtype = $element['settings']['videoType'] ?? '';
					if ( $bg_video && 'file' === $vtype ) {
						return true;
					}
					if ( $auto_yt && in_array( $vtype, [ 'youtube', 'vimeo' ], true ) ) {
						return true;
					}
					if ( 'standard' === $mode ) {
						return true;
					}
				}
			}
		}

		if ( $fallback_data ) {
			$meta_content = '';
			foreach ( $fallback_data as $data ) {
				$meta_content .= maybe_serialize( $data );
			}
			// Both modes: check for SmartVideo shortcodes/tags in
			// builder metadata (catches raw embeds in HTML blocks).
			if ( preg_match( $smartvideo_marker_re, $meta_content ) ) {
				return true;
			}

			// Strict mode: check for auto-convert targets
			// based on enabled features.
			if ( 'strict' === $mode ) {
				if ( $auto_yt && preg_match( $video_url_re, $meta_content ) ) {
					return true;
				}
				if ( $bg_video &&
					preg_match( $video_element_re, $meta_content ) ) {
					return true;
				}
			}

			// Standard mode: load on any video content (safe default).
			// Reuses $standard_union_re from the content scan above —
			// keeps both standard-mode paths in lock-step coverage.
			if ( 'standard' === $mode ) {
				if ( preg_match( $standard_union_re, $meta_content ) ) {
					return true;
				}
			}
		}

		return false;
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
	 * @param int    $depth    Current recursion depth.
	 * @return bool True if the script should load.
	 */
	private function scan_elementor_widgets( $elements, $mode, $auto_yt, $bg_video, $depth = 0 ) {
		// _elementor_data is DB-stored and attacker-plantable; cap recursion so a
		// pathological tree can't blow the PHP stack on public page renders.
		if ( $depth > 32 ) {
			return false;
		}
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

			// HTML, shortcode, text-editor, and the accordion/toggle/tabs
			// widgets (their WYSIWYG item content runs shortcodes on render)
			// can contain arbitrary video embeds. Scan their settings for
			// video content strings.
			if ( in_array( $widget_type, [ 'html', 'shortcode', 'text-editor', 'accordion', 'toggle', 'tabs' ], true ) ) {
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
						preg_match( self::VIDEO_IFRAME_RE, $settings_text ) ) {
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
				if ( $this->scan_elementor_widgets( $element['elements'], $mode, $auto_yt, $bg_video, $depth + 1 ) ) {
					return true;
				}
			}
		}
		return false;
	}

	public function enqueue_swarmify_script_admin( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		// Load in the block editor too: edit() appends a live <smartvideo> the
		// loader converts in-place (block is apiVersion 1, so the canvas isn't iframed).
		$this->enqueue_swarmify_script();
	}

	/**
	 * Enqueue the swarmdetect settings and script
	 */
	public function enqueue_swarmify_script() {
		$cdn_key         = $this->settings->get( 'swarmify_cdn_key' );
		$swarmify_status = $this->settings->get( 'swarmify_status' );

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

			$autoreplaceObject->youtube         = ( 'on' === $youtube );
			$autoreplaceObject->youtubecaptions = ( 'on' === $youtube_cc );
			$autoreplaceObject->videotag        = ( 'on' === $bgoptimize );

			$layout_status = ( 'on' === $layout ) ? 'iframe' : 'video';

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
				// Create the `watermark` subobject
				$watermarkObject          = new \stdClass();
				$watermarkObject->file    = $watermark;
				$watermarkObject->opacity = 0.75;
				$watermarkObject->xpos    = 100;
				$watermarkObject->ypos    = 100;

				// Store the `watermarkObject` in the `pluginsObject`
				$pluginsObject->watermark = $watermarkObject;
			}

			$swarmoptions    = array(
				'swarmcdnkey'       => $cdn_key,
				'autoreplace'       => $autoreplaceObject,
				'theme'             => $themeObject,
				'plugins'           => $pluginsObject,
				'iframeReplacement' => $layout_status,
			);
			$swarmoptions_js = 'var swarmoptions = ' . wp_json_encode( $swarmoptions ) . ';';

			$this->use_beta_player = 'on' === $this->settings->get( 'swarmify_toggle_beta_player' );
			$script_src            = $this->use_beta_player
				? 'https://assets.swarmcdn.com/beta/swarmcdn.js'
				: 'https://assets.swarmcdn.com/cross/swarmcdn.js';

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
			wp_register_style( 'smartvideo-frontend', false, array(), $this->version );
			wp_enqueue_style( 'smartvideo-frontend' );
			wp_add_inline_style( 'smartvideo-frontend',
				'smartvideo{display:block}'
				. 'smartvideo.swarm-fluid{width:100%;aspect-ratio:16/9}'
				. 'smartvideo:not(.swarm-fluid){max-width:100%}'
				. 'iframe.swarm-iframe'
				. '{ width: 100% !important; height: auto !important; aspect-ratio: auto 16/9; }'
			);

		}
	}

	/**
	 * Print preconnect/dns-prefetch link tags for the SwarmCDN asset host.
	 *
	 * @return void
	 */
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
		echo '<link rel="preconnect" href="https://assets.swarmcdn.com" crossorigin>' . "\n";
		echo '<link rel="dns-prefetch" href="https://assets.swarmcdn.com">' . "\n";
	}

	/**
	 * Add async and cache-plugin exclusion attributes to the swarmdetect <script> tag.
	 *
	 * Exists primarily to appease the QIT linter rules, since wp_enqueue_script
	 * lacks support for custom <script> attributes.
	 *
	 * @param  string $tag    Original <script> HTML tag.
	 * @param  string $handle Registered script handle being filtered.
	 * @return string Modified script tag for the swarmdetect handle, otherwise unchanged.
	 */
	public function add_async_swarmdetect_script_attributes( $tag, $handle ) {
		// Add async and cache-plugin exclusion attributes
		if ( $this->swarmdetect_handle === $handle ) {
			return str_replace(
				array( ' src=', '<script ' ),
				array( ' async src=', '<script data-cfasync="false" data-no-defer data-no-optimize ' ),
				$tag
			);
		}

		return $tag;
	}

	/**
	 * Scan Gutenberg static block output for <smartvideo> tags and register
	 * them with SchemaCollector (since Gutenberg has no server render_callback).
	 */
	public function collect_gutenberg_schema( $block_content, $block ) {
		if ( preg_match( '/<smartvideo[^>]+src="([^"]*)"/', $block_content, $src_match ) ) {
			$poster = '';
			if ( preg_match( '/poster="([^"]*)"/', $block_content, $poster_match ) ) {
				$poster = esc_url_raw( $poster_match[1] );
			}
			SchemaCollector::add( esc_url_raw( $src_match[1] ), $poster );
		}
		return $block_content;
	}

	/**
	 * Register the SmartVideo widget with WordPress.
	 *
	 * @return void
	 */
	public function load_widget() {
		register_widget( 'Swarmify\Smartvideo\AdminWidget' );
	}
}
