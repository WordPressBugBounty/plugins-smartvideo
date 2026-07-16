jQuery(document).ready(function ($) {
	'use strict';

	// Add Color Picker to all inputs that have 'color-field' class
	$(function () {
		const colorOptions = {
			width: 250,
			palettes: true,
		};
		$('.color-field').wpColorPicker(colorOptions);
	});

	// === Dialog code ===

	// Escape a value for use inside a shortcode attribute (double-quoted)
	function escShortcodeAttr(val) {
		return val.replace(/["\\]/g, '\\$&').replace(/\]/g, '&#93;');
	}

	// Validate that a string looks like an HTTP(S) URL
	function isValidUrl(str) {
		try {
			var u = new URL(str);
			return u.protocol === 'http:' || u.protocol === 'https:';
		} catch (e) {
			return false;
		}
	}

	// YouTube URL parser (ported from Gutenberg block)
	const youtubeParser = (url) => {
		const match = url.match(/^(?:https?:\/\/)?(?:(?:www|m|music)\.)?(?:youtube(?:-nocookie)?\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
		return match ? match[1] : false;
	};

	// Vimeo URL parser — returns numeric video ID or false
	const vimeoParser = (url) => {
		const match = url.match(/(?:vimeo\.com\/)(\d+)/);
		return match ? match[1] : false;
	};

	// SmartVideo branded icon (replaces generic emoji)
	const svIcon = '<svg class="sv-preview-icon" width="48" height="48" viewBox="0 0 47 47" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" aria-hidden="true">' +
		'<path d="M23.04 0l21 11.52v23.04l-21 11.52-21-11.52V11.52L23.05 0z" fill="#FFD84D"/>' +
		'<path d="M15.52 13.43c0-2.01 1.32-2.88 2.93-1.93l17.05 9.92c1.62.94 1.62 2.46 0 3.4l-17.05 9.93c-1.61.94-2.93.07-2.93-1.93v-19.4z" fill="#333"/>' +
		'</svg>';

	// Empty-state HTML for the video preview
	const previewEmptyHTML =
		svIcon +
		'<span>Paste a video URL or choose from Media Library</span>';

	// Update the video preview based on the URL input value
	function updatePreview(url) {
		const $preview = $('#sv-video-preview');

		if (!url) {
			// Empty: show full empty state
			$preview.removeClass('has-thumbnail').html(previewEmptyHTML);
			return;
		}

		const ytId = youtubeParser(url);
		if (ytId) {
			// YouTube: show thumbnail
			const img = new Image();
			img.alt = '';
			img.src = 'https://img.youtube.com/vi/' + ytId + '/mqdefault.jpg';
			img.onerror = function () {
				$preview.removeClass('has-thumbnail').html(previewEmptyHTML);
			};
			$preview.addClass('has-thumbnail').html('').append(img);
		} else {
			var vimeoId = vimeoParser(url);
			if (vimeoId) {
				// Vimeo: fetch thumbnail + title via public oEmbed endpoint
				$preview.removeClass('has-thumbnail').html(svIcon + '<span class="sv-preview-filename">Loading...</span>');
				$.getJSON('https://vimeo.com/api/oembed.json?url=' + encodeURIComponent(url))
					.done(function (data) {
						if (data.thumbnail_url) {
							var img = new Image();
							img.alt = data.title || '';
							img.src = data.thumbnail_url;
							img.onerror = function () {
								var title = data.title || 'Vimeo #' + vimeoId;
								$preview.removeClass('has-thumbnail').html(svIcon);
								$preview.append($('<span class="sv-preview-filename">').text(title));
							};
							$preview.addClass('has-thumbnail').html('').append(img);
						} else {
							var title = data.title || 'Vimeo #' + vimeoId;
							$preview.html(svIcon);
							$preview.append($('<span class="sv-preview-filename">').text(title));
						}
					})
					.fail(function () {
						$preview.html(svIcon);
						$preview.append($('<span class="sv-preview-filename">').text('Vimeo #' + vimeoId));
					});
			} else {
				// Other URL: show SmartVideo icon with filename hint
				var filename = url.split('/').pop().split('?')[0];
				var $hint = filename ? $('<span class="sv-preview-filename">').text(filename) : null;
				$preview.removeClass('has-thumbnail').html(svIcon);
				if ($hint) $preview.append($hint);
			}
		}
	}

	// Tab switching — scoped to #swarmify-dialog
	$(document).on('click', '#swarmify-dialog .sv-tab', function () {
		const $dialog = $(this).closest('.sv-dialog');
		$dialog.find('.sv-tab').removeClass('active').attr('aria-selected', 'false').attr('tabindex', '-1');
		$dialog.find('.sv-panel').removeClass('active');
		$(this).addClass('active').attr('aria-selected', 'true').attr('tabindex', '0');

		// Determine which panel to activate from the tab class
		if ($(this).hasClass('sv-tab-video')) {
			$dialog.find('.sv-panel-video').addClass('active');
		} else if ($(this).hasClass('sv-tab-options')) {
			$dialog.find('.sv-panel-options').addClass('active');
		} else if ($(this).hasClass('sv-tab-advanced')) {
			$dialog.find('.sv-panel-advanced').addClass('active');
		}
	});

	// Arrow key navigation for tabs
	$(document).on('keydown', '#swarmify-dialog .sv-tab', function (e) {
		if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
			var $tabs = $(this).closest('.sv-dialog-tabs').find('.sv-tab');
			var idx = $tabs.index(this);
			var next = e.key === 'ArrowRight' ? (idx + 1) % $tabs.length : (idx - 1 + $tabs.length) % $tabs.length;
			$tabs.eq(idx).attr('tabindex', '-1');
			$tabs.eq(next).attr('tabindex', '0').focus().trigger('click');
			e.preventDefault();
		}
	});

	// Aspect ratio → show/hide custom dimensions (scoped to .sv-dialog)
	$(document).on('change', '.sv-dialog .swarmify_aspect_ratio', function () {
		const $dialog = $(this).closest('.sv-dialog');
		if ($(this).val() === 'custom') {
			$dialog.find('.swarmify_custom_dimensions').show();
		} else {
			$dialog.find('.swarmify_custom_dimensions').hide();
		}
	});

	// Poster source dropdown
	$(document).on('change', '.sv-dialog .sv-poster-source', function () {
		const $dialog = $(this).closest('.sv-dialog');
		const val = $(this).val();
		if (val === 'none') {
			$dialog.find('.sv-poster-media-library').hide();
			$dialog.find('.sv-poster-other').hide();
		} else if (val === 'media_library') {
			$dialog.find('.sv-poster-media-library').show();
			$dialog.find('.sv-poster-other').hide();
		} else if (val === 'another_source') {
			$dialog.find('.sv-poster-other').show();
			$dialog.find('.sv-poster-media-library').hide();
		}
	});

	// Video URL preview update (debounced 300ms)
	var previewTimer = null;
	$(document).on('input', '#swarmify-dialog .swarmify_url', function () {
		var url = $(this).val();
		clearTimeout(previewTimer);
		previewTimer = setTimeout(function () {
			updatePreview(url);
		}, 300);
	});

	// Poster URL preview (for "Other" source) — debounced 300ms
	var posterTimer = null;
	$(document).on('input', '#swarmify-dialog .swarmify_poster', function () {
		var posterUrl = $(this).val();
		var $dialog = $(this).closest('.sv-dialog');
		var $posterPreview = $dialog.find('.sv-poster-preview');
		clearTimeout(posterTimer);
		posterTimer = setTimeout(function () {
			if (posterUrl) {
				var $img = $('<img>').attr({ src: posterUrl, alt: '' }).on('error', function () { $(this).parent().hide(); });
				$posterPreview.html('').append($img).show();
			} else {
				$posterPreview.hide().html('');
			}
		}, 300);
	});

	// Media library: video picker
	$(document).on('click', '.swarmify_add_video', function () {
		const button = $(this);
		const dialog = document.getElementById('swarmify-dialog');
		const insideDialog = dialog && dialog.contains(this);

		if (this._svVideoWindow === undefined) {
			this._svVideoWindow = wp.media({
				title: 'Insert a video',
				library: { type: 'video' },
				multiple: false,
				button: { text: 'Insert' },
			});

			const self = this;
			this._svVideoWindow.on('select', function () {
				const video = self._svVideoWindow
					.state()
					.get('selection')
					.first()
					.toJSON();
				const $dialog = button.closest('.sv-dialog');
				const $urlInput = $dialog.find('.swarmify_url');
				$urlInput.val(video.url);
				updatePreview(video.url);
				// Re-open the dialog after media library selection
				if (insideDialog && dialog) {
					dialog.showModal();
				}
			});

			// Re-open the dialog if media library is closed without selection
			this._svVideoWindow.on('close', function () {
				if (insideDialog && dialog && !dialog.open) {
					dialog.showModal();
				}
			});
		}

		// Close the modal dialog before opening media library so it doesn't sit on top
		if (insideDialog && dialog) {
			dialog.close();
		}

		this._svVideoWindow.open();
		return false;
	});

	// Media library: image picker (poster)
	$(document).on('click', '.swarmify_add_image', function () {
		const button = $(this);
		const dialog = document.getElementById('swarmify-dialog');
		const insideDialog = dialog && dialog.contains(this);

		if (this._svImageWindow === undefined) {
			this._svImageWindow = wp.media({
				title: 'Insert an image',
				library: { type: 'image' },
				multiple: false,
				button: { text: 'Insert' },
			});

			const self = this;
			this._svImageWindow.on('select', function () {
				const image = self._svImageWindow
					.state()
					.get('selection')
					.first()
					.toJSON();
				const $dialog = button.closest('.sv-dialog');
				$dialog.find('.swarmify_poster').val(image.url);
				button.text('Replace image');
				// Show poster preview thumbnail
				const $posterPreview = $dialog.find('.sv-poster-preview');
				var $img = $('<img>').attr({ src: image.url, alt: '' });
				$posterPreview.html('').append($img).show();
				// Re-open the dialog after media library selection
				if (insideDialog && dialog) {
					dialog.showModal();
				}
			});

			// Re-open the dialog if media library is closed without selection
			this._svImageWindow.on('close', function () {
				if (insideDialog && dialog && !dialog.open) {
					dialog.showModal();
				}
			});
		}

		// Close the modal dialog before opening media library so it doesn't sit on top
		if (insideDialog && dialog) {
			dialog.close();
		}

		this._svImageWindow.open();
		return false;
	});

	// Shortcode builder
	$(document).on('click', '.swarmify_insert_button', function () {
		const $dialog = $(this).closest('.sv-dialog');

		const url = $dialog.find('.swarmify_url').val();
		if (!url) {
			alert('Video URL is required.');
			return;
		}
		if (!isValidUrl(url)) {
			alert('Please enter a valid URL (http or https).');
			return;
		}

		// Build shortcode parts
		const parts = ['[smartvideo src="' + escShortcodeAttr(url) + '"'];

		// Poster — only if poster source is not "none" and there's a value
		const posterSource = $dialog.find('.sv-poster-source').val();
		const poster = $dialog.find('.swarmify_poster').val();
		if (posterSource !== 'none' && poster) {
			if (!isValidUrl(poster)) {
				alert('Poster must be a valid URL (http or https).');
				return;
			}
			parts.push('poster="' + escShortcodeAttr(poster) + '"');
		}

		// Aspect ratio + dimensions (whitelist valid values)
		const validRatios = ['16:9', '4:3', '1:1', '21:9', '9:16', 'custom'];
		const ratio = $dialog.find('.swarmify_aspect_ratio').val();
		if (ratio && ratio !== '16:9' && validRatios.indexOf(ratio) !== -1) {
			parts.push('aspect_ratio="' + ratio + '"');
		}
		if (ratio === 'custom') {
			const w = $dialog.find('.swarmify_width').val() || '1280';
			const h = $dialog.find('.swarmify_height').val() || '720';
			parts.push('width="' + escShortcodeAttr(w) + '"');
			parts.push('height="' + escShortcodeAttr(h) + '"');
		}

		// Boolean attributes — only include when toggled on
		if ($dialog.find('.swarmify_autoplay').is(':checked')) {
			parts.push('autoplay="true"');
		}
		if ($dialog.find('.swarmify_muted').is(':checked')) {
			parts.push('muted="true"');
		}
		if ($dialog.find('.swarmify_loop').is(':checked')) {
			parts.push('loop="true"');
		}
		if ($dialog.find('.swarmify_controls').is(':checked')) {
			parts.push('controls="true"');
		}
		if ($dialog.find('.swarmify_video_inline').is(':checked')) {
			parts.push('playsinline="true"');
		}
		if ($dialog.find('.swarmify_unresponsive').is(':checked')) {
			parts.push('responsive="true"');
		}

		const shortcode = parts.join(' ') + ']';
		wp.media.editor.insert(shortcode);
		reset_form_elements($dialog);
		var d = document.getElementById('swarmify-dialog');
		if (d) d.close();
	});

	// Close button handler
	$(document).on('click', '.sv-dialog-close', function () {
		var d = document.getElementById('swarmify-dialog');
		if (d) d.close();
	});

	// Reset form elements to defaults
	function reset_form_elements(modal) {
		var defaults = window.smartvideoDefaults || {};

		// Text and number inputs
		modal.find('input[type="text"]').val('');
		modal.find('.swarmify_width').val('1280');
		modal.find('.swarmify_height').val('720');

		// Selects: reset to first option
		modal.find('select').each(function () {
			this.selectedIndex = 0;
		});

		// Checkboxes: set from smartvideoDefaults (truthy check)
		modal.find('.swarmify_autoplay').prop('checked', !!defaults.autoplay);
		modal.find('.swarmify_muted').prop('checked', !!defaults.muted);
		modal.find('.swarmify_loop').prop('checked', !!defaults.loop);
		modal.find('.swarmify_controls').prop('checked', !!defaults.controls);
		modal.find('.swarmify_video_inline').prop('checked', !!defaults.playsinline);
		modal.find('.swarmify_unresponsive').prop('checked', !!defaults.responsive);

		// Hide conditional sections
		modal.find('.swarmify_custom_dimensions').hide();
		modal.find('.sv-poster-media-library').hide();
		modal.find('.sv-poster-other').hide();

		// Reset preview to empty state
		$('#sv-video-preview').removeClass('has-thumbnail').html(previewEmptyHTML);

		// Reset poster image button text and poster preview
		modal.find('.swarmify_add_image').text('Select poster image');
		modal.find('.sv-poster-preview').hide().html('');
	}

	// Apply site defaults to initial checkbox states on page load
	(function () {
		var $dialog = $('.sv-dialog');
		if ($dialog.length) {
			reset_form_elements($dialog);
		}
	})();

	// --------------------------------------------------------
	// Legacy widget handlers (sidebar widget still uses old selectors)
	// --------------------------------------------------------

	// Widget tab switching
	$(document).on('click', '.swarmify-tabs button', function () {
		const parent = $(this).parent().parent();
		$('.swarmify-tabs button', parent).removeClass('active').attr('aria-selected', 'false');
		$(this).addClass('active').attr('aria-selected', 'true');
		if ($(this).hasClass('swarmify-main-tab')) {
			$('.swarmify-basic,.swarmify-advanced', parent).hide();
			$('.swarmify-main', parent).show();
		} else if ($(this).hasClass('swarmify-basic-tab')) {
			$('.swarmify-main,.swarmify-advanced', parent).hide();
			$('.swarmify-basic', parent).show();
		} else if ($(this).hasClass('swarmify-advanced-tab')) {
			$('.swarmify-basic,.swarmify-main', parent).hide();
			$('.swarmify-advanced', parent).show();
		}
	});

	// Widget aspect ratio
	$(document).on('change', '.swarmify-widget-div .swarmify_aspect_ratio', function () {
		const parent = $(this).closest('.swarmify-widget-div');
		if ($(this).val() === 'custom') {
			parent.find('.swarmify_custom_dimensions').show();
		} else {
			parent.find('.swarmify_custom_dimensions').hide();
		}
	});

	// Widget YouTube/Other source buttons
	$(document).on('click', '.swarmify_add_youtube', function () {
		var parent = $(this).closest('.swarmify-widget-div');
		if (!parent.length) return;
		parent.find('.video_url_fancybox').show();
		parent.find('.video_url_fancybox .yt').show();
		parent.find('.video_url_fancybox .other').hide();
	});

	$(document).on('click', '.swarmify-widget-div .swarmify_add_source', function () {
		var parent = $(this).closest('.swarmify-widget-div');
		parent.find('.video_url_fancybox').show();
		parent.find('.video_url_fancybox .yt').hide();
		parent.find('.video_url_fancybox .other').show();
	});

	// Widget save/close buttons
	function update_swarmify_video(main) {
		const div_id = main.prev().parent().attr('id');
		const title = $('#' + div_id + '_title').find('.swarmify_title');
		title.trigger('keyup');
	}

	$(document).on('click', '.swarmify-lightbox-button', function () {
		update_swarmify_video($(this));
	});

	$(document).on('click', '.swarmify-lightbox-button-img', function () {
		update_swarmify_video($(this));
	});

	// Tooltip hover (for widget — lightbox uses inline hints instead)
	$(document).on('mouseenter mouseleave', '.swarmify_info', function () {
		const tooltip = $(this).next();
		tooltip.toggle();
	});
});
