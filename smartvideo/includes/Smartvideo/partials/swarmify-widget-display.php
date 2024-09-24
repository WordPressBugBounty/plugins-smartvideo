<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	// Page Builders Styles & Scripts
if (
		// Elementor
		( array_key_exists( 'action', $_REQUEST) && 'elementor_ajax' === $_REQUEST['action'] )
		|| 
		// Beaver Builder
		array_key_exists( 'fl_builder', $_REQUEST)
	) {
		wp_enqueue_style( 'smartvideo_jquery_fancybox_css', dirname(plugin_dir_url(__DIR__)) . '/admin/css/jquery.fancybox.min.css', array(), SWARMIFY_PLUGIN_VERSION );
		wp_enqueue_style( 'smartvideo_swarmify_admin_css', dirname(plugin_dir_url(__DIR__)) . '/admin/css/swarmify-admin.css', array(), SWARMIFY_PLUGIN_VERSION );

		wp_enqueue_script( 'smartvideo_jquery_fancybox_js', dirname(plugin_dir_url(__DIR__)) . '/admin/js/jquery.fancybox.min.js', array('jquery'), SWARMIFY_PLUGIN_VERSION, false );
		wp_enqueue_script( 'smartvideo_jquery_inputmask_bundle_js', dirname(plugin_dir_url(__DIR__)) . '/admin/js/jquery.inputmask.bundle.js', array('jquery'), SWARMIFY_PLUGIN_VERSION, false );

		wp_enqueue_script( 'smartvideo_swarmify_admin_js', dirname(plugin_dir_url(__DIR__)) . '/admin/js/swarmify-admin.js', array('smartvideo_jquery_fancybox_js', 'smartvideo_jquery_inputmask_bundle_js'), SWARMIFY_PLUGIN_VERSION, false );


}

if (array_key_exists( 'fl_builder', $_REQUEST)) {
	echo '<style>
			.swarmify-widget-div .button{color: #555;
				border-color: #ccc;
				background: #e4e7ea;
				box-shadow: 0 1px 0 #ccc;
				vertical-align: top;
				font-weight:normal;
			}
			.swarmify-tabs{
				margin-bottom:20px;
			}
			.swarmify-tabs span{
				font-size:15px;
			}
			.swarmify_title{
				display: block!important;
			}
		</style>';
}
?>


<?php
	$swarmify_url = '';
if (isset( $instance['swarmify_url'])) {
	$swarmify_url = $instance['swarmify_url'];
}
	$swarmify_poster = '';
if (isset( $instance['swarmify_poster'])) {
	$swarmify_poster = $instance['swarmify_poster'];
}
	$swarmify_autoplay = 0;
if (isset( $instance['swarmify_autoplay'])) {
	$swarmify_autoplay = $instance['swarmify_autoplay'];
}
	$swarmify_muted = 0;
if (isset( $instance['swarmify_muted'])) {
	$swarmify_muted = $instance['swarmify_muted'];
}
	$swarmify_loop = 0;
if (isset( $instance['swarmify_loop'])) {
	$swarmify_loop = $instance['swarmify_loop'];
}
	$swarmify_controls = 1;
if (isset( $instance['swarmify_controls'])) {
	$swarmify_controls = $instance['swarmify_controls'];
}
	$swarmify_video_inline = 0;
if (isset( $instance['swarmify_video_inline'])) {
	$swarmify_video_inline = $instance['swarmify_video_inline'];
}
	$swarmify_unresponsive = 1;
if (isset( $instance['swarmify_unresponsive'])) {
	$swarmify_unresponsive = $instance['swarmify_unresponsive'];
}
	$swarmify_height = 720;
if (isset( $instance['swarmify_height'])) {
	$swarmify_height = $instance['swarmify_height'];
}
	$swarmify_width = 1280;
