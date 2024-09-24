<?php

namespace Swarmify\Smartvideo;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://swarmify.idevaffiliate.com/idevaffiliate.php?id=10275&url=48
 * @since      1.0.0
 *
 * @package    Swarmify
 * @subpackage Swarmify/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Swarmify
 * @subpackage Swarmify/public
 */
class AdminWidget extends \WP_Widget {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	// private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	// private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */

	public function __construct() {
		 $widget_ops = array(
			 'classname'   => 'smartvideo_widget',
			 'description' => __( 'SmartVideo Widget', 'swarmify'),
		 );

		 parent::__construct( 'smartvideo_widget', __( 'SmartVideo Widget', 'swarmify'), $widget_ops);
	}


	// Widgets
	public function widget( $args, $instance) {
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
		$title           = apply_filters( 'widget_title', $instance['title']);
		$output          = $args['before_widget'];
		if ( ! empty( $title)) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}
		$swarmify_url = $instance['swarmify_url'];

		$swarmify_poster       = $instance['swarmify_poster'];
		$swarmify_autoplay     = intval( $instance['swarmify_autoplay']);
		$swarmify_muted        = intval( $instance['swarmify_muted']);
		$swarmify_loop         = intval( $instance['swarmify_loop']);
		$swarmify_controls     = intval( $instance['swarmify_controls']);
		$swarmify_video_inline = intval( $instance['swarmify_video_inline']);
		$swarmify_unresponsive = intval( $instance['swarmify_unresponsive']);
		$swarmify_height       = intval( $instance['swarmify_height']);
		$swarmify_width        = intval( $instance['swarmify_width']);
		$errors                = array();
		if ('' === $cdn_key) {
			$errors[] = 'CDN Key field is required.';
		}
		if ('on' !== $swarmify_status) {
			$errors[] = 'SmartVideo is disabled.';
		}

		if ('' === $swarmify_url) {
			$errors[] = 'SmartVideo URL is missing.';
		}

		if (empty( $errors)) {
			// if ( ! empty( $swarmify_poster)) {
			// 	$poster = 'poster="' . esc_url( $swarmify_poster ) . '"';
			// } else {
			// 	$poster = '';
			// }

			$autoplay     = ( 1 === $swarmify_autoplay ? 'autoplay' : '' );
			$muted        = ( 1 === $swarmify_muted ? 'muted' : '' );
			$loop         = ( 1 === $swarmify_loop ? 'loop' : '' );
			$controls     = ( 1 === $swarmify_controls ? 'controls' : '' );
			$video_inline = ( 1 === $swarmify_video_inline ? 'playsinline' : '' );
			$unresponsive = ( 1 === $swarmify_unresponsive ? 'class="swarm-fluid"' : '' );

			$output .= '<smartvideo src="' . esc_url( $swarmify_url ) . '" width="' . $swarmify_width . '" height="' . $swarmify_height . '" ' . $unresponsive . ' poster="' . esc_url( $swarmify_poster ) . '" ' . $autoplay . ' ' . $muted . ' ' . $loop . ' ' . $controls . ' ' . $video_inline . '></smartvideo>';
		} else {
			$output .= '<ul>';
			foreach ($errors as $error) {
				$output .= '<li>' . $error . '</li>';
			}
			$output .= '</ul>';
		}
		$output .= $args['after_widget'];

		$output = str_replace( 'et_pb_widget', '', $output);

		echo wp_kses(
			$output, 
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
				'aside' => array(
					'id'    => true,
					'class' => true,
				),
				'ul' => array(),
				'li' => array(),
			)
		);
	}

	public function form( $instance ) {
		$title = isset( $instance['title']) ? $instance['title'] : '';
		$page  = isset( $instance['page']) ? $instance['page'] : '';
		require plugin_dir_path( __FILE__) . 'partials/swarmify-widget-display.php';
	}


	public function update( $new_instance, $old_instance ) {
		$instance                      = array();
		$instance['title']             = ! empty( $new_instance['title']) ? sanitize_text_field( $new_instance['title']) : '';
		$instance['swarmify_url']      = ! empty( $new_instance['swarmify_url']) ? $new_instance['swarmify_url'] : '';
		$instance['swarmify_poster']   = ! empty( $new_instance['swarmify_poster']) ? $new_instance['swarmify_poster'] : '';
		$instance['swarmify_autoplay'] = ! empty( $new_instance['swarmify_autoplay']) ? intval( $new_instance['swarmify_autoplay']) : 0;
		$instance['swarmify_muted']    = ! empty( $new_instance['swarmify_muted']) ? intval( $new_instance['swarmify_muted']) : 0;
		$instance['swarmify_loop']     = ! empty( $new_instance['swarmify_loop']) ? intval( $new_instance['swarmify_loop']) : 0;
		$instance['swarmify_controls'] = ! empty( $new_instance['swarmify_controls']) ? intval( $new_instance['swarmify_controls']) : 1;
		$instance['swarmify_height']   = ! empty( $new_instance['swarmify_height']) ? intval( $new_instance['swarmify_height']) : 720;
		$instance['swarmify_width']    = ! empty( $new_instance['swarmify_width']) ? intval( $new_instance['swarmify_width']) : 1280;

		if (in_array( 'swarmify_controls', $old_instance) && null === $old_instance['swarmify_controls']) {
			$instance['swarmify_controls'] = 1;
		}
		$instance['swarmify_video_inline'] = ! empty( $new_instance['swarmify_video_inline']) ? intval( $new_instance['swarmify_video_inline']) : 0;
		$instance['swarmify_unresponsive'] = ! empty( $new_instance['swarmify_unresponsive']) ? intval( $new_instance['swarmify_unresponsive']) : 0;
		if (in_array( 'swarmify_unresponsive', $old_instance) && null === $old_instance['swarmify_unresponsive']) {
			$instance['swarmify_unresponsive'] = 0;
		}
		return $instance;
	}
}
