<?php

namespace Swarmify\Smartvideo;

/**
 * Smartvideo Settings Class
 */
class Settings {
    public const API_VERSION = 'v1';
    public const path = 'settings';

    private const DEFAULTS = [
		'swarmify_cdn_key'                    => '',
		'swarmify_status'                     => 'off',
		'swarmify_toggle_youtube'             => 'off',
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
		'swarmify_default_preload'            => 'auto',
		'swarmify_toggle_conditional_loading' => 'off',
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
        $this->plugin_name    = $plugin_name;
        $this->version        = $version;
        $this->setting_list   = array_keys( self::DEFAULTS );
    }

    public function url() {
        return rest_url( $this->plugin_name . '/' . self::API_VERSION . '/' . self::path );
    }

	/**
	 * Checks to see if it's on/off. Empty string is also accepted for backwards-compatibility
	 */
    function validate_onoff( $val, $request, $name ) {
		return 'on' === $val || 'off' === $val || '' === $val;
	}

	function sanitize_onoff( $val, $request, $name ) {
		if( '' === $val) {
			return 'off';
		}
		return $val;
	}

	private function update_rest_args() {
		$bool_param_callbacks = [
			'validate_callback' => [$this, 'validate_onoff'],
			'sanitize_callback' => [$this, 'sanitize_onoff'],
		];

		return [
			'swarmify_cdn_key' => [
				'validate_callback' => function( $param, $request, $key ) {
					// Accept empty string (clearing the key) or valid UUID format
					return is_string( $param ) && ( '' === $param || preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $param ) );
				}
			],
			'swarmify_status' => $bool_param_callbacks,
			'swarmify_toggle_youtube' => $bool_param_callbacks,
			'swarmify_toggle_youtube_cc' => $bool_param_callbacks,
			'swarmify_toggle_layout' => $bool_param_callbacks,
			'swarmify_toggle_bgvideo' => $bool_param_callbacks,
			'swarmify_theme_button' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return is_string( $param ) && in_array( $param, ["default", "rectangle", "circle"], true );
				}
			],
			'swarmify_toggle_uploadacceleration' => $bool_param_callbacks,
			'swarmify_toggle_schema' => $bool_param_callbacks,
			'swarmify_default_autoplay' => $bool_param_callbacks,
			'swarmify_default_muted' => $bool_param_callbacks,
			'swarmify_default_loop' => $bool_param_callbacks,
			'swarmify_default_controls' => $bool_param_callbacks,
			'swarmify_default_playsinline' => $bool_param_callbacks,
			'swarmify_default_responsive' => $bool_param_callbacks,
			'swarmify_default_preload' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return is_string( $param ) && in_array( $param, [ 'auto', 'metadata', 'none' ], true );
				}
			],
			'swarmify_theme_primarycolor' => [
				'validate_callback' => function ( $param, $request, $key ) {
					// Accept hex colors (3, 6, or 8 digit) and rgba/hsla strings from WP ColorPicker
					return is_string( $param ) && ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $param ) || preg_match( '/^(rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+))?\s*\)|hsla?\(\s*\d{1,3}\s*,\s*\d{1,3}%?\s*,\s*\d{1,3}%?\s*(,\s*(0|1|0?\.\d+))?\s*\))$/', $param ) );
				}
			],
			'swarmify_watermark' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return '' === $param || ( is_string( $param ) && esc_url_raw( $param ) === $param );
				}
			],
			'swarmify_ads_vasturl' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return '' === $param || ( is_string( $param ) && esc_url_raw( $param ) === $param );
				}
			],
			'swarmify_toggle_conditional_loading' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return is_string( $param ) && in_array( $param, [ 'off', 'standard', 'strict' ], true );
				}
			],
		];
	}

	/**
	 * Registers the API routes to get and set the plugin settings
	 * 
	 */
	public function register_plugin_settings_routes() {
		$rest_namespace = $this->plugin_name . "/" . self::API_VERSION;

		// Register the route to retrieve plugin settings
		register_rest_route( 
			$rest_namespace, 
			'settings', 
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [$this, 'get_plugin_settings'],
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
				'methods' => \WP_REST_Server::EDITABLE,
				'callback' => [$this, 'set_plugin_settings'],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args' => $this->update_rest_args(),
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
	function get_plugin_settings( $request ) {
		return new \WP_REST_Response( $this->get_all(), 200 );
	}

	/**
	 * Callback to update the plugin settings.
	 *
	 * @param WP_REST_Request $request The current REST request.
	 * @return WP_REST_Response
	 */
	function set_plugin_settings( $request ) {
		if ( $this->update( $request->get_params() ) ) {
			return new \WP_REST_Response( array( 'success' => true ), 200 );
		} else {
			return new \WP_REST_Response( array( 'success' => false ), 500 );
		}
	}

	public function get( $key ) {
		if ( null === $this->cache ) {
			$this->cache = [];
			foreach ( self::DEFAULTS as $k => $default ) {
				$this->cache[ $k ] = get_option( $k, $default );
			}
		}
		return $this->cache[ $key ] ?? ( self::DEFAULTS[ $key ] ?? '' );
	}

	function get_all() {
		if ( null === $this->cache ) {
			$this->get( $this->setting_list[0] );
		}
		return $this->cache;
	}

	function update( $options ) {
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
	function get_diagnostics() {
		$active_plugins = get_option( 'active_plugins', [] );

		// Known plugins that can interfere with SmartVideo.
		$known_conflicts = [
			'autoptimize/autoptimize.php'                   => 'Autoptimize — may combine or defer SmartVideo scripts',
			'wp-rocket/wp-rocket.php'                       => 'WP Rocket — may defer or delay SmartVideo scripts',
			'w3-total-cache/w3-total-cache.php'             => 'W3 Total Cache — may minify or combine SmartVideo scripts',
			'sg-cachepress/sg-cachepress.php'               => 'SiteGround Optimizer — may combine JS files',
			'litespeed-cache/litespeed-cache.php'           => 'LiteSpeed Cache — may defer or combine scripts',
			'async-javascript/async-javascript.php'         => 'Async JavaScript — may re-order script loading',
			'perfmatters/perfmatters.php'                   => 'Perfmatters — may defer or delay scripts',
		];

		$conflicts = [];
		foreach ( $known_conflicts as $plugin_path => $description ) {
			if ( in_array( $plugin_path, $active_plugins, true ) ) {
				$conflicts[] = $description;
			}
		}

		return new \WP_REST_Response(
			[
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
				'conflicts'   => $conflicts,
			],
			200
		);
	}
}
