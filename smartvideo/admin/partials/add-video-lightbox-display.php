<!-- This file is unused, and exists only to prevent an error message when upgrading from versions below 2.1.0 -->

<div id="swarmify-modal-content" style="display: none;">
	<div class="swarmify-widget-div">
		<div class="swarmify-tabs">
			<span class="swarmify-main-tab active">Content</span>
			<span class="swarmify-basic-tab">Basic options</span>
			<span class="swarmify-advanced-tab">Advanced options</span>
		</div>
		<div class="swarmify-main">
			<p>
				<label for="swarmify_url" style="display: block;">
					<?php esc_html_e( 'Add a video:', 'swarmify' ); ?>
				</label>
				<button class="swarmify_add_video button">Add video from WordPress Media Library</button>
				<button data-fancybox data-src="#video_url_fancybox" class="swarmify_add_youtube button">Add video from YouTube</button>
				<button data-fancybox data-src="#video_url_fancybox" class="swarmify_add_source button">Add video from another source</button>
				
				<!-- Fancybox URL -->
				<div class="video_url_fancybox" id="video_url_fancybox" style="display: none;">
					<p class="yt" style="display: none;">Head to YouTube, view your video, click "Share", click "Copy", and paste the URL here:</p>
					<p class="other" style="display: none;">To add a video from another source (like Amazon S3, Google Drive, Dropbox, etc.), paste the URL ending in ".mp4" here:</p>
					<input class="swarmify_url widefat" id="swarmify_url" placeholder="Video URL" type="text"/>
					<button data-fancybox-close class="swarmify-lightbox-button"> Save </button>
				</div>
			</p>
			<p>
				<i class="swarmify_info">i</i>
				<small class="swarmify_info_tooltip">Designates an image to be shown until playback begins. We recommend using a PNG or JPEG to be compatible with all browsers. Click the "Add Image" button to choose an image from your WordPress media library. To add an image from another source, paste the URL into the field below.</small>
				<label
					for="swarmify_poster">
					<?php esc_html_e( 'Add an optional poster image:', 'swarmify' ); ?>
				</label>
				<button class="swarmify_add_image button ">Add image from WordPress Media Library</button>
				<button data-fancybox data-src="#image_url_fancybox" class="swarmify_add_source button ">Add image from another source</button>
				<!-- Fancybox URL -->
				<div id="image_url_fancybox" style="display: none;">
					<p>Add an image from another source (like Amazon S3, Google Drive, Dropbox, etc.), paste the URL here.</p>
					<input class="swarmify_poster widefat" id="swarmify_poster" placeholder="Image URL" type="text"/>
					<button data-fancybox-close class="swarmify-lightbox-button"> Save </button>
				</div>
			</p>
			<p>
				<label
					for="swarmify_height">
					<?php esc_html_e( 'Height:', 'swarmify' ); ?>
				</label>
				<input class="swarmify_height widefat" id="swarmify_height" value="720" type="number"/>
			</p>
			<p>
				<label
					for="swarmify_width">
					<?php esc_html_e( 'Width:', 'swarmify' ); ?>
				</label>
				<input class="swarmify_width widefat" id="swarmify_width" value="1280" type="number"/>
			</p>
			
		</div>
		<div class="swarmify-basic">
			<p>
				<i class="swarmify_info">i</i>
				<small class="swarmify_info_tooltip">Player will begin playback automatically as soon as possible. Pro tip: unless combined with Muted toggled on, many browsers will restrict Autoplay. We recommend only using Autoplay in combination with Muted.</small>
				<label
					for="autoplay">
					<?php esc_html_e( 'Autoplay:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="autoplay" class="swarmify_autoplay">
					<span class="wp_slider round"></span>
				</label>
				
			</p>
			<p>
				<i class="swarmify_info">i</i>
				<small class="swarmify_info_tooltip">Player will begin playback with audio muted.</small>
				<?php esc_html_e( 'Muted:', 'swarmify' ); ?>
				<label
					for="muted">
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="muted" class="swarmify_muted">
					<span class="wp_slider round"></span>
				</label>
			</p>
			<p>
				<i class="swarmify_info">i</i>
				<small class="swarmify_info_tooltip">Player will restart the video once it ends.</small>
				<label
					for="loop">
					<?php esc_html_e( 'Loop:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="loop" class="swarmify_loop">
					<span class="wp_slider round"></span>
				</label>
			</p>
		</div>
		<div class="swarmify-advanced">
			<p>
				<i class="swarmify_info">i</i>
				<small class="swarmify_info_tooltip">Controls are on by default. Pro tip: if you toggle Controls off, make sure to toggle Autoplay on (and Muted to make sure Autoplay works). If you do not, the user will have no way of beginning video playback.</small>
				<label
					for="controls">
					<?php esc_html_e( 'Controls:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="controls" class="swarmify_controls" checked="checked">
					<span class="wp_slider round"></span>
				</label>
			</p>
			<p>
				<i class="swarmify_info">i</i>
				<small class="swarmify_info_tooltip">Safari on iOS forces videos to fullscreen. Toggle this option on to keep the video from automatically being forced to fullscreen.</small>
				<label
					for="video_inline">
					<?php esc_html_e( 'Play video inline:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="video_inline" class="swarmify_video_inline">
					<span class="wp_slider round"></span>
				</label>
			</p>
			<p>
				<i class="swarmify_info">i</i>
				<small class="swarmify_info_tooltip">The player is responsive by default. If you toggle this option off, the player will maintain height and width no matter what changes are made to the size of the browser window.</small>
				<label for="unresponsive">
					<?php esc_html_e( 'Responsive:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="unresponsive" class="swarmify_unresponsive" checked="checked">
					<span class="wp_slider round"></span>
				</label>
			</p>
		</div>
		<button class="button-primary button-large swarmify_insert_button">Insert into Post</button>
	</div>
</div>
