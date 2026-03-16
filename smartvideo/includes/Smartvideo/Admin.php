<?php

namespace Swarmify\Smartvideo;

/**
 * Smartvideo Admin Class
 */
class Admin {
	protected $plugin_name;
	protected $version;
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $plugin_name, $version, $settings ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->settings    = $settings;
	}

	/**
	 * Load all necessary dependencies.
	 *
	 * @since 1.0.0
	 */
	public function activation_redirect() {
		if ( ! get_transient( 'smartvideo_activation_redirect' ) ) {
			return;
		}

		// Don't redirect on multisite bulk activation, WP-CLI, or AJAX/REST requests.
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_ajax() || defined( 'REST_REQUEST' ) ) {
			return;
		}

		delete_transient( 'smartvideo_activation_redirect' );

		wp_safe_redirect( admin_url( 'admin.php?page=SmartVideo.php' ) );
		exit;
	}

	public function register_scripts( $hook ) {
		if ( 'toplevel_page_SmartVideo' !== $hook ) {
			return;
		}

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
			$this->plugin_name,
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_style(
			'smartvideo-google-fonts',
			'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		wp_register_style(
			$this->plugin_name,
			plugins_url( '/build/index.css', SMARTVIDEO_PLUGIN_FILE ),
			array( 'wp-components', 'smartvideo-google-fonts' ),
			'2.1.0'
		);

		wp_enqueue_media(); // necessary to ensure wp.media exists in Js	

		wp_enqueue_script( $this->plugin_name );
		wp_enqueue_style( $this->plugin_name );

		wp_localize_script(
			$this->plugin_name,
			'smartvideoPlugin',
			array(
				'baseUrl'         => plugins_url( '', SMARTVIDEO_PLUGIN_FILE ),
				'assetUrl'        => plugins_url( '/assets', SMARTVIDEO_PLUGIN_FILE ),
				'settingsUrl'     => $this->settings->url(),
				'initialSettings' => $this->settings->get_all(),
				'version'         => $this->version,
				'textDomain'      => 'swarmify',
			)
		);
	}

	/**
	 * Register page in admin.
	 *
	 * @since 1.0.0
	 */
	public function register_page() {

		// base64-encoded from assets/icon.svg, but modified for the menu
		$menu_icon = <<<EOSVG
        <svg viewBox="0 0 47 47" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" version="1.1" width="47" height="47" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
            <path fill="#000" d="M 23.050781,0 23.044922,0.00390625 23.039062,0 Z m 20.988281,11.519531 v 23.041016 l -21,11.519531 L 2.0390625,34.560547 V 11.519531 L 23.044922,0.00390625 Z m -28.519531,1.910157 v 19.390624 c 0,1.999998 1.319689,2.869687 2.929688,1.929688 L 35.5,24.820312 c 1.619998,-0.939999 1.619998,-2.460391 0,-3.40039 L 18.449219,11.5 c -0.4025,-0.2375 -0.786563,-0.362188 -1.136719,-0.382812 -1.050468,-0.06188 -1.792969,0.805001 -1.792969,2.3125 z" />
        </svg>
EOSVG;

		add_menu_page(
			__( 'SmartVideo', 'swarmify' ),
			__( 'SmartVideo', 'swarmify' ),
			'manage_options',
			$this->plugin_name.'.php',
			// 'smartvideo-admin',
			array($this, 'admin_display'), 
			'data:image/svg+xml;base64,' . base64_encode( $menu_icon ),
		);
	}

	public function admin_display() {
		?>
		<div class="wrap">
			<div id="smartvideo-admin-root"></div>
		</div>
		<?php
	}

	public function enqueue_classic_editor_styles( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name . '-fancybox', plugin_dir_url( __FILE__ ) . 'css/jquery.fancybox.min.css', array(), $this->version, 'all' );

		// Add the color picker css file
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style( $this->plugin_name . '-swarmify-admin', plugin_dir_url( __FILE__ ) . 'css/swarmify-admin.css', array(), $this->version, 'all' );

	}

	public function enqueue_classic_editor_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name . '-fancybox', plugin_dir_url( __FILE__ ) . 'js/jquery.fancybox.min.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( $this->plugin_name . '-swarmify-admin', plugin_dir_url( __FILE__ ) . 'js/swarmify-admin.js', array( 'jquery', 'wp-color-picker' ), $this->version, false );
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




	public function admin_notices() {
		// Don't show notices on the SmartVideo settings page itself.
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_SmartVideo' === $screen->id ) {
			return;
		}

		$cdn_key = $this->settings->get( 'swarmify_cdn_key' );
		$status  = $this->settings->get( 'swarmify_status' );
		$settings_url = esc_url( admin_url( 'admin.php?page=SmartVideo.php' ) );

		if ( '' === $cdn_key ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p><strong>SmartVideo</strong> needs your CDN key to work. <a href="%s">Set it up now</a></p></div>',
				$settings_url
			);
		} elseif ( 'on' !== $status ) {
			printf(
				'<div class="notice notice-info is-dismissible"><p><strong>SmartVideo</strong> is currently disabled. <a href="%s">Enable it</a> to start optimizing your videos.</p></div>',
				$settings_url
			);
		} elseif ( 'on' !== $this->settings->get( 'swarmify_toggle_youtube' ) ) {
			printf(
				'<div class="notice notice-info is-dismissible"><p><strong>SmartVideo</strong> can automatically replace YouTube and Vimeo embeds with a faster, ad-free player. <a href="%s">Turn it on</a></p></div>',
				$settings_url
			);
		}
	}

	public function add_video_button() {
		echo '<a href="" data-fancybox data-src="#swarmify-modal-content" class="button swarmify_add_button"><img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/smartvideo_icon.png" alt="">' . esc_html__( 'Add SmartVideo', 'swarmify' ) . '</a>';
	}

	public function add_video_lightbox_html() {
		global $pagenow;
		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		require __DIR__ . '/partials/add-video-lightbox-display.php';
	}
}
