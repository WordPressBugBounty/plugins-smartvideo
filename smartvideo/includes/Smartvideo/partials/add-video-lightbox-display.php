<div id="swarmify-modal-content" style="display: none;">
	<div class="swarmify-widget-div">
		<div class="swarmify-tabs">
			<span class="swarmify-main-tab active">Video</span>
			<span class="swarmify-basic-tab">Options</span>
			<span class="swarmify-advanced-tab">Advanced</span>
		</div>
		<div class="swarmify-main">
			<p>
				<label for="swarmify_url" style="display: block;">
					<?php esc_html_e( 'Video source:', 'swarmify' ); ?>
				</label>
				<button class="swarmify_add_video button">Media library</button>
				<button data-fancybox data-src="#video_url_fancybox" class="swarmify_add_youtube button">YouTube URL</button>
				<button data-fancybox data-src="#video_url_fancybox" class="swarmify_add_source button">Other URL</button>

				<!-- Fancybox URL -->
				<div class="video_url_fancybox" id="video_url_fancybox" style="display: none;">
					<p class="yt" style="display: none;">Paste a YouTube URL:</p>
					<p class="other" style="display: none;">Paste a direct video URL (e.g. .mp4 from S3, Google Drive, etc.):</p>
					<input class="swarmify_url widefat" id="swarmify_url" placeholder="https://..." type="text"/>
					<button data-fancybox-close class="swarmify-lightbox-button">Save</button>
				</div>
			</p>
			<p>
				<label for="swarmify_poster" style="display: block;">
					<?php esc_html_e( 'Poster image:', 'swarmify' ); ?>
				</label>
				<small class="swarmify-field-hint"><?php esc_html_e( 'Optional — leave blank for automatic.', 'swarmify' ); ?></small>
				<button class="swarmify_add_image button">Media library</button>
				<button data-fancybox data-src="#image_url_fancybox" class="swarmify_add_source button">Image URL</button>
				<!-- Fancybox URL -->
				<div id="image_url_fancybox" style="display: none;">
					<p>Paste an image URL (PNG or JPEG recommended):</p>
					<input class="swarmify_poster widefat" id="swarmify_poster" placeholder="https://example.com/poster.jpg" type="text"/>
					<button data-fancybox-close class="swarmify-lightbox-button">Save</button>
				</div>
			</p>
		</div>
		<div class="swarmify-basic">
			<p>
				<label for="swarmify_aspect_ratio">
					<?php esc_html_e( 'Aspect ratio:', 'swarmify' ); ?>
				</label>
				<select class="swarmify_aspect_ratio widefat" id="swarmify_aspect_ratio">
					<?php foreach ( \Swarmify\Smartvideo\AspectRatio::get_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value, '16:9' ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="swarmify_custom_dimensions" style="display: none;">
				<label for="swarmify_width"><?php esc_html_e( 'Width:', 'swarmify' ); ?></label>
				<input class="swarmify_width widefat" id="swarmify_width" value="1280" type="number"/>
			</p>
			<p class="swarmify_custom_dimensions" style="display: none;">
				<label for="swarmify_height"><?php esc_html_e( 'Height:', 'swarmify' ); ?></label>
				<input class="swarmify_height widefat" id="swarmify_height" value="720" type="number"/>
			</p>
				<hr style="margin: 12px 0;">
			<p>
				<label for="autoplay">
					<?php esc_html_e( 'Autoplay:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="autoplay" class="swarmify_autoplay">
					<span class="wp_slider round"></span>
				</label>
				<small class="swarmify-field-hint"><?php esc_html_e( 'Most browsers require Muted for autoplay to work.', 'swarmify' ); ?></small>
			</p>
			<p>
				<label for="muted">
					<?php esc_html_e( 'Muted:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="muted" class="swarmify_muted">
					<span class="wp_slider round"></span>
				</label>
			</p>
			<p>
				<label for="loop">
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
				<label for="controls">
					<?php esc_html_e( 'Controls:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="controls" class="swarmify_controls" checked="checked">
					<span class="wp_slider round"></span>
				</label>
				<small class="swarmify-field-hint"><?php esc_html_e( 'If disabled, enable Autoplay + Muted so users can still see the video.', 'swarmify' ); ?></small>
			</p>
			<p>
				<label for="video_inline">
					<?php esc_html_e( 'Play inline:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="video_inline" class="swarmify_video_inline">
					<span class="wp_slider round"></span>
				</label>
				<small class="swarmify-field-hint"><?php esc_html_e( 'Prevents iOS Safari from forcing fullscreen.', 'swarmify' ); ?></small>
			</p>
			<p>
				<label for="unresponsive">
					<?php esc_html_e( 'Responsive:', 'swarmify' ); ?>
				</label>
				<label class="wp_switch">
					<input type="checkbox" id="unresponsive" class="swarmify_unresponsive" checked="checked">
					<span class="wp_slider round"></span>
				</label>
				<small class="swarmify-field-hint"><?php esc_html_e( 'Fills container width, maintains aspect ratio.', 'swarmify' ); ?></small>
			</p>
		</div>
		<button class="button-primary button-large swarmify_insert_button">Insert into post</button>
	</div>
</div>
