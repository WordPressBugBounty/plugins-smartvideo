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

	// Tab switching (shared between classic editor lightbox and widget)
	$(document).on('click', '.swarmify-tabs span', function () {
		const parent = $(this).parent().parent();
		$('.swarmify-tabs span', parent).removeClass('active');
		$(this).addClass('active');
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

	// Aspect ratio → show/hide custom dimensions
	$(document).on('change', '.swarmify_aspect_ratio', function () {
		const parent = $(this).closest('.swarmify-widget-div');
		if ($(this).val() === 'custom') {
			parent.find('.swarmify_custom_dimensions').show();
		} else {
			parent.find('.swarmify_custom_dimensions').hide();
		}
	});

	// Media library: video picker
	$(document).on('click', '.swarmify_add_video', open_video_window);

	function open_video_window() {
		const button = $(this);
		if (this.window === undefined) {
			this.window = wp.media({
				title: 'Insert a video',
				library: { type: 'video' },
				multiple: false,
				button: { text: 'Insert' },
			});

			const self = this;
			this.window.on('select', function () {
				const video = self.window
					.state()
					.get('selection')
					.first()
					.toJSON();
				const div_parent = button
					.closest('.swarmify-widget-div')
					.find('.swarmify_url');
				div_parent.val(video.url);
				update_swarmify_video(div_parent);
			});
		}

		this.window.open();
		return false;
	}

	function update_swarmify_video(main) {
		const div_id = main.prev().parent().attr('id');
		const title = $('#' + div_id + '_title').find('.swarmify_title');
		title.trigger('keyup');
	}

	$(document).on('click', '.swarmify-lightbox-button', function () {
		update_swarmify_video($(this));
		$.fancybox.close();
	});

	$(document).on('click', '.swarmify-lightbox-button-img', function () {
		update_swarmify_video($(this));
		$.fancybox.close();
	});

	// Media library: image picker
	$(document).on('click', '.swarmify_add_image', open_image_window);

	function open_image_window() {
		const button = $(this);
		if (this.window === undefined) {
			this.window = wp.media({
				title: 'Insert an image',
				library: { type: 'image' },
				multiple: false,
				button: { text: 'Insert' },
			});

			const self = this;
			this.window.on('select', function () {
				const image = self.window
					.state()
					.get('selection')
					.first()
					.toJSON();
				const div_parent = button
					.closest('.swarmify-widget-div')
					.find('.swarmify_poster');
				const div_parent2 = button
					.closest('.swarmify-widget-div')
					.find('.swarmify_url');
				div_parent.val(image.url);
				update_swarmify_video(div_parent2);
			});
		}

		this.window.open();
		return false;
	}

	// Tooltip hover (for widget — lightbox uses inline hints instead)
	$(document).on('mouseenter mouseleave', '.swarmify_info', function () {
		const tooltip = $(this).next();
		tooltip.toggle();
	});

	// Build and insert [smartvideo] shortcode into the classic editor
	$(document).on('click', '.swarmify_insert_button', function () {
		const modal = $(this).closest('.swarmify-widget-div');

		const url = modal.find('.swarmify_url').val();
		if (!url) {
			alert('Video URL is required.');
			return;
		}

		// Build shortcode parts
		const parts = ['[smartvideo src="' + url + '"'];

		// Poster
		const poster = modal.find('.swarmify_poster').val();
		if (poster) {
			parts.push('poster="' + poster + '"');
		}

		// Aspect ratio + dimensions
		const ratio = modal.find('.swarmify_aspect_ratio').val();
		if (ratio && ratio !== '16:9') {
			parts.push('aspect_ratio="' + ratio + '"');
		}
		if (ratio === 'custom') {
			const w = modal.find('.swarmify_width').val() || '1280';
			const h = modal.find('.swarmify_height').val() || '720';
			parts.push('width="' + w + '"');
			parts.push('height="' + h + '"');
		}

		// Boolean attributes — only include when toggled on
		if (modal.find('.swarmify_autoplay').is(':checked')) {
			parts.push('autoplay="true"');
		}
		if (modal.find('.swarmify_muted').is(':checked')) {
			parts.push('muted="true"');
		}
		if (modal.find('.swarmify_loop').is(':checked')) {
			parts.push('loop="true"');
		}
		if (modal.find('.swarmify_controls').is(':checked')) {
			parts.push('controls="true"');
		}
		if (modal.find('.swarmify_video_inline').is(':checked')) {
			parts.push('playsinline="true"');
		}
		if (modal.find('.swarmify_unresponsive').is(':checked')) {
			parts.push('responsive="true"');
		}

		const shortcode = parts.join(' ') + ']';
		wp.media.editor.insert(shortcode);
		reset_form_elements(modal);
		$.fancybox.close();
	});

	const default_checked = new Set(['controls', 'unresponsive']);

	function reset_form_elements(modal) {
		modal.find(':input').each(function () {
			switch (this.type) {
				case 'text':
					$(this).val('');
					break;
				case 'checkbox':
					this.checked = default_checked.has(this.id);
					break;
				case 'select-one':
					this.selectedIndex = 0;
					break;
			}
		});
		// Re-hide custom dimensions after reset
		modal.find('.swarmify_custom_dimensions').hide();
	}

	// YouTube / other source URL prompt
	$(document).on('click', '.swarmify_add_youtube', function () {
		$('.video_url_fancybox .yt').show();
		$('.video_url_fancybox .other').hide();
	});

	$(document).on('click', '.swarmify_add_source', function () {
		$('.video_url_fancybox .yt').hide();
		$('.video_url_fancybox .other').show();
	});

	// Watermark picker (settings page)
	function open_watermark_window() {
		const button = $(this);
		if (this.window === undefined) {
			this.window = wp.media({
				title: 'Insert an image',
				library: { type: 'image' },
				multiple: false,
				button: { text: 'Insert' },
			});

			const self = this;
			this.window.on('select', function () {
				const watermark = self.window
					.state()
					.get('selection')
					.first()
					.toJSON();
				const watermark_input = button
					.parent()
					.parent()
					.find('#swarmify_watermark');
				const image_preview = button
					.parent()
					.parent()
					.find('#swarmify_watermark_preview');
				watermark_input.val(watermark.url);
				image_preview.attr('src', watermark.url);
			});
		}

		this.window.open();
		return false;
	}

	function remove_watermark() {
		const button = $(this);
		const watermark_input = button.parent().find('#swarmify_watermark');
		const image_preview = button
			.parent()
			.find('#swarmify_watermark_preview');
		watermark_input.val('');
		image_preview.removeAttr('src');

		return false;
	}

	$('#swarmify_watermark_remove_btn').click(remove_watermark);
	$('#swarmify_watermark_button').click(open_watermark_window);

	let advancedPanelVisibile = true;
	function hideShowAdvancedOptions(evt) {
		const speed = evt && evt.data && evt.data.speed ? evt.data.speed : 0;
		if (advancedPanelVisibile) {
			$('#panel-advanced-body').hide(speed);
		} else {
			$('#panel-advanced-body').show(speed);
		}
		advancedPanelVisibile = !advancedPanelVisibile;
	}

	$('#panel-advanced-btn').click({ speed: 500 }, hideShowAdvancedOptions);
	// Hide panel initially
	hideShowAdvancedOptions();
});
