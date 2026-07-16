<?php defined( 'ABSPATH' ) || exit; ?>
<dialog id="swarmify-dialog" aria-labelledby="sv-dialog-title">
<div class="sv-dialog">
	<!-- Header -->
	<div class="sv-dialog-header">
		<div class="sv-dialog-header-left">
			<span class="sv-dialog-icon"><svg width="24" height="24" viewBox="0 0 47 47" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M23.04 0l21 11.52v23.04l-21 11.52-21-11.52V11.52L23.05 0z" fill="#FFD84D"/><path d="M15.52 13.43c0-2.01 1.32-2.88 2.93-1.93l17.05 9.92c1.62.94 1.62 2.46 0 3.4l-17.05 9.93c-1.61.94-2.93.07-2.93-1.93v-19.4z" fill="#333"/></svg></span>
			<span class="sv-dialog-title" id="sv-dialog-title"><?php esc_html_e( 'Add SmartVideo', 'swarmify' ); ?></span>
		</div>
		<button type="button" class="sv-dialog-close" aria-label="<?php esc_attr_e( 'Close', 'swarmify' ); ?>">&times;</button>
	</div>

	<!-- Tabs -->
	<div class="sv-dialog-tabs" role="tablist">
		<button type="button" role="tab" id="sv-tab-video" class="sv-tab sv-tab-video active" aria-selected="true" aria-controls="sv-panel-video" tabindex="0" data-label="<?php esc_attr_e( 'Video', 'swarmify' ); ?>"><?php esc_html_e( 'Video', 'swarmify' ); ?></button>
		<button type="button" role="tab" id="sv-tab-options" class="sv-tab sv-tab-options" aria-selected="false" aria-controls="sv-panel-options" tabindex="-1" data-label="<?php esc_attr_e( 'Options', 'swarmify' ); ?>"><?php esc_html_e( 'Options', 'swarmify' ); ?></button>
		<button type="button" role="tab" id="sv-tab-advanced" class="sv-tab sv-tab-advanced" aria-selected="false" aria-controls="sv-panel-advanced" tabindex="-1" data-label="<?php esc_attr_e( 'Advanced', 'swarmify' ); ?>"><?php esc_html_e( 'Advanced', 'swarmify' ); ?></button>
	</div>

	<!-- Body -->
	<div class="sv-dialog-body">
		<!-- Video Tab -->
		<div class="sv-panel sv-panel-video active" id="sv-panel-video" role="tabpanel" aria-labelledby="sv-tab-video">
			<div class="sv-field-group">
				<label class="sv-field-label" for="sv-video-url"><?php esc_html_e( 'Video URL', 'swarmify' ); ?></label>
				<div class="sv-url-row">
					<input class="sv-input swarmify_url" id="sv-video-url" type="text" placeholder="<?php esc_attr_e( 'https:// or swarmify://', 'swarmify' ); ?>" />
					<button type="button" class="sv-media-btn swarmify_add_video"><?php esc_html_e( 'Media library', 'swarmify' ); ?></button>
				</div>
				<span class="sv-field-hint"><?php esc_html_e( 'YouTube, Vimeo, Swarmify, or direct video URL', 'swarmify' ); ?></span>
			</div>

			<div class="sv-field-group">
				<div class="sv-preview" id="sv-video-preview">
					<!-- Empty state populated by JS -->
				</div>
			</div>

			<div class="sv-field-group">
				<label class="sv-field-label" for="sv-poster-source"><?php esc_html_e( 'Poster source', 'swarmify' ); ?></label>
				<select class="sv-select sv-poster-source" id="sv-poster-source">
					<option value="none"><?php esc_html_e( 'Automatic', 'swarmify' ); ?></option>
					<option value="media_library"><?php esc_html_e( 'Media library', 'swarmify' ); ?></option>
					<option value="another_source"><?php esc_html_e( 'Other', 'swarmify' ); ?></option>
				</select>
				<div class="sv-poster-media-library" style="display:none;">
					<button type="button" class="sv-media-btn swarmify_add_image"><?php esc_html_e( 'Select poster image', 'swarmify' ); ?></button>
					<div class="sv-poster-preview" style="display:none;"></div>
				</div>
				<div class="sv-poster-other" style="display:none;">
					<input class="sv-input swarmify_poster" type="text" placeholder="https://example.com/poster.jpg" />
					<div class="sv-poster-preview" style="display:none;"></div>
				</div>
			</div>
		</div>

		<!-- Options Tab -->
		<div class="sv-panel sv-panel-options" id="sv-panel-options" role="tabpanel" aria-labelledby="sv-tab-options">
			<div class="sv-field-group">
				<label class="sv-field-label" for="sv-aspect-ratio"><?php esc_html_e( 'Aspect ratio', 'swarmify' ); ?></label>
				<select class="sv-select swarmify_aspect_ratio" id="sv-aspect-ratio">
					<?php foreach ( \Swarmify\Smartvideo\AspectRatio::get_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $value, '16:9' ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="sv-field-group swarmify_custom_dimensions" style="display:none;">
				<div class="sv-dimensions-row">
					<div>
						<label class="sv-field-label" for="sv-width"><?php esc_html_e( 'Width', 'swarmify' ); ?></label>
						<input class="sv-input swarmify_width" id="sv-width" type="number" value="1280" />
					</div>
					<div>
						<label class="sv-field-label" for="sv-height"><?php esc_html_e( 'Height', 'swarmify' ); ?></label>
						<input class="sv-input swarmify_height" id="sv-height" type="number" value="720" />
					</div>
				</div>
			</div>

			<hr class="sv-separator" />

			<div class="sv-toggle-row">
				<div>
					<div class="sv-toggle-label"><?php esc_html_e( 'Autoplay', 'swarmify' ); ?></div>
					<div class="sv-toggle-hint" id="sv-hint-autoplay"><?php esc_html_e( "Automatically start playing when the video is visible. Most browsers require 'Muted' to be enabled.", 'swarmify' ); ?></div>
				</div>
				<label class="sv-switch">
					<input type="checkbox" class="swarmify_autoplay" aria-label="<?php esc_attr_e( 'Autoplay', 'swarmify' ); ?>" aria-describedby="sv-hint-autoplay" />
					<span class="sv-slider"></span>
				</label>
			</div>

			<div class="sv-toggle-row">
				<div>
					<div class="sv-toggle-label"><?php esc_html_e( 'Muted', 'swarmify' ); ?></div>
					<div class="sv-toggle-hint" id="sv-hint-muted"><?php esc_html_e( 'Start playback with audio muted.', 'swarmify' ); ?></div>
				</div>
				<label class="sv-switch">
					<input type="checkbox" class="swarmify_muted" aria-label="<?php esc_attr_e( 'Muted', 'swarmify' ); ?>" aria-describedby="sv-hint-muted" />
					<span class="sv-slider"></span>
				</label>
			</div>

			<div class="sv-toggle-row">
				<div>
					<div class="sv-toggle-label"><?php esc_html_e( 'Loop', 'swarmify' ); ?></div>
					<div class="sv-toggle-hint" id="sv-hint-loop"><?php esc_html_e( 'Restart the video automatically when it reaches the end.', 'swarmify' ); ?></div>
				</div>
				<label class="sv-switch">
					<input type="checkbox" class="swarmify_loop" aria-label="<?php esc_attr_e( 'Loop', 'swarmify' ); ?>" aria-describedby="sv-hint-loop" />
					<span class="sv-slider"></span>
				</label>
			</div>
		</div>

		<!-- Advanced Tab -->
		<div class="sv-panel sv-panel-advanced" id="sv-panel-advanced" role="tabpanel" aria-labelledby="sv-tab-advanced">
			<div class="sv-toggle-row">
				<div>
					<div class="sv-toggle-label"><?php esc_html_e( 'Controls', 'swarmify' ); ?></div>
					<div class="sv-toggle-hint" id="sv-hint-controls"><?php esc_html_e( 'Show player controls (play, pause, volume, etc.).', 'swarmify' ); ?></div>
				</div>
				<label class="sv-switch">
					<input type="checkbox" class="swarmify_controls" checked="checked" aria-label="<?php esc_attr_e( 'Controls', 'swarmify' ); ?>" aria-describedby="sv-hint-controls" />
					<span class="sv-slider"></span>
				</label>
			</div>

			<div class="sv-toggle-row">
				<div>
					<div class="sv-toggle-label"><?php esc_html_e( 'Play inline', 'swarmify' ); ?></div>
					<div class="sv-toggle-hint" id="sv-hint-playsinline"><?php esc_html_e( 'Keep the video inline on iOS instead of opening in fullscreen.', 'swarmify' ); ?></div>
				</div>
				<label class="sv-switch">
					<input type="checkbox" class="swarmify_video_inline" aria-label="<?php esc_attr_e( 'Play inline', 'swarmify' ); ?>" aria-describedby="sv-hint-playsinline" />
					<span class="sv-slider"></span>
				</label>
			</div>

			<div class="sv-toggle-row">
				<div>
					<div class="sv-toggle-label"><?php esc_html_e( 'Responsive', 'swarmify' ); ?></div>
					<div class="sv-toggle-hint" id="sv-hint-responsive"><?php esc_html_e( 'Make the video responsive to fill its container width while maintaining aspect ratio.', 'swarmify' ); ?></div>
				</div>
				<label class="sv-switch">
					<input type="checkbox" class="swarmify_unresponsive" checked="checked" aria-label="<?php esc_attr_e( 'Responsive', 'swarmify' ); ?>" aria-describedby="sv-hint-responsive" />
					<span class="sv-slider"></span>
				</label>
			</div>
		</div>
	</div>

	<!-- Footer -->
	<div class="sv-dialog-footer">
		<button type="button" class="sv-btn-cancel sv-dialog-close"><?php esc_html_e( 'Cancel', 'swarmify' ); ?></button>
		<button type="button" class="sv-btn-insert swarmify_insert_button"><?php esc_html_e( 'Insert into post', 'swarmify' ); ?></button>
	</div>
</div>
</dialog>
