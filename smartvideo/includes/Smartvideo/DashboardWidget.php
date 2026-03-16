<?php

namespace Swarmify\Smartvideo;

/**
 * Adds a SmartVideo status widget to the WordPress admin dashboard.
 */
class DashboardWidget {

	/**
	 * Enqueue dashboard widget styles on the main dashboard screen only.
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();
		if ( $screen && 'dashboard' === $screen->id ) {
			wp_enqueue_style(
				'smartvideo-dashboard',
				plugin_dir_url( __FILE__ ) . 'css/smartvideo-dashboard.css',
				[],
				SWARMIFY_PLUGIN_VERSION
			);
		}
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register() {
		wp_add_dashboard_widget(
			'smartvideo_status',
			__( 'SmartVideo', 'swarmify' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Render the dashboard widget content.
	 */
	public function render() {
		$cdn_key   = get_option( 'swarmify_cdn_key', '' );
		$status    = get_option( 'swarmify_status', 'off' );
		$youtube   = get_option( 'swarmify_toggle_youtube', 'off' );
		$bgvideo   = get_option( 'swarmify_toggle_bgvideo', 'off' );
		$is_on     = 'on' === $status && '' !== $cdn_key;
		$has_key   = '' !== $cdn_key;

		$settings_url = admin_url( 'admin.php?page=SmartVideo.php' );
		?>
		<div class="sv-dash">
			<span class="sv-dash-badge <?php echo $is_on ? 'sv-dash-badge--on' : 'sv-dash-badge--off'; ?>">
				<span class="sv-dash-badge__dot"></span>
				<?php echo $is_on ? esc_html__( 'Active', 'swarmify' ) : esc_html__( 'Inactive', 'swarmify' ); ?>
			</span>

			<ul class="sv-dash-items">
				<li>
					<span class="sv-dash-label"><?php esc_html_e( 'CDN Key', 'swarmify' ); ?></span>
					<?php if ( $has_key ) : ?>
						<span class="sv-dash-val sv-dash-val--on"><?php esc_html_e( 'Connected', 'swarmify' ); ?></span>
					<?php else : ?>
						<span class="sv-dash-val sv-dash-val--warn"><?php esc_html_e( 'Missing', 'swarmify' ); ?></span>
					<?php endif; ?>
				</li>
				<li>
					<span class="sv-dash-label"><?php esc_html_e( 'YouTube/Vimeo auto-replace', 'swarmify' ); ?></span>
					<span class="sv-dash-val <?php echo 'on' === $youtube ? 'sv-dash-val--on' : 'sv-dash-val--off'; ?>">
						<?php echo 'on' === $youtube ? esc_html__( 'On', 'swarmify' ) : esc_html__( 'Off', 'swarmify' ); ?>
					</span>
				</li>
				<li>
					<span class="sv-dash-label"><?php esc_html_e( 'Background video optimization', 'swarmify' ); ?></span>
					<span class="sv-dash-val <?php echo 'on' === $bgvideo ? 'sv-dash-val--on' : 'sv-dash-val--off'; ?>">
						<?php echo 'on' === $bgvideo ? esc_html__( 'On', 'swarmify' ) : esc_html__( 'Off', 'swarmify' ); ?>
					</span>
				</li>
			</ul>

			<div class="sv-dash-actions">
				<a class="sv-dash-btn sv-dash-btn--primary" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'swarmify' ); ?> &rarr;</a>
				<a class="sv-dash-btn sv-dash-btn--secondary" href="https://support.swarmify.com/hc/en-us/categories/360003156514--FAQ" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Help & FAQs', 'swarmify' ); ?></a>
			</div>
		</div>
		<?php
	}
}
