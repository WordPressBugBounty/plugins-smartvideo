<?php

namespace Swarmify\Smartvideo;

/**
 * Admin-side functionality: the settings page, classic-editor integration, and admin notices.
 */
class Admin {
	private const ADMIN_HANDLE = 'smartvideo-admin';

	/**
	 * WP script/style handle for the classic-editor bundle. Keep this exact
	 * string stable — it's a public handle other code may enqueue or dequeue by name.
	 */
	private const CLASSIC_EDITOR_HANDLE = 'SmartVideo-swarmify-admin';

	protected $plugin_name;
	protected $version;
	protected $settings;

	/**
	 * @since 1.0.0
	 */
	public function __construct( $plugin_name, $version, $settings ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->settings    = $settings;
	}

	/**
	 * Redirect to the SmartVideo settings page once, right after activation.
	 *
	 * @since 1.0.0
	 */
	public function activation_redirect() {
		if ( ! get_transient( 'smartvideo_activation_redirect_' . get_current_user_id() ) ) {
			return;
		}

		// Don't redirect on multisite bulk activation, WP-CLI, or AJAX/REST requests.
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_ajax() || defined( 'REST_REQUEST' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check to skip a redirect, no state change.
			return;
		}

		delete_transient( 'smartvideo_activation_redirect_' . get_current_user_id() );

		wp_safe_redirect( admin_url( 'admin.php?page=SmartVideo.php' ) );
		exit;
	}

	/**
	 * Register and enqueue admin scripts and styles for the SmartVideo settings page.
	 *
	 * @param  string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function register_scripts( $hook ) {
		if ( 'toplevel_page_SmartVideo' !== $hook ) {
			return;
		}

		// Warm the tier transient here — frontend renders read it via
		// get_cached() and never HTTP, so without this admin-side refresh
		// the tier would stay unknown forever (gating permanently fail-open).
		$account_tier = ( new AccountTier( $this->settings ) )->get();

		$script_path       = '/build/index.js';
		$script_asset_path = dirname( SMARTVIDEO_PLUGIN_FILE ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->version,
			);
		$script_url        = plugins_url( $script_path, SMARTVIDEO_PLUGIN_FILE );

		wp_register_script(
			self::ADMIN_HANDLE,
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_style(
			'smartvideo-google-fonts',
			'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
			array(),
			$this->version
		);

		wp_register_style(
			self::ADMIN_HANDLE,
			plugins_url( '/build/index.css', SMARTVIDEO_PLUGIN_FILE ),
			array( 'wp-components', 'smartvideo-google-fonts' ),
			$this->version
		);

		wp_enqueue_media(); // the settings page's media picker needs wp.media

		wp_enqueue_script( self::ADMIN_HANDLE );
		wp_set_script_translations( self::ADMIN_HANDLE, 'swarmify' );
		wp_enqueue_style( self::ADMIN_HANDLE );

		$current_user = wp_get_current_user();
		wp_localize_script(
			self::ADMIN_HANDLE,
			'smartvideoPlugin',
			array(
				'baseUrl'         => plugins_url( '', SMARTVIDEO_PLUGIN_FILE ),
				'assetUrl'        => plugins_url( '/assets', SMARTVIDEO_PLUGIN_FILE ),
				'settingsUrl'     => $this->settings->url(),
				'initialSettings' => $this->settings->get_all(),
				'playerScriptSrc' => Swarmify::player_script_src(
					'on' === $this->settings->get( 'swarmify_toggle_beta_player' ),
					'on' === $this->settings->get( 'swarmify_toggle_legacy_player' )
				),
				'accountTier'     => $account_tier,
				'version'         => $this->version,
				'textDomain'      => 'swarmify',
				// Prefills the support beacon's contact fields on swarmify.com.
				'userName'        => $current_user->display_name,
				'userEmail'       => $current_user->user_email,
			)
		);
	}

	/**
	 * Register the SmartVideo admin menu page.
	 *
	 * @since 1.0.0
	 */
	public function register_page() {

		// Copy of assets/icon.svg, modified for the admin menu.
		$menu_icon = <<<'EOSVG'
        <svg viewBox="0 0 47 47" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" version="1.1" width="47" height="47" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
            <path fill="#000" d="M 23.050781,0 23.044922,0.00390625 23.039062,0 Z m 20.988281,11.519531 v 23.041016 l -21,11.519531 L 2.0390625,34.560547 V 11.519531 L 23.044922,0.00390625 Z m -28.519531,1.910157 v 19.390624 c 0,1.999998 1.319689,2.869687 2.929688,1.929688 L 35.5,24.820312 c 1.619998,-0.939999 1.619998,-2.460391 0,-3.40039 L 18.449219,11.5 c -0.4025,-0.2375 -0.786563,-0.362188 -1.136719,-0.382812 -1.050468,-0.06188 -1.792969,0.805001 -1.792969,2.3125 z" />
        </svg>
EOSVG;

		add_menu_page(
			__( 'SmartVideo', 'swarmify' ),
			__( 'SmartVideo', 'swarmify' ),
			'manage_options',
			$this->plugin_name . '.php',
			array( $this, 'admin_display' ),
			'data:image/svg+xml;base64,' . base64_encode( $menu_icon ),
		);
	}

