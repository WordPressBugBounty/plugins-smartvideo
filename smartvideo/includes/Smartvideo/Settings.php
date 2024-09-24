<?php

namespace Swarmify\Smartvideo;

/**
 * Smartvideo Settings Class
 */
class Settings {
    public const API_VERSION = 'v1';
    public const path = 'settings';

    public $setting_list = [
		'swarmify_cdn_key',
		'swarmify_status',
		'swarmify_toggle_youtube',
		'swarmify_toggle_youtube_cc',
		'swarmify_toggle_layout',
		'swarmify_toggle_bgvideo',
		'swarmify_theme_button',
		'swarmify_toggle_uploadacceleration',
		'swarmify_theme_primarycolor',
		'swarmify_watermark',
		'swarmify_ads_vasturl',
	];

    protected $plugin_name;
    protected $version;


    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
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
					return is_string( $param ) && preg_match( '/^[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}$/', $param );
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
			'swarmify_theme_primarycolor' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return is_string( $param ) && preg_match( '/^#[0-9a-f]{6}$/i', $param );
				}
			],
			'swarmify_watermark' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return esc_url_raw( $param ) === $param;
				}
			],
			'swarmify_ads_vasturl' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return esc_url_raw( $param ) === $param;
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

	function get_all() {
		$all_options = [];
		foreach ( $this->setting_list as $value ) {
			$all_options[ $value ] = get_option( $value );
		}

		return $all_options;
	}

	function update( $options ) {
		foreach ( $options as $key => $value ) {
			if ( in_array( $key, $this->setting_list ) ) {
				update_option( $key, $value );  
			}
		}
		return true;
	}
}
