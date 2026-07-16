<?php

namespace Swarmify\Smartvideo;

/**
 * Smartvideo Settings Class
 */
class Settings {
	public const API_VERSION = 'v1';
	public const PATH        = 'settings';

	private const DEFAULTS = [
		'swarmify_cdn_key'                    => '',
		'swarmify_status'                     => 'on',
		'swarmify_toggle_youtube'             => 'on',
		'swarmify_toggle_youtube_cc'          => 'off',
		'swarmify_toggle_layout'              => 'on',
		'swarmify_toggle_bgvideo'             => 'off',
		'swarmify_theme_button'               => 'default',
		'swarmify_toggle_uploadacceleration'  => 'on',
		'swarmify_theme_primarycolor'         => '#ffde17',
		'swarmify_watermark'                  => '',
		'swarmify_ads_vasturl'                => '',
		'swarmify_toggle_schema'              => 'on',
		'swarmify_default_autoplay'           => 'off',
		'swarmify_default_muted'              => 'off',
		'swarmify_default_loop'               => 'off',
		'swarmify_default_controls'           => 'on',
		'swarmify_default_playsinline'        => 'off',
		'swarmify_default_responsive'         => 'on',
		'swarmify_toggle_conditional_loading' => 'standard',
		'swarmify_toggle_beta_player'         => 'off',
	];

	public $setting_list;

	private $cache = null;

