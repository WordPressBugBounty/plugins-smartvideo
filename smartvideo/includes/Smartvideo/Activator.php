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

	/**
	 * Run plugin activation tasks.
	 *
	 * Sets the redirect transient, seeds default options, and deactivates the
	 * legacy swarm-cdn plugin if it is currently active.
	 *
	 * @return void
	 */
	public static function activate() {
		// Sites deactivated across the update never load the plugin on a normal
		// request, so the constructor's call never fires. Run it here first: it
		// keys off swarmify_status, which does not exist yet on a fresh install,
		// so this only ever migrates a genuine pre-existing site.
		self::maybe_backfill_conditional_loading();

		set_transient( 'smartvideo_activation_redirect_' . get_current_user_id(), true, 30 );

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

		// New installs default to 'standard' conditional loading (skip swarmdetect on
		// pages without video). Existing installs keep their current setting since
		// add_option() is a no-op when the option already exists.
		add_option( 'swarmify_toggle_conditional_loading', 'standard' );

		add_option( 'swarmify_toggle_beta_player', 'off' );

		// Marks the install as already carrying 2.3.1's option layout, so
		// maybe_backfill_conditional_loading() never fires on a fresh install.
		add_option( 'swarmify_plugin_version', SWARMIFY_PLUGIN_VERSION );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'swarm-cdn/swarmcdn.php' ) ) {
			deactivate_plugins( 'swarm-cdn/swarmcdn.php' );
		}
	}

	/**
	 * Undo the conditional-loading default 2.3.0 forced onto existing sites.
	 *
	 * 2.3.0 flipped this option's default from 'off' (always load the player) to
	 * 'standard' (load only where a content scan finds video). There is no
	 * upgrade hook — activate() does not run on an in-place update — so sites
	 * whose markup the scan cannot see (WPBakery, Oxygen, ACF, theme templates)
	 * stopped loading the player entirely.
	 *
	 * A stored 'standard' cannot be trusted as a deliberate choice: reactivating
	 * under 2.3.0 writes exactly that, and the result is indistinguishable from a
	 * fresh 2.3.0 install. Both are reset, because 'off' is the safe direction —
	 * it costs a script load on video-less pages, where 'standard' costs broken
	 * playback. Any other value can only come from the settings screen, so it
	 * stands.
	 *
	 * @return void
	 */
	public static function maybe_backfill_conditional_loading() {
		if ( false !== get_option( 'swarmify_plugin_version', false ) ) {
			return;
		}
		if ( false === get_option( 'swarmify_status', false ) ) {
			return;
		}

		$conditional = get_option( 'swarmify_toggle_conditional_loading', false );
		if ( false === $conditional || 'standard' === $conditional ) {
			update_option( 'swarmify_toggle_conditional_loading', 'off' );
		}

		// Presence-only marker — never version_compare() against it; nothing updates the value.
		add_option( 'swarmify_plugin_version', SWARMIFY_PLUGIN_VERSION );
	}
}
