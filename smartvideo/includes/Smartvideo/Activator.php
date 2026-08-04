<?php

namespace Swarmify\Smartvideo;

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Swarmify
 * @subpackage Swarmify/includes
 */
class Activator {

	/**
	 * Seed the default options and run one-time migrations when the plugin activates.
	 *
	 * @return void
	 */
	public static function activate() {
		// Sites deactivated across the update never reach the migration call in
		// Swarmify's constructor, so run it here — before the seeding below
		// creates the very options it keys off.
		self::maybe_backfill_conditional_loading();

		set_transient( 'smartvideo_activation_redirect_' . get_current_user_id(), true, 30 );

		// Fresh install (no options yet): stamp the migration version NOW,
		// before the defaults below exist. The upgrade migration first runs
		// one request after activation, by which point swarmify_status is
		// set — without this stamp it would mistake every fresh install for
		// an upgrade and show the player-update notice.
		if ( false === get_option( 'swarmify_status' ) ) {
			update_option( 'smartvideo_version', Swarmify::DB_VERSION );

			// Rounded corners are a fresh-install-only default: the seeding
			// below no-ops on options that exist, but upgrades never created
			// this row, so seeding it there would restyle live players. The
			// play-button radius is deliberately NOT seeded — unset follows
			// the player's per-shape default (hexagon 16, rectangle 8).
			add_option( 'swarmify_theme_cornerradius', '24' );
		}

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

		add_option( 'swarmify_default_autoplay', 'off' );
		add_option( 'swarmify_default_muted', 'off' );
		add_option( 'swarmify_default_loop', 'off' );
		add_option( 'swarmify_default_controls', 'on' );
		add_option( 'swarmify_default_playsinline', 'off' );
		add_option( 'swarmify_default_responsive', 'on' );

		// 'standard' loads the CDN script only on pages that contain a SmartVideo.
		add_option( 'swarmify_toggle_conditional_loading', 'standard' );

		add_option( 'swarmify_toggle_beta_player', 'off' );
		add_option( 'swarmify_toggle_legacy_player', 'on' );
		add_option( 'swarmify_toggle_facade', 'off' );

		// Presence marker so the conditional-loading backfill skips this fresh install.
		add_option( 'swarmify_plugin_version', SWARMIFY_PLUGIN_VERSION );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'swarm-cdn/swarmcdn.php' ) ) {
			deactivate_plugins( 'swarm-cdn/swarmcdn.php' );
		}
	}

	/**
	 * Reset conditional loading to 'off' for sites that updated through 2.3.0.
	 *
	 * 2.3.0 changed the default from 'off' to 'standard' (load the player only
	 * where a content scan finds video) with no upgrade hook, silently breaking
	 * playback on sites whose markup the scan cannot see. A stored 'standard'
	 * may be nothing more than what a 2.3.0 reactivation wrote, so it is reset
	 * along with the unset case — 'off' costs a script load where 'standard'
	 * costs playback. Any other value can only come from the settings screen,
	 * so it stands.
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
