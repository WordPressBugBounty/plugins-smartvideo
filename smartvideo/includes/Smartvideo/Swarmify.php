<?php

namespace Swarmify\Smartvideo;

/**
 * @link       https://swarmify.com/?smartvideo_wordpress_plugin
 * @since      1.0.0
 *
 * @package    Swarmify
 * @subpackage Swarmify/includes
 */

/**
 * The core plugin class: settings, admin hooks, and public-facing hooks.
 *
 * @since      1.0.0
 * @package    Swarmify
 * @subpackage Swarmify/includes
 */


class Swarmify {

	/**
	 * Version the upgrade migration brings an install up to. The Activator
	 * stamps it on fresh installs so they are never mistaken for upgrades
	 * (the migration can't tell the difference on its own — it first runs
	 * one request AFTER activation, when the default options already exist).
	 */
	public const DB_VERSION = '2.4.0';

	/**
	 * Mirrors ASPECT_RATIOS in gutenberg/packages/block-library/src/embed/constants.js,
	 * widest first — core's thresholds decide the padding box, so these must stay in step.
	 *
	 * @var array<int, array{0: float, 1: string}>
	 */
	private const EMBED_ASPECT_RATIOS = [
		[ 2.33, 'wp-embed-aspect-21-9' ],
		[ 2.00, 'wp-embed-aspect-18-9' ],
		[ 1.78, 'wp-embed-aspect-16-9' ],
		[ 1.33, 'wp-embed-aspect-4-3' ],
		[ 1.00, 'wp-embed-aspect-1-1' ],
		[ 0.56, 'wp-embed-aspect-9-16' ],
		[ 0.50, 'wp-embed-aspect-1-2' ],
	];

	/**
	 * Regex to detect YouTube/Vimeo iframes with a bare `src=` attribute.
	 *
	 * Requires whitespace before `src=` so lazy-loading attributes like
	 * `data-src=` don't match.
	 */
	private const VIDEO_IFRAME_RE = '/<iframe[^>]*\ssrc=["\'][^"\']*(?:youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)[^"\']*["\']/';

	/**
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
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
	 * Set during template_redirect, once the queried content can be scanned.
	 *
	 * @var bool|null null = not yet determined
	 */
	protected $should_load_script = null;

	protected $swarmdetect_handle = 'smartvideo_swarmdetect';

