<?php

namespace Swarmify\Smartvideo;

/**
 * The classic (WP_Widget) SmartVideo widget.
 *
 * @link       https://swarmify.com/?smartvideo_wordpress_plugin
 * @since      1.0.0
 *
 * @package    Swarmify
 * @subpackage Swarmify/public
 */

/**
 * Classic widget that renders a SmartVideo player in a widget area.
 *
 * @package    Swarmify
 * @subpackage Swarmify/public
 */
class AdminWidget extends \WP_Widget {

	/**
	 * @since    1.0.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'smartvideo_widget',
			'description' => __( 'SmartVideo Widget', 'swarmify'),
		);

		parent::__construct( 'smartvideo_widget', __( 'SmartVideo Widget', 'swarmify'), $widget_ops);
	}


	/**
	 * Render the widget on the front end.
	 *
	 * @param  array $args     Display arguments including before/after_widget and before/after_title.
	 * @param  array $instance Saved widget instance values.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		if (empty( $instance)) {
			$instance = array(
				'title'                 => '',
				'swarmify_url'          => '',
				'swarmify_poster'       => '',
				'swarmify_autoplay'     => '',
				'swarmify_muted'        => '',
				'swarmify_loop'         => '',
				'swarmify_controls'     => '',
				'swarmify_video_inline' => '',
				'swarmify_unresponsive' => '',
				'swarmify_height'       => '',
				'swarmify_width'        => '',
			);
		}
		$cdn_key         = get_option( 'swarmify_cdn_key');
		$swarmify_status = get_option( 'swarmify_status');
		$title           = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$swarmify_url    = $instance['swarmify_url'] ?? '';

		$swarmify_poster   = $instance['swarmify_poster'] ?? '';
		$swarmify_autoplay = intval( $instance['swarmify_autoplay'] ?? 0 );
		$swarmify_muted    = intval( $instance['swarmify_muted'] ?? 0 );
		$swarmify_loop     = intval( $instance['swarmify_loop'] ?? 0 );
		// Older widget instances may lack swarmify_controls; default it on.
		$swarmify_controls     = ( isset( $instance['swarmify_controls'] ) && '' !== $instance['swarmify_controls'] )
			? intval( $instance['swarmify_controls'] )
			: 1;
		$swarmify_video_inline = intval( $instance['swarmify_video_inline'] ?? 0 );
		$swarmify_unresponsive = intval( $instance['swarmify_unresponsive'] ?? 0 );
		$swarmify_height       = intval( $instance['swarmify_height'] ?? 720 );
		$swarmify_width        = intval( $instance['swarmify_width'] ?? 1280 );
		$errors                = array();
		if ('' === $cdn_key) {
			$errors[] = __( 'CDN Key field is required.', 'swarmify' );
		}
		if ('on' !== $swarmify_status) {
			$errors[] = __( 'SmartVideo is disabled.', 'swarmify' );
		}

		if ('' === $swarmify_url) {
			$errors[] = __( 'SmartVideo URL is missing.', 'swarmify' );
		}

		$inner_output = '';
		if (empty( $errors)) {
			$autoplay     = ( 1 === $swarmify_autoplay ? 'autoplay' : '' );
			$muted        = ( 1 === $swarmify_muted ? 'muted' : '' );
			$loop         = ( 1 === $swarmify_loop ? 'loop' : '' );
			$controls     = ( 1 === $swarmify_controls ? 'controls' : '' );
			$video_inline = ( 1 === $swarmify_video_inline ? 'playsinline' : '' );
			$unresponsive = ( 1 === $swarmify_unresponsive ? 'class="swarm-fluid"' : '' );

			\Swarmify\Smartvideo\SchemaCollector::add( $swarmify_url, $swarmify_poster ?: '' );

			$inner_output = '<smartvideo src="' . esc_url( $swarmify_url, array_merge( wp_allowed_protocols(), array( 'swarmify' ) ) ) . '" width="' . $swarmify_width . '" height="' . $swarmify_height . '" ' . $unresponsive . ' poster="' . esc_url( $swarmify_poster ) . '" ' . $autoplay . ' ' . $muted . ' ' . $loop . ' ' . $controls . ' ' . $video_inline . '></smartvideo>';
		} else {
			$inner_output = '<ul>';
			foreach ($errors as $error) {
				$inner_output .= '<li>' . $error . '</li>';
			}
			$inner_output .= '</ul>';
		}
		// The before/after wrapper args come from register_sidebar() and are
		// theme-trusted, so they are echoed unescaped.

		// Strip the Divi-specific "et_pb_widget" class that some themes inject
		// into before_widget.
		$before_widget = preg_replace( '/\bet_pb_widget\b/', '', $args['before_widget'] );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-controlled wrapper
		echo $before_widget;

		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-controlled wrapper
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// Allow the swarmify:// protocol for this output only — it is
		// deliberately kept out of the site-wide allowed protocols so user
		// content can't use it.
		echo wp_kses(
			$inner_output,
			array(
				'smartvideo' => array(
					'src'         => true,
					'width'       => true,
					'height'      => true,
					'class'       => true,
					'poster'      => true,
					'autoplay'    => true,
					'muted'       => true,
					'loop'        => true,
					'controls'    => true,
					'playsinline' => true,
				),
				'ul'         => array(),
				'li'         => array(),
			),
			array_merge( wp_allowed_protocols(), array( 'swarmify' ) )
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-controlled wrapper
		echo $args['after_widget'];
	}

	/**
	 * Render the widget configuration form in the admin.
	 *
	 * @param  array $instance Current saved widget instance values.
	 * @return void
	 */
	public function form( $instance ) {
		$title = isset( $instance['title']) ? $instance['title'] : '';
		$page  = isset( $instance['page']) ? $instance['page'] : '';
		require plugin_dir_path( __FILE__) . 'partials/swarmify-widget-display.php';
	}