if (isset( $instance['swarmify_width'])) {
	$swarmify_width = $instance['swarmify_width'];
}
?>
<div class="swarmify-widget-div">
	<div class="swarmify-tabs">
		<span class="swarmify-main-tab active">Content</span>
		<span class="swarmify-basic-tab">Basic options</span>
		<span class="swarmify-advanced-tab">Advanced options</span>
	</div>
	<div class="swarmify-main">
		<p>

			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_url')); ?>" style="display: block;">
				<?php esc_html_e( 'Add a video:', 'swarmify'); ?>
			</label>
			<button class="swarmify_add_video button">Add video from WordPress Media Library</button>
			<button data-fancybox data-src="#<?php echo esc_attr($this->get_field_id( 'lightbox')); ?>" class="swarmify_fancybox swarmify_add_youtube button">Add video from YouTube</button>
			<button data-fancybox data-src="#<?php echo esc_attr($this->get_field_id( 'lightbox')); ?>" class="swarmify_add_source button">Add video from another source</button>
			<!-- Fancybox URL -->
			<div class="video_url_fancybox" id="<?php echo esc_attr($this->get_field_id( 'lightbox')); ?>" style="display: none;">
				<p class="yt" style="display: none;">Head to YouTube, view your video, click "Share", click "Copy", and paste the URL here:</p>
				<p class="other" style="display: none;">To add a video from another source (like Amazon S3, Google Drive, Dropbox, etc.), paste the URL ending in ".mp4" here:</p>
				<input class="swarmify_url widefat" id="<?php echo esc_attr($this->get_field_id( 'swarmify_url')); ?>" name="<?php echo esc_attr($this->get_field_name( 'swarmify_url')); ?>" placeholder="Video URL" type="text" value="<?php echo esc_url($swarmify_url); ?>"/>
				<button class="swarmify-lightbox-button">Save</button>
			</div>
		</p>
		<p>
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">Designates an image to be shown until playback begins. We recommend using a PNG or JPEG to be compatible with all browsers. Click the "Add Image" button to choose an image from your WordPress media library. To add an image from another source, paste the URL into the field below.</small>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_poster')); ?>">
				<?php esc_html_e( 'Add an optional poster image:', 'swarmify'); ?>
			</label>
			<button class="swarmify_add_image button ">Add image from WordPress Media Library</button>
			<button data-fancybox data-src="#<?php echo esc_attr($this->get_field_id( 'lightbox_image')); ?>" class="swarmify_add_source button ">Add image from another source</button>
			<!-- Fancybox URL -->
			<div class="image_url_fancybox" id="<?php echo esc_attr($this->get_field_id( 'lightbox_image')); ?>" style="display: none;">
				<p>Add an image from another source (like Amazon S3, Google Drive, Dropbox, etc.), paste the URL here.</p>
				<input class="swarmify_poster widefat" id="<?php echo esc_attr($this->get_field_id( 'swarmify_poster')); ?>"
				name="<?php echo esc_attr($this->get_field_name( 'swarmify_poster')); ?>" placeholder="Image URL" type="text"
				value="<?php echo esc_url( $swarmify_poster ); ?>"/>
				<button data-fancybox-close class="swarmify-lightbox-button-img">Save</button>
			</div>
		</p>
		<p id="<?php echo esc_attr($this->get_field_id( 'lightbox_title')); ?>">
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">Places a title above the video. If you do not want one, leave this field blank.</small>
			<label
			for="<?php echo esc_attr($this->get_field_id( 'title')); ?>"><?php esc_html_e( 'Add a title above video:', 'swarmify'); ?></label>
			<input class="widefat swarmify_title" id="<?php echo esc_attr($this->get_field_id( 'title')); ?>"
			name="<?php echo esc_attr($this->get_field_name( 'title')); ?>" type="text"
			value="<?php echo esc_attr( $title); ?>"/>
		</p>
		<p>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_height')); ?>">
				<?php esc_html_e( 'Height:', 'swarmify'); ?>
			</label>
			<input class="swarmify_height widefat" id="<?php echo esc_attr($this->get_field_id( 'swarmify_height')); ?>"
				name="<?php echo esc_attr($this->get_field_name( 'swarmify_height')); ?>" type="number"
				value="<?php echo '' == $swarmify_height ? '720' : esc_attr( $swarmify_height ); ?>"/>
		</p>
		<p>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_width')); ?>">
				<?php esc_html_e( 'Width:', 'swarmify'); ?>
			</label>
			<input class="swarmify_width widefat" id="<?php echo esc_attr($this->get_field_id( 'swarmify_width')); ?>"
			name="<?php echo esc_attr($this->get_field_name( 'swarmify_width')); ?>" type="number"
			value="<?php echo '' == $swarmify_width ? '1280' : esc_attr($swarmify_width); ?>"/>
		</p>
		
	</div>
	<div class="swarmify-basic">
		<p>
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">Player will begin playback automatically as soon as possible. Pro tip: unless combined with Muted toggled on, many browsers will restrict Autoplay. We recommend only using Autoplay in combination with Muted.</small>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_autoplay')); ?>">
				<?php esc_html_e( 'Autoplay:', 'swarmify'); ?>
			</label>
			<label class="wp_switch">
				<input type="checkbox" 
				<?php 
				if (1 == $swarmify_autoplay ) {
					echo 'checked="checked"';
				} 
				?>
				 name="<?php echo esc_attr($this->get_field_name( 'swarmify_autoplay')); ?>" 
				 id="<?php echo esc_attr($this->get_field_id( 'swarmify_autoplay')); ?>"" 
				 value="<?php echo '' == $swarmify_autoplay ? 1 : esc_attr($swarmify_autoplay); ?>">
				<span class="wp_slider round"></span>
			</label>
			
		</p>
		<p>
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">Player will begin playback with audio muted.</small>
			<?php esc_html_e( 'Muted:', 'swarmify'); ?>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_muted')); ?>">
			</label>
			<label class="wp_switch">
				<input type="checkbox" 
				<?php 
				if (1 == $swarmify_muted ) {
					echo 'checked="checked"';
				} 
				?>
				 name="<?php echo esc_attr($this->get_field_name( 'swarmify_muted')); ?>" 
				 id="<?php echo esc_attr($this->get_field_id( 'swarmify_muted')); ?>"" 
				 value="<?php echo '' == $swarmify_muted ? 1 : esc_attr($swarmify_muted); ?>">
				<span class="wp_slider round"></span>
			</label>
		</p>
		<p>
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">Player will restart the video once it ends.</small>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_loop')); ?>">
				<?php esc_html_e( 'Loop:', 'swarmify'); ?>
			</label>
			<label class="wp_switch">
				<input type="checkbox" 
				<?php 
				if ( 1 == $swarmify_loop ) {
					echo 'checked="checked"';
				} 
				?>
				 name="<?php echo esc_attr($this->get_field_name( 'swarmify_loop')); ?>" 
				 id="<?php echo esc_attr($this->get_field_id( 'swarmify_loop')); ?>"" 
				 value="<?php echo '' == $swarmify_loop ? 1 : esc_attr($swarmify_loop); ?>">
				<span class="wp_slider round"></span>
			</label>
		</p>
	</div>
	<div class="swarmify-advanced">
		<p>
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">Controls are on by default. Pro tip: if you toggle Controls off, make sure to toggle Autoplay on (and Muted to make sure Autoplay works). If you do not, the user will have no way of beginning video playback.</small>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_controls')); ?>">
				<?php esc_html_e( 'Controls:', 'swarmify'); ?>
			</label>
			<label class="wp_switch">
				<input type="checkbox" 
				<?php 
				if ( 1 == $swarmify_controls ) {
					echo 'checked="checked"';
				} 
				?>
				 name="<?php echo esc_attr($this->get_field_name( 'swarmify_controls')); ?>" 
				 id="<?php echo esc_attr($this->get_field_id( 'swarmify_controls')); ?>"" 
				 value="<?php echo '' == $swarmify_controls ? 1 : esc_attr($swarmify_controls); ?>">
				<span class="wp_slider round"></span>
			</label>
		</p>
		<p>
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">Safari on iOS forces videos to fullscreen. Toggle this option on to keep the video from automatically being forced to fullscreen.</small>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_video_inline')); ?>">
				<?php esc_html_e( 'Play video inline:', 'swarmify'); ?>
			</label>
			<label class="wp_switch">
				<input type="checkbox" 
				<?php 
				if ( 1 == $swarmify_video_inline ) {
					echo 'checked="checked"';
				} 
				?>
				 name="<?php echo esc_attr($this->get_field_name( 'swarmify_video_inline')); ?>" 
				 id="<?php echo esc_attr($this->get_field_id( 'swarmify_video_inline')); ?>"" 
				 value="<?php echo '' == $swarmify_video_inline ? 1 : esc_attr($swarmify_video_inline); ?>">
				<span class="wp_slider round"></span>
			</label>
		</p>
		<p>
			<i class="swarmify_info">i</i>
			<small class="swarmify_info_tooltip">The player is responsive by default. If you toggle this option off, the player will maintain height and width no matter what changes are made to the size of the browser window.</small>
			<label
				for="<?php echo esc_attr($this->get_field_id( 'swarmify_unresponsive')); ?>">
				<?php esc_html_e( 'Responsive:', 'swarmify'); ?>
			</label>
			<label class="wp_switch">
				<input type="checkbox" 
				<?php 
				if ( 1 == $swarmify_unresponsive ) {
					echo 'checked="checked"';
				} 
				?>
				 name="<?php echo esc_attr($this->get_field_name( 'swarmify_unresponsive')); ?>" 
				 id="<?php echo esc_attr($this->get_field_id( 'swarmify_unresponsive')); ?>"" 
				 value="<?php echo '' == $swarmify_unresponsive ? 1 : esc_attr($swarmify_unresponsive); ?>">
				<span class="wp_slider round"></span>
			</label>
		</p>
	</div>
</div>
