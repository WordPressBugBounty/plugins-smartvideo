<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://swarmify.com/
 * @since      1.0.0
 *
 * @package    Swarmify
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all plugin options from wp_options.
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
	delete_option( 'swarmify_default_preload' ); // legacy row from installs that predate the option's removal
delete_option( 'swarmify_toggle_conditional_loading' );
delete_option( 'swarmify_toggle_beta_player' );
delete_option( 'swarmify_plugin_version' );

// Delete activation redirect transient (set with a per-user suffix, so use a
// wildcard delete since we can't know the user ID at uninstall time).
global $wpdb;
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_smartvideo_activation_redirect_' ) . '%', $wpdb->esc_like( '_transient_timeout_smartvideo_activation_redirect_' ) . '%' ) );

// Delete cached Vimeo oEmbed thumbnail transients (one row per rendered Vimeo
// video; self-expiring, but cleaned here so uninstall leaves no rows behind).
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_sv_vimeo_thumb_' ) . '%', $wpdb->esc_like( '_transient_timeout_sv_vimeo_thumb_' ) . '%' ) );

// Delete all per-post meta across all posts in one query.
delete_post_meta_by_key( '_smartvideo_disabled' );

// Clear the upload-accelerator chunk-cleanup cron event.
wp_clear_scheduled_hook( 'swarmify_cleanup_chunks' );

// Accumulator directories to sweep. wp-content is global, but the 2.3.3
// read-only fallback lives under uploads, which is per-blog on multisite — so
// each blog contributes its own while we are switched to it.
$sv_chunks_dirs = array( WP_CONTENT_DIR . '/.swarmify-chunks' );

/** Append the current blog's uploads accumulator, if uploads resolves. */
$sv_add_uploads_chunks_dir = function () use ( &$sv_chunks_dirs ) {
	$uploads = wp_get_upload_dir();
	if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
		$sv_chunks_dirs[] = $uploads['basedir'] . '/.swarmify-chunks';
	}
};
$sv_add_uploads_chunks_dir();

// Multisite: clean up each site in the network.
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
		delete_option( 'swarmify_default_preload' ); // legacy row from installs that predate the option's removal
		delete_option( 'swarmify_toggle_beta_player' );
		delete_option( 'swarmify_plugin_version' );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_smartvideo_activation_redirect_' ) . '%', $wpdb->esc_like( '_transient_timeout_smartvideo_activation_redirect_' ) . '%' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( '_transient_sv_vimeo_thumb_' ) . '%', $wpdb->esc_like( '_transient_timeout_sv_vimeo_thumb_' ) . '%' ) );
		delete_post_meta_by_key( '_smartvideo_disabled' );
		wp_clear_scheduled_hook( 'swarmify_cleanup_chunks' );

		restore_current_blog();
	}
}

// Remove the upload-accelerator accumulator directories collected above.
// Best-effort: leave a directory alone if a co-tenant or admin has placed
// unexpected files there.
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