	/**
	 * Render the SmartVideo admin page wrapper for the React app.
	 *
	 * @return void
	 */
	public function admin_display() {
		?>
		<div class="wrap">
			<div id="smartvideo-admin-root"></div>
		</div>
		<?php
	}

	/**
	 * Whether the current admin screen is the block editor.
	 *
	 * @return bool
	 */
	private function is_block_editor_screen() {
		$screen = get_current_screen();
		return $screen && $screen->is_block_editor();
	}

	/**
	 * Enqueue classic editor styles on post edit screens.
	 *
	 * @param  string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_classic_editor_styles( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( $this->is_block_editor_screen() ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style( self::CLASSIC_EDITOR_HANDLE, plugin_dir_url( __FILE__ ) . 'css/swarmify-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Enqueue classic editor scripts and localize SmartVideo defaults.
	 *
	 * @param  string $hook Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_classic_editor_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( $this->is_block_editor_screen() ) {
			return;
		}

		wp_enqueue_script( self::CLASSIC_EDITOR_HANDLE, plugin_dir_url( __FILE__ ) . 'js/swarmify-admin.js', array( 'jquery', 'wp-color-picker' ), $this->version, false );

		wp_localize_script(
			self::CLASSIC_EDITOR_HANDLE,
			'smartvideoDefaults',
			array(
				'autoplay'    => ( 'on' === $this->settings->get( 'swarmify_default_autoplay' ) ),
				'muted'       => ( 'on' === $this->settings->get( 'swarmify_default_muted' ) ),
				'loop'        => ( 'on' === $this->settings->get( 'swarmify_default_loop' ) ),
				'controls'    => ( 'on' === $this->settings->get( 'swarmify_default_controls' ) ),
				'playsinline' => ( 'on' === $this->settings->get( 'swarmify_default_playsinline' ) ),
				'responsive'  => ( 'on' === $this->settings->get( 'swarmify_default_responsive' ) ),
			)
		);
	}

	/**
	 * Show Settings link on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=SmartVideo.php' ) ) . '" aria-label="' . esc_attr__( 'View SmartVideo settings', 'swarmify' ) . '">' . esc_html__( 'Settings', 'swarmify' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}




	/**
	 * Render dismissible admin notices when the CDN key is missing or features are disabled.
	 *
	 * @return void
	 */
	public function admin_notices() {
		// Don't show notices on the SmartVideo settings page itself.
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_SmartVideo' === $screen->id ) {
			return;
		}

		$cdn_key      = $this->settings->get( 'swarmify_cdn_key' );
		$status       = $this->settings->get( 'swarmify_status' );
		$settings_url = esc_url( admin_url( 'admin.php?page=SmartVideo.php' ) );

		if ( '' === $cdn_key ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: 1: opening <strong>, 2: closing </strong>, 3: opening <a> tag, 4: closing </a> */
					esc_html__( '%1$sSmartVideo%2$s needs your CDN key to work. %3$sSet it up now%4$s', 'swarmify' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				)
			);
		} elseif ( 'on' !== $status ) {
			printf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: 1: opening <strong>, 2: closing </strong>, 3: opening <a> tag, 4: closing </a> */
					esc_html__( '%1$sSmartVideo%2$s is currently disabled. %3$sEnable it%4$s to start optimizing your videos.', 'swarmify' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				)
			);
		} elseif ( 'on' !== $this->settings->get( 'swarmify_toggle_youtube' ) ) {
			printf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: 1: opening <strong>, 2: closing </strong>, 3: opening <a> tag, 4: closing </a> */
					esc_html__( '%1$sSmartVideo%2$s can automatically replace YouTube and Vimeo embeds with a faster, ad-free player. %3$sTurn it on%4$s', 'swarmify' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				)
			);
		}
	}

	/**
	 * Output the "Add SmartVideo" button for the classic editor media bar.
	 *
	 * @return void
	 */
	public function add_video_button() {
		if ( $this->is_block_editor_screen() ) {
			return;
		}
		echo '<a href="" class="button swarmify_add_button" onclick="event.preventDefault();document.getElementById(\'swarmify-dialog\').showModal();"><img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/smartvideo_icon.png" alt="">' . esc_html__( 'Add SmartVideo', 'swarmify' ) . '</a>';
	}

	/**
	 * Output the "Add SmartVideo" lightbox markup in the admin footer on post edit screens.
	 *
	 * @return void
	 */
	public function add_video_lightbox_html() {
		global $pagenow;
		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( $this->is_block_editor_screen() ) {
			return;
		}
		require __DIR__ . '/partials/add-video-lightbox-display.php';
	}
}
