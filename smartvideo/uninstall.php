<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://swarmify.com/
 * @since      1.0.0
 *
 * @package    Swarmify
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'swarmify_status' );
delete_option( 'swarmify_cdn_key' );
delete_option( 'swarmify_toggle_youtube' );
delete_option( 'swarmify_toggle_youtube_cc' );
delete_option( 'swarmify_toggle_layout' );
delete_option( 'swarmify_toggle_bgvideo' );
delete_option( 'swarmify_theme_button' );
delete_option( 'swarmify_toggle_uploadacceleration' );
delete_option( 'swarmify_theme_primarycolor' );
delete_option( 'swarmify_watermark' );
delete_option( 'swarmify_ads_vasturl' );
delete_option( 'swarmify_toggle_schema' );
delete_option( 'swarmify_default_autoplay' );
delete_option( 'swarmify_default_muted' );
delete_option( 'swarmify_default_loop' );
delete_option( 'swarmify_default_controls' );
delete_option( 'swarmify_default_playsinline' );
delete_option( 'swarmify_default_responsive' );
	delete_option( 'swarmify_default_preload' ); // older installs may still carry this row
delete_option( 'swarmify_toggle_conditional_loading' );
delete_option( 'swarmify_toggle_beta_player' );
delete_option( 'swarmify_plugin_version' );
delete_option( 'smartvideo_version' );
delete_option( 'smartvideo_show_player_notice' );
delete_option( 'swarmify_toggle_legacy_player' );
delete_option( 'swarmify_toggle_facade' );
delete_option( 'swarmify_theme_secondarycolor' );
delete_option( 'swarmify_theme_glasstint' );
delete_option( 'swarmify_theme_cornerradius' );
delete_option( 'swarmify_theme_button_radius' );
delete_option( 'swarmify_toggle_keyboard' );
delete_option( 'swarmify_keyboard_seekstep' );
delete_option( 'swarmify_toggle_kb_mute' );
delete_option( 'swarmify_toggle_kb_fullscreen' );
delete_option( 'swarmify_toggle_kb_numbers' );
delete_option( 'swarmify_toggle_kb_captions' );
delete_option( 'swarmify_watermark_opacity' );
delete_option( 'swarmify_watermark_position' );
delete_option( 'swarmify_toggle_ga' );
delete_option( 'swarmify_ga_interval' );
delete_option( 'swarmify_toggle_lazyload' );
delete_transient( 'smartvideo_account_tier' );
delete_metadata( 'user', 0, 'smartvideo_player_notice_dismissed', '', true );

// The activation-redirect transient is stored per user, so match by prefix
// rather than by name.
global $wpdb;
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_smartvideo_activation_redirect_' ) . '%', $wpdb->esc_like( '_transient_timeout_smartvideo_activation_redirect_' ) . '%' ) );

// Cached Vimeo thumbnails expire on their own, but there is one row per video.
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_sv_vimeo_thumb_' ) . '%', $wpdb->esc_like( '_transient_timeout_sv_vimeo_thumb_' ) . '%' ) );

delete_post_meta_by_key( '_smartvideo_disabled' );

wp_clear_scheduled_hook( 'swarmify_cleanup_chunks' );

// Upload chunks land under wp-content, or in the uploads directory where
// wp-content is read-only. Each blog has its own uploads directory.
$sv_chunks_dirs = array( WP_CONTENT_DIR . '/.swarmify-chunks' );

$sv_add_uploads_chunks_dir = function () use ( &$sv_chunks_dirs ) {
	$uploads = wp_get_upload_dir();
	if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
		$sv_chunks_dirs[] = $uploads['basedir'] . '/.swarmify-chunks';
	}
};
$sv_add_uploads_chunks_dir();

// Options and meta are per-site, so repeat the whole cleanup on every site.
if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		$sv_add_uploads_chunks_dir();

		delete_option( 'swarmify_status' );
		delete_option( 'swarmify_cdn_key' );
		delete_option( 'swarmify_toggle_youtube' );
		delete_option( 'swarmify_toggle_youtube_cc' );
		delete_option( 'swarmify_toggle_layout' );
		delete_option( 'swarmify_toggle_bgvideo' );
		delete_option( 'swarmify_theme_button' );
		delete_option( 'swarmify_toggle_uploadacceleration' );
		delete_option( 'swarmify_theme_primarycolor' );
		delete_option( 'swarmify_watermark' );
		delete_option( 'swarmify_ads_vasturl' );
		delete_option( 'swarmify_toggle_schema' );
		delete_option( 'swarmify_default_autoplay' );
		delete_option( 'swarmify_default_muted' );
		delete_option( 'swarmify_default_loop' );
		delete_option( 'swarmify_default_controls' );
		delete_option( 'swarmify_default_playsinline' );
		delete_option( 'swarmify_default_responsive' );
		delete_option( 'swarmify_toggle_conditional_loading' );
		delete_option( 'swarmify_default_preload' ); // older installs may still carry this row
		delete_option( 'swarmify_toggle_beta_player' );
		delete_option( 'swarmify_plugin_version' );
		delete_option( 'smartvideo_version' );
		delete_option( 'smartvideo_show_player_notice' );
		delete_option( 'swarmify_toggle_legacy_player' );
		delete_option( 'swarmify_toggle_facade' );
		delete_option( 'swarmify_theme_secondarycolor' );
		delete_option( 'swarmify_theme_glasstint' );
		delete_option( 'swarmify_theme_cornerradius' );
		delete_option( 'swarmify_theme_button_radius' );
		delete_option( 'swarmify_toggle_keyboard' );
		delete_option( 'swarmify_keyboard_seekstep' );
		delete_option( 'swarmify_toggle_kb_mute' );
		delete_option( 'swarmify_toggle_kb_fullscreen' );
		delete_option( 'swarmify_toggle_kb_numbers' );
		delete_option( 'swarmify_toggle_kb_captions' );
		delete_option( 'swarmify_watermark_opacity' );
		delete_option( 'swarmify_watermark_position' );
		delete_option( 'swarmify_toggle_ga' );
		delete_option( 'swarmify_ga_interval' );
		delete_option( 'swarmify_toggle_lazyload' );
		delete_transient( 'smartvideo_account_tier' );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_smartvideo_activation_redirect_' ) . '%', $wpdb->esc_like( '_transient_timeout_smartvideo_activation_redirect_' ) . '%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_sv_vimeo_thumb_' ) . '%', $wpdb->esc_like( '_transient_timeout_sv_vimeo_thumb_' ) . '%' ) );
		delete_post_meta_by_key( '_smartvideo_disabled' );
		wp_clear_scheduled_hook( 'swarmify_cleanup_chunks' );

		restore_current_blog();
	}
}

// Delete only plain files, so a directory someone else has put subdirectories
// in survives the rmdir.
foreach ( array_unique( $sv_chunks_dirs ) as $chunks_dir ) {
	if ( ! is_dir( $chunks_dir ) || is_link( $chunks_dir ) ) {
		continue;
	}
	$entries = @scandir( $chunks_dir );
	if ( is_array( $entries ) ) {
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$path = $chunks_dir . '/' . $entry;
			if ( is_link( $path ) || is_file( $path ) ) {
				@unlink( $path );
			}
		}
	}
	@rmdir( $chunks_dir );
}
