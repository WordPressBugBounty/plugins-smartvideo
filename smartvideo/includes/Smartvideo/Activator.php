<?php

namespace Swarmify\Smartvideo;

/**
 * Fired during plugin activation
 *
 * @link       https://swarmify.com/?smartvideo_wordpress_plugin
 * @since      1.0.0
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Swarmify
 * @subpackage Swarmify/includes
 */
class Activator {

	public static function activate() {
		set_transient( 'smartvideo_activation_redirect', true, 30 );

		add_option( 'swarmify_status', 'on' );
		add_option( 'swarmify_cdn_key', '' );

		add_option( 'swarmify_toggle_youtube', 'on' );
		add_option( 'swarmify_toggle_youtube_cc', 'off' );
		add_option( 'swarmify_toggle_layout', 'on' );
		add_option( 'swarmify_toggle_bgvideo', 'off' );
		add_option( 'swarmify_theme_button', 'default' );
		add_option( 'swarmify_toggle_uploadacceleration', 'on' );
		add_option( 'swarmify_theme_primarycolor', '#ffde17' );
		add_option( 'swarmify_watermark', '' );
		add_option( 'swarmify_ads_vasturl', '' );

		add_option( 'swarmify_toggle_schema', 'on' );

		// Global video defaults.
		add_option( 'swarmify_default_autoplay', 'off' );
		add_option( 'swarmify_default_muted', 'off' );
		add_option( 'swarmify_default_loop', 'off' );
		add_option( 'swarmify_default_controls', 'on' );
		add_option( 'swarmify_default_playsinline', 'off' );
		add_option( 'swarmify_default_responsive', 'on' );
		add_option( 'swarmify_default_preload', 'auto' );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'swarm-cdn/swarmcdn.php' ) ) {
			deactivate_plugins( 'swarm-cdn/swarmcdn.php' );
		}
	}

}