	/**
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name ) {
		if ( defined( 'SWARMIFY_PLUGIN_VERSION' ) ) {
			$this->version = SWARMIFY_PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = $plugin_name;

		// Must run before any Settings::get(), which caches every option value.
		Activator::maybe_backfill_conditional_loading();

		$this->settings  = new Settings( $this->plugin_name, $this->version );
		$this->post_meta = new PostMeta();

		// Load the upload accelerator on cron runs too — it sweeps abandoned upload chunks.
		if ( is_admin() || defined( 'DOING_CRON' ) ) {
			UploadAccelerator::get_instance();
		}

		$this->run_upgrade_migration();

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
				'src'                => '',
				'poster'             => '',
				'height'             => '',
				'width'              => '',
				'aspect_ratio'       => '',
				'responsive'         => '',
				'autoplay'           => '',
				'muted'              => '',
				'loop'               => '',
				'controls'           => '',
				'playsinline'        => '',
				'preload'            => '',
				'overlay_enabled'    => '',
				'overlay_text'       => '',
				'overlay_url'        => '',
				'overlay_color'      => '',
				'overlay_align'      => '',
				'overlay_start'      => '',
				'overlay_end'        => '',
				'overlay_bg'         => 'true',
				'overlay_bg_color'   => '',
				'overlay_bg_opacity' => '',
			),
			$atts,
			'smartvideo'
		);
		$swarmify_url = $atts['src'];
		if ( empty( $swarmify_url ) ) {
			// Render nothing, not a placeholder — unlike the builder
			// modules, the shortcode can't tell it is being edited.
			return '';
		}
		if ( '' !== $atts['aspect_ratio'] && 'custom' !== $atts['aspect_ratio'] ) {
			list( $width, $height ) = AspectRatio::resolve( $atts['aspect_ratio'] );
		} else {
			$width  = $atts['width'];
			$height = $atts['height'];
		}

		$default_autoplay    = 'on' === $this->settings->get( 'swarmify_default_autoplay' );
		$default_muted       = 'on' === $this->settings->get( 'swarmify_default_muted' );
		$default_loop        = 'on' === $this->settings->get( 'swarmify_default_loop' );
		$default_controls    = 'on' === $this->settings->get( 'swarmify_default_controls' );
		$default_playsinline = 'on' === $this->settings->get( 'swarmify_default_playsinline' );
		$default_responsive  = 'on' === $this->settings->get( 'swarmify_default_responsive' );

		$sc_bool     = function ( $val, $default_value = false ) {
			return '' !== $val ? 'true' === $val : $default_value;
		};
		$preload_raw = '' !== $atts['preload'] ? $atts['preload'] : 'auto';
		$preload     = in_array( $preload_raw, array( 'auto', 'metadata', 'none' ), true ) ? $preload_raw : 'auto';

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

		$overlay_args = [
			'overlayEnabled'   => '' !== $atts['overlay_enabled'] ? filter_var( $atts['overlay_enabled'], FILTER_VALIDATE_BOOLEAN ) : false,
			'overlayText'      => $atts['overlay_text'],
			'overlayUrl'       => $atts['overlay_url'],
			'overlayColor'     => $atts['overlay_color'],
			'overlayAlign'     => $atts['overlay_align'],
			'overlayStart'     => $atts['overlay_start'],
			'overlayEnd'       => $atts['overlay_end'],
			'overlayBg'        => '' !== $atts['overlay_bg'] ? filter_var( $atts['overlay_bg'], FILTER_VALIDATE_BOOLEAN ) : true,
			'overlayBgColor'   => $atts['overlay_bg_color'],
			'overlayBgOpacity' => $atts['overlay_bg_opacity'],
		];

		$setup_array = OverlayMarkup::build(
			$overlay_args,
			'on' === $this->settings->get( 'swarmify_toggle_legacy_player' ),
			( new AccountTier( $this->settings ) )->get_cached()
		);
		if ( null !== $setup_array ) {
			$setup_json = wp_json_encode( $setup_array );
			if ( false !== $setup_json ) {
				$tag_attrs['data-swarm-setup'] = esc_attr( $setup_json );
			}
		}

		$parts = [];
		foreach ( $tag_attrs as $key => $val ) {
			$parts[] = $key . '="' . $val . '"';
		}
		$parts = array_merge( $parts, $booleans );

		SchemaCollector::add( $swarmify_url, '' !== $atts['poster'] ? $atts['poster'] : '' );

		$smartvideo = '<smartvideo ' . implode( ' ', $parts ) . '></smartvideo>';

		return Facade::wrap(
			$smartvideo,
			[
				'src'      => $swarmify_url,
				'poster'   => $atts['poster'],
				'width'    => $width,
				'height'   => $height,
				'autoplay' => $sc_bool( $atts['autoplay'], $default_autoplay ),
			],
			$this->settings
		);
	}

	/**
	 * Load any configuration defined by constants in the wp_config file
	 *
	 * @since    2.0.12
	 */
	private function load_config_from_constants() {

		if ( defined( 'SWARMIFY_CDN_KEY' ) ) {
			$key = constant( 'SWARMIFY_CDN_KEY' );
			if ( is_string( $key ) && preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key ) ) {
				if ( get_option( 'swarmify_cdn_key', '' ) !== $key ) {
					update_option( 'swarmify_cdn_key', $key );
				}
			}
		}
	}


	/**
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
		add_action( 'admin_notices', [ $this, 'maybe_show_player_notice' ] );
		add_action( 'wp_ajax_smartvideo_dismiss_player_notice', [ $this, 'dismiss_player_notice' ] );

		add_action( 'media_buttons', [ $admin, 'add_video_button' ], 15 );
		add_action( 'admin_footer', [ $admin, 'add_video_lightbox_html' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( SMARTVIDEO_PLUGIN_FILE ), [ $admin, 'plugin_action_links' ] );

		// Only the classic-editor half of the per-page disable toggle belongs
		// here; register_meta lives in the public hooks.
		add_action( 'add_meta_boxes', [ $this->post_meta, 'add_meta_box' ] );
		add_action( 'save_post', [ $this->post_meta, 'save_meta_box' ] );

		$dashboard_widget = new DashboardWidget();
		add_action( 'wp_dashboard_setup', [ $dashboard_widget, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ $dashboard_widget, 'enqueue_styles' ] );
	}


	/**
	 * @since    1.0.0
	 */
	private function define_public_hooks() {
		add_action( 'wp_head', [ $this, 'add_preconnect_link' ], 2 );
		add_action( 'wp_footer', [ 'Swarmify\Smartvideo\SchemaCollector', 'output_schema' ], 20 );
		add_action( 'template_redirect', [ $this, 'check_should_load_script' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_swarmify_script' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_swarmify_script_admin' ] );
		add_filter( 'wp_inline_script_attributes', [ $this, 'add_inline_swarmdetect_script_attributes' ] );
		add_filter( 'embed_oembed_html', [ __CLASS__, 'embed_aspect_ratio_html' ], 10, 2 );
		add_filter( 'render_block_core/embed', [ __CLASS__, 'embed_aspect_class_block' ], 10, 2 );

		// Admin-only, but registered here: REST requests are not is_admin().
		add_action( 'rest_api_init', [ $this->settings, 'register_plugin_settings_routes' ] );

		add_action( 'add_option_swarmify_cdn_key', [ 'Swarmify\Smartvideo\AccountTier', 'flush_cache' ] );
		add_action( 'update_option_swarmify_cdn_key', [ 'Swarmify\Smartvideo\AccountTier', 'flush_cache' ] );

		// Must fire on REST requests too — the Gutenberg sidebar reads and
		// writes the per-page disable toggle.
		add_action( 'init', [ $this->post_meta, 'register_meta' ] );

		add_action( 'widgets_init', [ $this, 'load_widget' ] );

		// Gutenberg blocks render statically, so their schema has to be read off the block output.
		add_filter( 'render_block_smartvideo/block-smartvideo-guten', [ $this, 'collect_gutenberg_schema' ], 10, 2 );
		add_filter( 'render_block_smartvideo/smartvideo', [ $this, 'collect_gutenberg_schema' ], 10, 2 );
		add_filter( 'render_block_smartvideo/block-smartvideo-guten', [ $this, 'render_block_add_cta' ], 11, 2 );
		add_filter( 'render_block_smartvideo/block-smartvideo-guten', [ $this, 'render_block_add_facade' ], 12, 2 );
		add_filter( 'render_block_smartvideo/smartvideo', [ $this, 'render_block_add_facade' ], 12, 2 );
	}



	/**
	 * Determine whether the current page needs the SmartVideo script.
	 * Runs on template_redirect so the queried object is available.
	 *
	 * Any new frontend enqueue must check $this->should_load_script
	 * (see enqueue_swarmify_script()).
	 */
	public function check_should_load_script() {
		$is_singular = is_singular();
		$is_disabled = $is_singular && PostMeta::is_disabled();

		// Conditional loading modes:
		//   'off'      — always load (default)
		//   'standard' — load on pages with any video content
		//   'strict'   — load only when enabled features will act on the content
		$mode = $this->settings->get( 'swarmify_toggle_conditional_loading' );

		// Strict mode also has to know which content the player will convert
		// on its own, not just explicit SmartVideo tags.
		$auto_yt  = 'on' === $this->settings->get( 'swarmify_toggle_youtube' );
		$bg_video = 'on' === $this->settings->get( 'swarmify_toggle_bgvideo' );

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

		// A synced pattern leaves only a `wp:block` ref in the post content,
		// so pull in what it points at.
		$contents = self::expand_block_refs( $contents );

		// Page builders store their content in postmeta, not post_content.
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
			$bricks_data,
			defined( '__BREAKDANCE_VERSION' )
		);
	}

	/**
	 * Append the content of synced patterns (wp:block refs) to the scan list.
	 *
	 * Patterns can nest, so newly-resolved content is queued for another pass.
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
	 * given already-gathered context.
	 *
	 * @param string[]   $contents       Post-content strings to scan.
	 * @param string     $mode           Conditional-loading mode: '', 'off', 'standard', 'strict'.
	 * @param bool       $auto_yt        YouTube auto-convert feature enabled.
	 * @param bool       $bg_video       Background-video auto-convert feature enabled.
	 * @param bool       $is_disabled    Per-page disable toggle (singular pages only).
	 * @param string     $elementor_data Raw _elementor_data JSON ('' when none / Elementor inactive).
	 * @param array|null $bb_data        _fl_builder_data node array (null when none / Beaver inactive).
	 * @param array|null $bricks_data    _bricks_page_content_2 element array (null when none / Bricks inactive).
	 * @param bool       $breakdance     Breakdance is running on this site.
	 * @return bool
	 */
	protected function evaluate_should_load( array $contents, $mode, $auto_yt, $bg_video, $is_disabled, $elementor_data = '', ?array $bb_data = null, ?array $bricks_data = null, $breakdance = false ) {
		// Per-page disable wins over everything.
		if ( $is_disabled ) {
			return false;
		}

		// Breakdance builds a page from documents this scan can't reach — other
		// posts, wp_options presets, site-wide custom code — so it fails open.
		if ( $breakdance ) {
			return true;
		}

		if ( 'off' === $mode ) {
			return true;
		}

		if ( apply_filters( 'smartvideo_force_load_script', false ) ) {
			return true;
		}

		if ( is_active_widget( false, false, 'smartvideo_widget' ) ) {
			return true;
		}

		// --- Content scan ---
		static $smartvideo_marker_re = '/\[smartvideo[\s\]]|\[smartvideo_divi_module|wp:smartvideo\/|<smartvideo[\s>]/';
		static $video_url_re         = '/youtube\.com|youtu\.be|vimeo\.com/';
		static $video_element_re     = '/<video[\s>]/i';
		static $divi_video_re        = '/\[et_pb_video|wp:divi\/video/';
		// The /i is safe: every sub-pattern is a lowercase marker or hostname.
		static $standard_union_re = '/youtube\.com|youtu\.be|vimeo\.com|wp:core\/embed|\[et_pb_video|wp:divi\/video|<iframe[^>]*\ssrc=["\'][^"\']*(?:youtube\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)[^"\']*["\']|<video[\s>]/i';

		foreach ( $contents as $content ) {
			if ( preg_match( $smartvideo_marker_re, $content ) ) {
				return true;
			}

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

			if ( 'standard' === $mode ) {
				if ( preg_match( $standard_union_re, $content ) ) {
					return true;
				}
				// A post-content block renders another post's content, which
				// isn't visible here, so load rather than guess.
				if ( preg_match( '/wp:post-content/', $content ) ) {
					return true;
				}
			}
		}

		// --- Builder metadata scan ---
		// Each builder is checked against its own parsed data first, then
		// whatever is left falls through to a string scan below.
		$fallback_data = array();

		if ( $elementor_data ) {
			// Kept out of $fallback_data — Elementor's stored default field
			// values would make the string scan fire on videos that aren't there.
			$el_decoded = json_decode( $elementor_data, true );
			if ( is_array( $el_decoded ) &&
				$this->scan_elementor_widgets( $el_decoded, $mode, $auto_yt, $bg_video ) ) {
				return true;
			}
		}

		if ( is_array( $bb_data ) ) {
			$fallback_data[] = $bb_data;
			foreach ( $bb_data as $node ) {
				if ( ! isset( $node->type ) || 'module' !== $node->type || ! isset( $node->settings->type ) ) {
					continue;
				}
				// Beaver Builder stores either the file slug or the class
				// name, depending on the version that saved the page.
				if ( in_array( $node->settings->type, [ 'class-beaverbuilder-smartvideo', 'SmartVideo' ], true ) ) {
					return true;
				}
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

		if ( is_array( $bricks_data ) ) {
			$fallback_data[] = $bricks_data;
			foreach ( $bricks_data as $element ) {
				if ( ! isset( $element['name'] ) ) {
					continue;
				}
				if ( 'smartvideo' === $element['name'] ) {
					return true;
				}
				// Bricks stores a bare YouTube ID with no URL, so the
				// string scan below would miss its own video element.
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
			if ( preg_match( $smartvideo_marker_re, $meta_content ) ) {
				return true;
			}

			if ( 'strict' === $mode ) {
				if ( $auto_yt && preg_match( $video_url_re, $meta_content ) ) {
					return true;
				}
				if ( $bg_video &&
					preg_match( $video_element_re, $meta_content ) ) {
					return true;
				}
			}

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
	 * Elementor keeps default field values (e.g. a YouTube URL) even when the
	 * widget is set to another video type, so this checks each widget's actual
	 * video_type rather than scanning the raw data as a string.
	 *
	 * @param array  $elements Elementor elements array (sections/columns/widgets).
	 * @param string $mode     'standard' or 'strict'.
	 * @param bool   $auto_yt  Whether YouTube auto-conversion is on.
	 * @param bool   $bg_video Whether background video conversion is on.
	 * @param int    $depth    Current recursion depth.
	 * @return bool True if the script should load.
	 */
	private function scan_elementor_widgets( $elements, $mode, $auto_yt, $bg_video, $depth = 0 ) {
		// Cap the depth so a hand-crafted _elementor_data tree can't blow the
		// PHP stack on a public page render.
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

			// These widgets can hold arbitrary video embeds — the
			// accordion/toggle/tabs ones run shortcodes in their item content.
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

			if ( ! empty( $element['settings']['background_video_link'] ) ) {
				if ( $bg_video || 'standard' === $mode ) {
					return true;
				}
			}

			if ( ! empty( $element['elements'] ) ) {
				if ( $this->scan_elementor_widgets( $element['elements'], $mode, $auto_yt, $bg_video, $depth + 1 ) ) {
					return true;
				}
			}
		}
		return false;
	}

	public function enqueue_swarmify_script_admin( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) || ! Admin::is_block_editor_screen() ) {
			return;
		}

		// The block editor renders a live <smartvideo> in the canvas, which needs the player.
		$this->enqueue_swarmify_script();
	}

	/**
	 * Resolve the player script URL for the legacy, beta, or stable channel.
	 *
	 * Legacy wins over beta, so the escape hatch reproduces pre-2.4 behavior
	 * whatever else is toggled. Every caller that needs the enqueued URL —
	 * including the admin status probe — must read it from here, or the two
	 * copies drift (2.3.x shipped a probe pointed at a file it never enqueued).
	 *
	 * @param bool $use_beta_player Whether the beta channel is enabled.
	 * @param bool $legacy_player   Whether the legacy escape hatch is enabled.
	 * @return string Absolute URL of the player script to enqueue.
	 */
	public static function player_script_src( $use_beta_player, $legacy_player = false ) {
		if ( $legacy_player ) {
			return 'https://assets.swarmcdn.com/cross/swarmcdn.js';
		}
		return $use_beta_player
			? 'https://assets.swarmcdn.com/beta/swarmcdn.js'
			: 'https://assets.swarmcdn.com/swarmcdn.js';
	}

	/**
	 * Build the inline loader that injects the player bundle into the head.
	 *
	 * Reproduces the swarmdetect shim's SWARMIFY_LOADED guard: sites that also
	 * carry a hand-pasted shim snippet would otherwise start two players — the
	 * two shims used to mutually exclude each other through this same flag.
	 *
	 * @param  string $script_src Absolute URL of the player bundle to inject.
	 * @return string JavaScript suitable for wp_add_inline_script().
	 */
	public static function player_loader_js( $script_src ) {
		// Unescaped slashes keep the URL readable and greppable in page source
		// (the release gate scripts match it there).
		$src_literal = wp_json_encode( $script_src, JSON_UNESCAPED_SLASHES );

		return '(function(){if(window.SWARMIFY_LOADED){return;}window.SWARMIFY_LOADED=true;'
			. 'var s=document.createElement("script");'
			. 's.src=' . $src_literal . ';'
			. 's.async=true;'
			. 's.setAttribute("data-cfasync","false");'
			. 's.setAttribute("data-no-defer","");'
			. 's.setAttribute("data-no-optimize","");'
			. 'document.head.appendChild(s);})();';
	}

	/**
	 * Stamp a YouTube/Vimeo oEmbed iframe with its own aspect ratio.
	 *
	 * The frontend stylesheet forces iframe.swarm-iframe to a 16:9 fallback,
	 * which pillarboxes vertical (Shorts) and 4:3 embeds whose oEmbed
	 * width/height are correct. An inline aspect-ratio from those attributes
	 * outranks the fallback, and the player copies attributes — style
	 * included — onto its replacement iframe.
	 *
	 * @since 2.4.1
	 *
	 * @param  string $html Cached oEmbed markup.
	 * @param  string $url  The embedded URL.
	 * @return string
	 */
	public static function embed_aspect_ratio_html( $html, $url ) {
		if ( ! is_string( $html ) || ! is_string( $url ) ) {
			return $html;
		}
		if ( ! preg_match( VideoUrl::YT_REGEX, $url )
			&& ! preg_match( '%^https?://(?:[a-z0-9-]+\.)*vimeo\.com/%i', $url ) ) {
			return $html;
		}
		$iframe = self::iframe_dimensions( $html );
		if ( null === $iframe ) {
			return $html;
		}
		[ $tag, $width, $height ] = $iframe;
		if ( false !== stripos( $tag, 'style=' ) ) {
			return $html;
		}
		return preg_replace(
			'/<iframe\b/i',
			'<iframe style="aspect-ratio:' . $width . '/' . $height . '"',
			$html,
			1
		);
	}

	/**
	 * Correct a stale wp-embed-aspect-* class on a core/embed block at render time.
	 *
	 * Gutenberg never regenerates a class already in the saved markup, and the padding box
	 * core builds from it beats the inline aspect-ratio embed_aspect_ratio_html() stamps.
	 *
	 * @since 2.4.2
	 *
	 * @param  string $block_content Rendered block markup.
	 * @param  array  $block         Parsed block. Unused.
	 * @return string
	 */
	public static function embed_aspect_class_block( $block_content, $block ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- render_block_{block-name} filter signature registered with accepted_args=2; WP always passes (content, block).
		if ( ! is_string( $block_content ) || false === strpos( $block_content, 'wp-embed-aspect-' ) ) {
			return $block_content;
		}
		// This video plugin must not rewrite unrelated providers' embed markup.
		if ( ! preg_match( self::VIDEO_IFRAME_RE, $block_content ) ) {
			return $block_content;
		}
		if ( ! preg_match( '/\bwp-embed-aspect-[0-9]+-[0-9]+\b/', $block_content, $current ) ) {
			return $block_content;
		}
		$iframe = self::iframe_dimensions( $block_content );
		if ( null === $iframe ) {
			return $block_content;
		}
		[ , $width, $height ] = $iframe;

		// Truncate like JS toFixed(2) rather than round(), whose pre-rounding turns 426x240
		// into 16:9 where core's getClassNames drops the classes instead.
		$ratio  = (float) sprintf( '%.2f', $width / $height );
		$wanted = null;
		$found  = false;
		foreach ( self::EMBED_ASPECT_RATIOS as $candidate ) {
			if ( $ratio >= $candidate[0] ) {
				$found = true;
				// Core drops the classes rather than scale to a ratio this far off.
				if ( $ratio - $candidate[0] <= 0.1 ) {
					$wanted = $candidate[1];
				}
				break;
			}
		}
		if ( ! $found || $current[0] === $wanted ) {
			return $block_content;
		}

		if ( null === $wanted ) {
			$corrected = preg_replace( '/\s*\bwp-has-aspect-ratio\b/', '', $block_content, 1 );
			return preg_replace( '/\s*' . preg_quote( $current[0], '/' ) . '\b/', '', $corrected, 1 );
		}
		return preg_replace( '/\b' . preg_quote( $current[0], '/' ) . '\b/', $wanted, $block_content, 1 );
	}

	/**
	 * Extract the first iframe's opening tag and its positive dimensions.
	 *
	 * @param  string $html Markup containing an iframe.
	 * @return array{0: string, 1: int, 2: int}|null
	 */
	private static function iframe_dimensions( $html ) {
		if ( ! preg_match( '/<iframe\b[^>]*>/i', $html, $tag )
			|| ! preg_match( '/\bwidth="(\d+)"/i', $tag[0], $w )
			|| ! preg_match( '/\bheight="(\d+)"/i', $tag[0], $h ) ) {
			return null;
		}
		$width  = (int) $w[1];
		$height = (int) $h[1];
		if ( $width < 1 || $height < 1 ) {
			return null;
		}
		return array( $tag[0], $width, $height );
	}

	/**
	 * Enqueue the swarmoptions payload and the player-bundle loader.
	 */
	public function enqueue_swarmify_script() {
		$cdn_key         = $this->settings->get( 'swarmify_cdn_key' );
		$swarmify_status = $this->settings->get( 'swarmify_status' );

		if ( 'on' === $swarmify_status && '' !== $cdn_key ) {
			// Breakdance canvas static previews reuse the facade stylesheet for the big-play button.
			// The iframe param is forgeable, so it only counts alongside edit permission.
			if ( function_exists( 'Breakdance\isRequestFromBuilderIframe' ) && \Breakdance\isRequestFromBuilderIframe()
				&& function_exists( 'Breakdance\Permissions\hasMinimumPermission' ) && \Breakdance\Permissions\hasMinimumPermission( 'edit' ) ) {
				$this->enqueue_facade_styles();
				return;
			}

			if ( ! is_admin() && false === $this->should_load_script ) {
				// Builder edit modes render as frontend pages, and their
				// unsaved content can't be scanned, so always load there.
				$in_builder = ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) ||
								( class_exists( '\FLBuilderModel' ) && \FLBuilderModel::is_builder_active() ) ||
								( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) ||
								( class_exists( '\Elementor\Plugin' ) && isset( $_GET['elementor-preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only builder-preview detection, no state change.
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

			$legacy_player = 'on' === $this->settings->get( 'swarmify_toggle_legacy_player' );

			if ( $legacy_player ) {
				// Configure legacy pre-2.4 `autoreplace` object
				$autoreplaceObject                  = new \stdClass();
				$autoreplaceObject->youtube         = ( 'on' === $youtube );
				$autoreplaceObject->youtubecaptions = ( 'on' === $youtube_cc );
				$autoreplaceObject->videotag        = ( 'on' === $bgoptimize );

				$layout_status = ( 'on' === $layout ) ? 'iframe' : 'video';

				// Configure legacy pre-2.4 `theme` object
				$themeObject = new \stdClass();
				if ( $theme_primarycolor ) {
					$themeObject->primaryColor = $theme_primarycolor;
				}
				// Anything else is left unset, which gives the player's default
				// hexagon button.
				if ( 'rectangle' === $theme_button || 'circle' === $theme_button ) {
					$themeObject->button = $theme_button;
				}

				// Configure legacy pre-2.4 `plugins` object
				$pluginsObject = new \stdClass();
				if ( $ads_vasturl && '' !== $ads_vasturl ) {
					$swarmadsObject           = new \stdClass();
					$swarmadsObject->adTagUrl = $ads_vasturl;
					$pluginsObject->swarmads  = $swarmadsObject;
				}
				if ( $watermark && '' !== $watermark ) {
					$watermarkObject          = new \stdClass();
					$watermarkObject->file    = $watermark;
					$watermarkObject->opacity = 0.75;
					$watermarkObject->xpos    = 100;
					$watermarkObject->ypos    = 100;
					$pluginsObject->watermark = $watermarkObject;
				}

				$swarmoptions = array(
					'swarmcdnkey'       => $cdn_key,
					'autoreplace'       => $autoreplaceObject,
					'theme'             => $themeObject,
					'plugins'           => $pluginsObject,
					'iframeReplacement' => $layout_status,
				);
			} else {
				// Cached-only read: frontend renders must never HTTP to the
				// tier endpoint; null (expired/unknown) fails open.
				$tier   = ( new AccountTier( $this->settings ) )->get_cached();
				$is_pro = ( 'pro' === $tier || null === $tier );

				$layout_status = ( 'on' === $layout ) ? 'iframe' : 'video';

				// Configure `autoreplace` object
				$autoreplaceObject                  = new \stdClass();
				$autoreplaceObject->youtube         = ( 'on' === $youtube );
				$autoreplaceObject->vimeo           = ( 'on' === $youtube ); // Vimeo mirrors YouTube
				$autoreplaceObject->youtubecaptions = ( 'on' === $youtube_cc );
				$autoreplaceObject->videotag        = ( 'on' === $bgoptimize );
				// The top-level `iframeReplacement` below is YouTube-only; Vimeo's
				// mode lives here, so the one toggle has to feed both.
				$autoreplaceObject->vimeoIframeReplacement = $layout_status;

				// Configure `theme` object
				$themeObject = new \stdClass();
				if ( $theme_primarycolor ) {
					$themeObject->primaryColor = $theme_primarycolor;
				}

				$secondarycolor = $this->settings->get( 'swarmify_theme_secondarycolor' );
				if ( '' !== $secondarycolor ) {
					$themeObject->secondaryColor = $secondarycolor;
				}

				$glasstint = $this->settings->get( 'swarmify_theme_glasstint' );
				if ( '' !== $glasstint ) {
					$themeObject->glassTint = (int) $glasstint;
				}

				$cornerradius = $this->settings->get( 'swarmify_theme_cornerradius' );
				if ( '' !== $cornerradius ) {
					$themeObject->cornerRadius = $cornerradius . 'px';
				}

				$button_radius = $this->settings->get( 'swarmify_theme_button_radius' );
				if ( '' !== $button_radius ) {
					$shape = $this->settings->get( 'swarmify_theme_button' );
					if ( '' === $shape || 'default' === $shape ) {
						$shape = 'hexagon';
					}
					$buttonObject               = new \stdClass();
					$buttonObject->shape        = $shape;
					$buttonObject->borderRadius = $button_radius . 'px';
					$themeObject->button        = $buttonObject;
				} elseif ( 'rectangle' === $theme_button || 'circle' === $theme_button ) {
					$themeObject->button = $theme_button;
				}

				// Configure `plugins` object
				$pluginsObject = new \stdClass();

				if ( $ads_vasturl && '' !== $ads_vasturl ) {
					$swarmadsObject           = new \stdClass();
					$swarmadsObject->adTagUrl = $ads_vasturl;
					$pluginsObject->swarmads  = $swarmadsObject;
				}

				if ( $watermark && '' !== $watermark ) {
					$watermarkObject       = new \stdClass();
					$watermarkObject->file = $watermark;

					$opt_opacity  = $this->settings->get( 'swarmify_watermark_opacity' );
					$opt_position = $this->settings->get( 'swarmify_watermark_position' );

					$is_touched = ( '' !== $opt_opacity || '' !== $opt_position );

					if ( $is_touched && $is_pro ) {
						$watermarkObject->opacity = ( '' !== $opt_opacity ) ? (int) $opt_opacity : 75;
						$pos_key                  = ( '' !== $opt_position ) ? $opt_position : 'bottom-right';
						$wm_pos_map               = [
							'top-left'     => [
								'xpos' => 0,
								'ypos' => 0,
							],
							'top-right'    => [
								'xpos' => 100,
								'ypos' => 0,
							],
							'bottom-left'  => [
								'xpos' => 0,
								'ypos' => 100,
							],
							'bottom-right' => [
								'xpos' => 100,
								'ypos' => 100,
							],
						];
						$pos                      = $wm_pos_map[ $pos_key ] ?? $wm_pos_map['bottom-right'];
						$watermarkObject->xpos    = $pos['xpos'];
						$watermarkObject->ypos    = $pos['ypos'];
					} else {
						$watermarkObject->opacity = 0.75;
						$watermarkObject->xpos    = 100;
						$watermarkObject->ypos    = 100;
					}

					$pluginsObject->watermark = $watermarkObject;
				}

				$toggle_keyboard = $this->settings->get( 'swarmify_toggle_keyboard' );
				if ( 'off' === $toggle_keyboard ) {
					$keyboardObject          = new \stdClass();
					$keyboardObject->enabled = false;
					$pluginsObject->keyboard = $keyboardObject;
				} else {
					$keyboard_deviations = [];

					$seekstep = $this->settings->get( 'swarmify_keyboard_seekstep' );
					if ( '' !== $seekstep && '5' !== (string) $seekstep ) {
						$keyboard_deviations['seekStep'] = (int) $seekstep;
					}

					$kb_mute = $this->settings->get( 'swarmify_toggle_kb_mute' );
					if ( 'off' === $kb_mute ) {
						$keyboard_deviations['enableMute'] = false;
					}

					$kb_fullscreen = $this->settings->get( 'swarmify_toggle_kb_fullscreen' );
					if ( 'off' === $kb_fullscreen ) {
						$keyboard_deviations['enableFullscreen'] = false;
					}

					$kb_numbers = $this->settings->get( 'swarmify_toggle_kb_numbers' );
					if ( 'off' === $kb_numbers ) {
						$keyboard_deviations['enableNumbers'] = false;
					}

					$kb_captions = $this->settings->get( 'swarmify_toggle_kb_captions' );
					if ( 'off' === $kb_captions ) {
						$keyboard_deviations['enableCaptions'] = false;
					}

					if ( ! empty( $keyboard_deviations ) ) {
						$pluginsObject->keyboard = (object) $keyboard_deviations;
					}
				}

				$swarmoptions = array(
					'swarmcdnkey'       => $cdn_key,
					'autoreplace'       => $autoreplaceObject,
					'theme'             => $themeObject,
					'plugins'           => $pluginsObject,
					'iframeReplacement' => $layout_status,
				);

				$toggle_ga = $this->settings->get( 'swarmify_toggle_ga' );
				if ( 'on' === $toggle_ga ) {
					$gaObject                         = new \stdClass();
					$ga_interval                      = $this->settings->get( 'swarmify_ga_interval' );
					$gaObject->percentsPlayedInterval = ( '' !== $ga_interval ) ? (int) $ga_interval : 10;
					$swarmoptions['ga']               = $gaObject;
				}

				$toggle_lazyload = $this->settings->get( 'swarmify_toggle_lazyload' );
				if ( '' !== $toggle_lazyload ) {
					$performanceObject           = new \stdClass();
					$performanceObject->lazyLoad = ( 'on' === $toggle_lazyload );
					$swarmoptions['performance'] = $performanceObject;
				}
			}

			$swarmoptions_js = 'var swarmoptions = ' . wp_json_encode( $swarmoptions ) . ';';

			$use_beta_player = 'on' === $this->settings->get( 'swarmify_toggle_beta_player' );
			$script_src      = self::player_script_src( $use_beta_player, $legacy_player );

			// Registered without a src: the handle carries the two inline
			// scripts (swarmoptions, then the loader) and prints no tag of its
			// own — the loader injects the bundle tag itself.
			wp_register_script( $this->swarmdetect_handle, false, array(), $this->version, false );
			wp_enqueue_script( $this->swarmdetect_handle );

			wp_add_inline_script( $this->swarmdetect_handle, $swarmoptions_js, 'before' );
			wp_add_inline_script( $this->swarmdetect_handle, self::player_loader_js( $script_src ) );

			// oEmbed gives YouTube/Vimeo iframes small fixed dimensions, which
			// they keep when swarmdetect leaves the iframe in place, so force
			// them responsive.
			wp_register_style( 'smartvideo-frontend', false, array(), $this->version );
			wp_enqueue_style( 'smartvideo-frontend' );
			wp_add_inline_style( 'smartvideo-frontend',
				'smartvideo{display:block}'
				. 'smartvideo.swarm-fluid{width:100%;aspect-ratio:16/9}'
				. 'smartvideo:not(.swarm-fluid){max-width:100%}'
				// Bricks 2.x containers are flex; its widthless element wrapper
				// shrinks to the fluid player's 0 intrinsic width.
				. '.brxe-smartvideo{width:100%}'
				. 'iframe.swarm-iframe'
				. '{ width: 100% !important; height: auto !important; aspect-ratio: auto 16/9; }'
			);

			$facade_active = ! $legacy_player && 'on' === $this->settings->get( 'swarmify_toggle_facade' );
			if ( $facade_active ) {
				$this->enqueue_facade_assets();
			}       
		}
	}

	/**
	 * Keep JS-delay optimizers away from the swarmoptions payload and loader.
	 *
	 * Cache plugins that defer or combine inline scripts would run the loader
	 * after the page settles (or hoist it above its swarmoptions payload), so
	 * both inline blocks carry the same opt-out attributes the external player
	 * tag used to get.
	 *
	 * @since 2.4.0
	 *
	 * @param  array $attributes Attributes for the inline script tag.
	 * @return array
	 */
	public function add_inline_swarmdetect_script_attributes( $attributes ) {
		$id = isset( $attributes['id'] ) ? $attributes['id'] : '';
		if ( $this->swarmdetect_handle . '-js-before' !== $id && $this->swarmdetect_handle . '-js-after' !== $id ) {
			return $attributes;
		}

		// A boolean true prints as a valueless attribute (WP's
		// wp_sanitize_script_attributes), matching what the old tag filter wrote.
		$attributes['data-cfasync']     = 'false';
		$attributes['data-no-defer']    = true;
		$attributes['data-no-optimize'] = true;
		// WP-Optimize's Delay JS excludes by src only, so an inline block can only opt out via this attribute.
		$attributes['data-no-delay-js'] = true;

		return $attributes;
	}

	/**
	 * Enqueue the poster-facade stylesheets.
	 *
	 * The vendored artifact is pinned to a swarmcdn-browser player build
	 * (assets/facade/PIN); facade-base.css is built from src/.
	 *
	 * @since 2.4.0
	 *
	 * @return array The build/facade.asset.php metadata.
	 */
	private function enqueue_facade_styles() {
		wp_enqueue_style(
			'smartvideo-facade-artifact',
			plugins_url( '/assets/facade/facade.84a3697.css', SMARTVIDEO_PLUGIN_FILE ),
			array(),
			$this->version
		);

		$asset_path = dirname( SMARTVIDEO_PLUGIN_FILE ) . '/build/facade.asset.php';
		$asset      = file_exists( $asset_path )
			? require $asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->version,
			);

		wp_enqueue_style(
			'smartvideo-facade',
			plugins_url( '/build/facade.css', SMARTVIDEO_PLUGIN_FILE ),
			array( 'smartvideo-facade-artifact' ),
			$asset['version']
		);

		return $asset;
	}

	/**
	 * Enqueue the poster-facade stylesheets and handoff script.
	 *
	 * @since 2.4.0
	 */
	private function enqueue_facade_assets() {
		$asset = $this->enqueue_facade_styles();

		wp_enqueue_script(
			'smartvideo-facade',
			plugins_url( '/build/facade.js', SMARTVIDEO_PLUGIN_FILE ),
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}

	/**
	 * Print preconnect/dns-prefetch link tags for the SwarmCDN asset host.
	 *
	 * @return void
	 */
	public function add_preconnect_link() {
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
	 * Scan Gutenberg static block output for <smartvideo> tags and register
	 * them with SchemaCollector. These blocks render statically, so their
	 * output is the only place the server sees the video.
	 */
	public function collect_gutenberg_schema( $block_content, $block ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- render_block_{block-name} filter signature registered with accepted_args=2; WP always passes (content, block).
		if ( preg_match( '/<smartvideo[^>]+src="([^"]*)"/', $block_content, $src_match ) ) {
			$src    = $src_match[1];
			$poster = '';
			if ( preg_match( '/poster="([^"]*)"/', $block_content, $poster_match ) ) {
				$poster = esc_url_raw( $poster_match[1] );
			}

			$added = false;
			if ( 0 === strpos( $src, 'swarmify://' ) ) {
				$resolved = isset( $block['attrs']['resolvedContentUrl'] ) ? $block['attrs']['resolvedContentUrl'] : '';
				if ( is_string( $resolved ) && '' !== $resolved ) {
					$escaped = esc_url_raw( $resolved );
					if ( '' !== $escaped && 0 === strpos( $escaped, 'http' ) ) {
						SchemaCollector::add( $escaped, $poster );
						$added = true;
					}
				}
			}

			if ( ! $added ) {
				SchemaCollector::add( esc_url_raw( $src ), $poster );
			}
		}
		return $block_content;
	}

	/**
	 * Inject CTA overlay settings into Gutenberg block output at render.
	 *
	 * @since 2.4.0
	 */
	public function render_block_add_cta( $block_content, $block ) {
		if ( false !== stripos( $block_content, 'data-swarm-setup' ) ) {
			return $block_content;
		}

		$legacy_mode = 'on' === $this->settings->get( 'swarmify_toggle_legacy_player' );
		$cached_tier = ( new AccountTier( $this->settings ) )->get_cached();

		$setup_array = OverlayMarkup::build( $block['attrs'] ?? [], $legacy_mode, $cached_tier );
		if ( null === $setup_array ) {
			return $block_content;
		}

		$setup_json = wp_json_encode( $setup_array );
		if ( false === $setup_json ) {
			return $block_content;
		}
		$setup_attr = esc_attr( $setup_json );

		// preg_replace_callback: the JSON is author-controlled text — as a
		// preg_replace() replacement string, `$`+digit in it would be eaten
		// as a backreference ("Get $5 off" → "Get  off").
		$replaced = preg_replace_callback(
			'/<smartvideo\b/i',
			static function () use ( $setup_attr ) {
				return '<smartvideo data-swarm-setup="' . $setup_attr . '"';
			},
			$block_content,
			1
		);

		return null !== $replaced ? $replaced : $block_content;
	}

	/**
	 * Wrap the Gutenberg block's <smartvideo> tag in the poster facade.
	 *
	 * @since 2.4.0
	 */
	public function render_block_add_facade( $block_content, $block ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- render_block_{block-name} filter signature registered with accepted_args=2; WP always passes (content, block).
		if ( ! preg_match( '/<smartvideo\b[^>]*><\/smartvideo>/i', $block_content, $tag_match ) ) {
			return $block_content;
		}
		$tag = $tag_match[0];

		$src = preg_match( '/\ssrc="([^"]*)"/i', $tag, $m ) ? $m[1] : '';
		if ( '' === $src ) {
			return $block_content;
		}
		$poster = preg_match( '/\sposter="([^"]*)"/i', $tag, $m ) ? $m[1] : '';
		$width  = preg_match( '/\swidth="([^"]*)"/i', $tag, $m ) ? $m[1] : '';
		$height = preg_match( '/\sheight="([^"]*)"/i', $tag, $m ) ? $m[1] : '';
		// The block's save() serializes the boolean attr as autoplay="" — match
		// both the bare and empty-value forms.
		$autoplay = (bool) preg_match( '/\sautoplay(?:="[^"]*")?(?=[\s>])/i', $tag );

		$wrapped = Facade::wrap(
			$tag,
			[
				'src'      => $src,
				'poster'   => $poster,
				'width'    => $width,
				'height'   => $height,
				'autoplay' => $autoplay,
			],
			$this->settings
		);
		if ( $wrapped === $tag ) {
			return $block_content;
		}

		return str_replace( $tag, $wrapped, $block_content );
	}

	/**
	 * Run version-gated upgrade migration.
	 *
	 * @since 2.4.0
	 */
	private function run_upgrade_migration() {
		$stored_version = get_option( 'smartvideo_version' );
		if ( ! $stored_version || version_compare( $stored_version, self::DB_VERSION, '<' ) ) {
			$beta_val = get_option( 'swarmify_toggle_beta_player' );
			if ( false !== $beta_val ) {
				update_option( 'swarmify_toggle_beta_player', 'off' );
			}

			// Staged rollout: the upgrade parks everyone on the legacy player
			// and lets them opt into the new one. Beta users already chose the
			// new player, so they are not sent back.
			update_option( 'swarmify_toggle_legacy_player', 'on' === $beta_val ? 'off' : 'on' );
			// swarmify_status exists on every install after its first
			// activation; fresh installs never reach here because the
			// Activator stamps smartvideo_version (see DB_VERSION).
			if ( false !== get_option( 'swarmify_status' ) ) {
				update_option( 'smartvideo_show_player_notice', 1 );
			}
			update_option( 'smartvideo_version', self::DB_VERSION );
		}
	}

	/**
	 * Show the one-time player-update notice to admins who haven't dismissed it.
	 *
	 * @since 2.4.0
	 */
	public function maybe_show_player_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! get_option( 'smartvideo_show_player_notice' ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), 'smartvideo_player_notice_dismissed', true ) ) {
			return;
		}
		$nonce = wp_create_nonce( 'smartvideo_dismiss_player_notice' );
		// The same migration that sets this flag moves ex-beta sites to the new
		// player, so the two cohorts need opposite copy.
		$message = 'on' === $this->settings->get( 'swarmify_toggle_legacy_player' )
			? __( 'SmartVideo keeps playing your videos on the classic player, so nothing changes on your site today. A faster new player is ready whenever you are — set "Player version" to Stable in SmartVideo settings to switch over.', 'swarmify' )
			: __( 'SmartVideo now plays your videos on the new player — the one you were testing as the beta player, no longer a beta. If you need the old behavior back, set "Player version" to Legacy in SmartVideo settings.', 'swarmify' );
		?>
		<div class="notice notice-info is-dismissible" id="smartvideo-player-notice" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<script>
		document.addEventListener( 'click', function ( e ) {
			var notice = document.getElementById( 'smartvideo-player-notice' );
			if ( ! notice || ! e.target.closest || ! e.target.closest( '#smartvideo-player-notice .notice-dismiss' ) ) {
				return;
			}
			var body = new URLSearchParams();
			body.append( 'action', 'smartvideo_dismiss_player_notice' );
			body.append( '_ajax_nonce', notice.getAttribute( 'data-nonce' ) );
			fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } ).catch( function () {} );
		} );
		</script>
		<?php
	}

	/**
	 * Persist per-user dismissal of the player-update notice.
	 *
	 * @since 2.4.0
	 */
	public function dismiss_player_notice() {
		check_ajax_referer( 'smartvideo_dismiss_player_notice' );
		if ( current_user_can( 'manage_options' ) ) {
			update_user_meta( get_current_user_id(), 'smartvideo_player_notice_dismissed', 1 );
		}
		wp_die();
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
