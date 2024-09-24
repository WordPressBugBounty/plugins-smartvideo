<?php

namespace Swarmify\Smartvideo;

/**
 * Fired during plugin activation
 *
 * @link       https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=48
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

		if ( is_plugin_active( 'swarm-cdn/swarmcdn.php' ) ) {
			deactivate_plugins( 'swarm-cdn/swarmcdn.php' );
		}
	}

}