	protected $plugin_name;
	protected $version;


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name  = $plugin_name;
		$this->version      = $version;
		$this->setting_list = array_keys( self::DEFAULTS );
	}

	/**
	 * Get the REST URL for the settings endpoint.
	 *
	 * @return string Fully qualified REST URL.
	 */
	public function url() {
		return rest_url( $this->plugin_name . '/' . self::API_VERSION . '/' . self::PATH );
	}

	/**
	 * Checks to see if it's on/off. Empty string is also accepted for backwards-compatibility
	 */
	public function validate_onoff( $val, $request, $name ) {
		return 'on' === $val || 'off' === $val || '' === $val;
	}

	/**
	 * Sanitize an on/off toggle value, treating empty string as 'off'.
	 *
	 * @param  string           $val     Submitted value.
	 * @param  \WP_REST_Request $request Current REST request.
	 * @param  string           $name    Parameter name.
	 * @return string 'on' or 'off'.
	 */
	public function sanitize_onoff( $val, $request, $name ) {
		if ( '' === $val) {
			return 'off';
		}
		return $val;
	}

	private function update_rest_args() {
		$bool_param_callbacks = [
			'validate_callback' => [ $this, 'validate_onoff' ],
			'sanitize_callback' => [ $this, 'sanitize_onoff' ],
		];

		$url_param_callbacks = [
			'validate_callback' => function ( $param, $request, $key ) {
				return '' === $param || ( is_string( $param ) && esc_url_raw( $param ) === $param );
			},
			'sanitize_callback' => 'esc_url_raw',
		];

		return [
			'swarmify_cdn_key'                    => [
				'validate_callback' => function ( $param, $request, $key ) {
					// Accept empty string (clearing the key) or valid UUID format
					return is_string( $param ) && ( '' === $param || preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $param ) );
				},
				'sanitize_callback' => function ( $param ) {
					return sanitize_text_field( trim( $param ) );
				},
			],
			'swarmify_status'                     => $bool_param_callbacks,
			'swarmify_toggle_youtube'             => $bool_param_callbacks,
			'swarmify_toggle_youtube_cc'          => $bool_param_callbacks,
			'swarmify_toggle_layout'              => $bool_param_callbacks,
			'swarmify_toggle_bgvideo'             => $bool_param_callbacks,
			'swarmify_theme_button'               => [
				'validate_callback' => function ( $param, $request, $key ) {
					return is_string( $param ) && ( '' === $param || in_array( $param, [ 'default', 'rectangle', 'circle' ], true ) );
				},
				'sanitize_callback' => function ( $param ) {
					return '' === $param ? self::DEFAULTS['swarmify_theme_button'] : sanitize_text_field( $param );
				},
			],
			'swarmify_toggle_uploadacceleration'  => $bool_param_callbacks,
			'swarmify_toggle_schema'              => $bool_param_callbacks,
			'swarmify_default_autoplay'           => $bool_param_callbacks,
			'swarmify_default_muted'              => $bool_param_callbacks,
			'swarmify_default_loop'               => $bool_param_callbacks,
			'swarmify_default_controls'           => $bool_param_callbacks,
			'swarmify_default_playsinline'        => $bool_param_callbacks,
			'swarmify_default_responsive'         => $bool_param_callbacks,
			'swarmify_theme_primarycolor'         => [
				'validate_callback' => function ( $param, $request, $key ) {
					// Accept empty string (means "use default color") or
					// hex colors (3, 6, or 8 digit) and rgba/hsla strings from WP ColorPicker
					return is_string( $param ) && ( '' === $param || preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $param ) || preg_match( '/^(rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+))?\s*\)|hsla?\(\s*\d{1,3}\s*,\s*\d{1,3}%?\s*,\s*\d{1,3}%?\s*(,\s*(0|1|0?\.\d+))?\s*\))$/', $param ) );
				},
				'sanitize_callback' => function ( $param ) {
					return '' === $param ? self::DEFAULTS['swarmify_theme_primarycolor'] : sanitize_text_field( $param );
				},
			],
			'swarmify_watermark'                  => $url_param_callbacks,
			'swarmify_ads_vasturl'                => $url_param_callbacks,
			'swarmify_toggle_conditional_loading' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return is_string( $param ) && ( '' === $param || in_array( $param, [ 'off', 'standard', 'strict' ], true ) );
				},
				'sanitize_callback' => function ( $param ) {
					return '' === $param ? self::DEFAULTS['swarmify_toggle_conditional_loading'] : sanitize_text_field( $param );
				},
			],
			'swarmify_toggle_beta_player'         => $bool_param_callbacks,
		];
	}

	/**
	 * Registers the API routes to get and set the plugin settings
	 */
	public function register_plugin_settings_routes() {
		$rest_namespace = $this->plugin_name . '/' . self::API_VERSION;

		// Register the route to retrieve plugin settings
		register_rest_route( 
			$rest_namespace, 
			'settings', 
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_plugin_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		// Register the route to update plugin settings
		register_rest_route(
			$rest_namespace,
			'settings',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'set_plugin_settings' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => $this->update_rest_args(),
			]
		);

		// Diagnostics endpoint for health checks.
		register_rest_route(
			$rest_namespace,
			'diagnostics',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_diagnostics' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}
	

	/**
	 * Callback to retrieve the plugin settings.
	 *
	 * @param WP_REST_Request $request The current REST request.
	 * @return WP_REST_Response
	 */
	public function get_plugin_settings( $request ) {
		return new \WP_REST_Response( $this->get_all(), 200 );
	}

	/**
	 * Callback to update the plugin settings.
	 *
	 * @param WP_REST_Request $request The current REST request.
	 * @return WP_REST_Response
	 */
	public function set_plugin_settings( $request ) {
		// Pull each known setting via get_param() so the registered
		// validate_callback/sanitize_callback in update_rest_args() actually
		// run — get_json_params() returns the raw decoded body and bypasses
		// the args pipeline.
		$params = [];
		foreach ( self::DEFAULTS as $key => $default ) {
			$value = $request->get_param( $key );
			if ( null !== $value ) {
				$params[ $key ] = $value;
			}
		}
		if ( $this->update( $params ) ) {
			return new \WP_REST_Response( array( 'success' => true ), 200 );
		} else {
			return new \WP_REST_Response( array( 'success' => false ), 500 );
		}
	}

	/**
	 * Get a single plugin setting value, falling back to the registered default.
	 *
	 * @param  string $key Option name.
	 * @return mixed  Option value, or default/empty string when unknown.
	 */
	public function get( $key ) {
		if ( null === $this->cache ) {
			$this->cache = [];
			foreach ( self::DEFAULTS as $k => $default ) {
				$this->cache[ $k ] = get_option( $k, $default );
			}
		}
		return $this->cache[ $key ] ?? ( self::DEFAULTS[ $key ] ?? '' );
	}

	/**
	 * Get all plugin settings as an associative array (lazy-loaded and cached).
	 *
	 * @return array<string, mixed> All known setting key/value pairs.
	 */
	public function get_all() {
		if ( null === $this->cache ) {
			$this->get( $this->setting_list[0] );
		}
		return $this->cache;
	}

	/**
	 * Persist a batch of settings to the options table.
	 *
	 * Only keys present in the registered setting list are saved. Returns true
	 * when every persisted key matches its requested value after the call.
	 *
	 * @param  array<string, mixed> $options Key/value pairs to persist.
	 * @return bool True on full success, false if any option failed to save.
	 */
	public function update( $options ) {
		$has_failure = false;
		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $this->setting_list, true ) ) {
				$result = update_option( $key, $value );
				// update_option returns false on failure OR when value is unchanged.
				// Only count as failure if value doesn't match after the call.
				if ( false === $result && get_option( $key ) !== $value ) {
					$has_failure = true;
				}
			}
		}
		$this->cache = null;
		return ! $has_failure;
	}

	/**
	 * Return diagnostic information for the health check panel.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_diagnostics() {
		$active_plugins = get_option( 'active_plugins', [] );

		// Known plugins that can interfere with SmartVideo.
		$known_conflicts = [
			'async-javascript/async-javascript.php'      => esc_html__( 'Async JavaScript -- may re-order script loading', 'swarmify' ),
			'autoptimize/autoptimize.php'                => esc_html__( 'Autoptimize -- may combine or defer SmartVideo scripts', 'swarmify' ),
			'breeze/breeze.php'                          => esc_html__( 'Cloudways Breeze -- may defer or combine scripts', 'swarmify' ),
			'cloudflare/cloudflare.php'                  => esc_html__( 'Cloudflare -- may rewrite CDN URLs or enable Rocket Loader script deferral', 'swarmify' ),
			'flying-press/flying-press.php'              => esc_html__( 'FlyingPress -- may delay script execution until user interaction', 'swarmify' ),
			'hummingbird-performance/wp-hummingbird.php' => esc_html__( 'Hummingbird -- may defer or combine scripts', 'swarmify' ),
			'jetpack-boost/jetpack-boost.php'            => esc_html__( 'Jetpack Boost -- may defer non-critical scripts', 'swarmify' ),
			'litespeed-cache/litespeed-cache.php'        => esc_html__( 'LiteSpeed Cache -- may defer or combine scripts', 'swarmify' ),
			'nitropack/main.php'                         => esc_html__( 'NitroPack -- may defer scripts and rewrite resource URLs', 'swarmify' ),
			'perfmatters/perfmatters.php'                => esc_html__( 'Perfmatters -- may defer or delay scripts', 'swarmify' ),
			'phastpress/phastpress.php'                  => esc_html__( 'Phast -- may bundle scripts and defer execution', 'swarmify' ),
			'powered-cache/powered-cache.php'            => esc_html__( 'Powered Cache -- may defer or combine scripts', 'swarmify' ),
			'sg-cachepress/sg-cachepress.php'            => esc_html__( 'SiteGround Optimizer -- may combine JS files', 'swarmify' ),
			'speed-optimizer/speed_optimizer.php'        => esc_html__( 'SiteGround Speed Optimizer -- may defer or combine scripts', 'swarmify' ),
			'swift-performance-lite/performance.php'     => esc_html__( 'Swift Performance -- may merge or defer scripts', 'swarmify' ),
			'w3-total-cache/w3-total-cache.php'          => esc_html__( 'W3 Total Cache -- may minify or combine SmartVideo scripts', 'swarmify' ),
			'wp-fastest-cache/wpFastestCache.php'        => esc_html__( 'WP Fastest Cache -- may combine JS files or defer loading', 'swarmify' ),
			'wp-optimize/wp-optimize.php'                => esc_html__( 'WP-Optimize -- may defer or minify scripts', 'swarmify' ),
			'wp-rocket/wp-rocket.php'                    => esc_html__( 'WP Rocket -- may defer or delay SmartVideo scripts', 'swarmify' ),
			'wp-super-cache/wp-cache.php'                => esc_html__( 'WP Super Cache -- may serve cached pages with stale script references', 'swarmify' ),
		];

		$conflicts = [];
		foreach ( $known_conflicts as $plugin_path => $description ) {
			if ( in_array( $plugin_path, $active_plugins, true ) ) {
				$conflicts[] = $description;
			}
		}

		return new \WP_REST_Response(
			[
				'php_version'         => PHP_VERSION,
				'wp_version'          => get_bloginfo( 'version' ),
				'plugin_version'      => SWARMIFY_PLUGIN_VERSION,
				'cdn_key_set'         => '' !== $this->get( 'swarmify_cdn_key' ),
				'status'              => $this->get( 'swarmify_status' ),
				'youtube_enabled'     => $this->get( 'swarmify_toggle_youtube' ),
				'conditional_loading' => $this->get( 'swarmify_toggle_conditional_loading' ),
				'theme_name'          => wp_get_theme()->get( 'Name' ),
				'is_multisite'        => is_multisite(),
				'php_memory_limit'    => ini_get( 'memory_limit' ),
				'wp_memory_limit'     => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'not set',
				'is_ssl'              => is_ssl(),
				'object_cache_active' => wp_using_ext_object_cache(),
				'max_execution_time'  => (int) ini_get( 'max_execution_time' ),
				'conflicts'           => $conflicts,
			],
			200
		);
	}
}