	/**
	 * Sanitize widget settings before they are saved.
	 *
	 * @param  array $new_instance New settings submitted from the form.
	 * @param  array $old_instance Previously saved settings.
	 * @return array Sanitized instance values to persist.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                      = array();
		$instance['title']             = ! empty( $new_instance['title']) ? sanitize_text_field( $new_instance['title']) : '';
		$instance['swarmify_url']      = ! empty( $new_instance['swarmify_url']) ? esc_url_raw( $new_instance['swarmify_url']) : '';
		$instance['swarmify_poster']   = ! empty( $new_instance['swarmify_poster']) ? esc_url_raw( $new_instance['swarmify_poster']) : '';
		$instance['swarmify_autoplay'] = ! empty( $new_instance['swarmify_autoplay']) ? intval( $new_instance['swarmify_autoplay']) : 0;
		$instance['swarmify_muted']    = ! empty( $new_instance['swarmify_muted']) ? intval( $new_instance['swarmify_muted']) : 0;
		$instance['swarmify_loop']     = ! empty( $new_instance['swarmify_loop']) ? intval( $new_instance['swarmify_loop']) : 0;
		$instance['swarmify_controls'] = ! empty( $new_instance['swarmify_controls']) ? intval( $new_instance['swarmify_controls']) : 0;
		$instance['swarmify_height']   = ! empty( $new_instance['swarmify_height']) ? intval( $new_instance['swarmify_height']) : 720;
		$instance['swarmify_width']    = ! empty( $new_instance['swarmify_width']) ? intval( $new_instance['swarmify_width']) : 1280;

		$instance['swarmify_video_inline'] = ! empty( $new_instance['swarmify_video_inline']) ? intval( $new_instance['swarmify_video_inline']) : 0;
		$instance['swarmify_unresponsive'] = ! empty( $new_instance['swarmify_unresponsive']) ? intval( $new_instance['swarmify_unresponsive']) : 0;
		return $instance;
	}
}
