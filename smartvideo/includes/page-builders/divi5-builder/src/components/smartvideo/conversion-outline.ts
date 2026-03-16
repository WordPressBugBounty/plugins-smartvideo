/* eslint-disable @typescript-eslint/naming-convention */
import { ModuleConversionOutline } from '@divi/types';

/**
 * D4 → D5 conversion outline for SmartVideo.
 *
 * Maps the D4 shortcode [smartvideo_divi_module] attributes to their
 * D5 attribute paths, enabling automatic migration when users upgrade
 * from Divi 4 to Divi 5.
 */
export const conversionOutline: ModuleConversionOutline = {
	// Standard Divi advanced/decoration fields.
	advanced: {
		admin_label: 'module.meta.adminLabel',
		animation: 'module.decoration.animation',
		background: 'module.decoration.background',
		borders: { default: 'module.decoration.border' },
		box_shadow: { default: 'module.decoration.boxShadow' },
		disabled_on: 'module.decoration.disabledOn',
		filters: { default: 'module.decoration.filters' },
		height: 'module.decoration.sizing',
		margin_padding: 'module.decoration.spacing',
		max_width: 'module.decoration.sizing',
		module: 'module.advanced.htmlAttributes',
		overflow: 'module.decoration.overflow',
		position_fields: 'module.decoration.position',
		scroll: 'module.decoration.scroll',
		sticky: 'module.decoration.sticky',
		transform: 'module.decoration.transform',
		transition: 'module.decoration.transition',
		z_index: 'module.decoration.zIndex',
	},

	// Custom SmartVideo fields from D4 shortcode attributes.
	module: {
		video_src: 'videoSource.innerContent.*',
		media_library: 'mediaLibrary.innerContent.*',
		youtube: 'youtubeUrl.innerContent.*',
		vimeo: 'vimeoUrl.innerContent.*',
		another_source: 'anotherSource.innerContent.*',
		swarmify_url: 'swarmifyUrl.innerContent.*',
		poster_src: 'posterSource.innerContent.*',
		internal_poster: 'posterImage.innerContent.*',
		external_poster: 'posterUrl.innerContent.*',
		aspect_ratio: 'aspectRatio.innerContent.*',
		video_width: 'videoWidth.innerContent.*',
		video_height: 'videoHeight.innerContent.*',
		autoplay: 'autoplay.innerContent.*',
		muted: 'muted.innerContent.*',
		loop: 'loop.innerContent.*',
		controls: 'controls.innerContent.*',
		playsinline: 'playsinline.innerContent.*',
		responsive: 'responsive.innerContent.*',
	},
};
