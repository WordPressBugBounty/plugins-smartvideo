/**
 * SmartVideo Gutenberg Block
 *
 * Migrated from cgb-scripts to @wordpress/scripts.
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import icon from './icon';
import edit from './edit';
import { getSmartVideoElem } from './smartvideo';

import './editor.scss';
import './style.scss';

/**
 * Parse a YouTube video ID from any common URL format.
 */
const youtubeParser = (url) => {
	if (!url) return false;
	const match = url.match(
		/^.*(?:youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?)\??v?=?([^#&?]*).*/
	);
	return match && match[1].length === 11 ? match[1] : false;
};

/**
 * Derive the embed URL from legacy block attributes.
 * Old blocks stored source-specific URLs (youtube, vimeo, etc.) instead of
 * a single smartvideoEmbedLink. This reconstructs what the embed URL was.
 */
function deriveLegacyEmbedUrl(attributes) {
	const {
		source,
		youtube,
		vimeo,
		videoInternalUrl,
		swarmifyUrl,
		anotherSource,
		smartvideoEmbedLink,
	} = attributes;

	// If smartvideoEmbedLink was explicitly stored, use it as-is.
	if (smartvideoEmbedLink) return smartvideoEmbedLink;

	// Derive from legacy source-specific attributes.
	let url = '';
	switch (source) {
		case 'youtube':
			url = youtube || '';
			break;
		case 'vimeo':
			url = vimeo || '';
			break;
		case 'media_library':
			url = videoInternalUrl || '';
			break;
		case 'swarmify_url':
			url = swarmifyUrl || '';
			break;
		case 'another_source':
			url = anotherSource || '';
			break;
	}

	const ytId = youtubeParser(url);
	return ytId ? `https://www.youtube.com/embed/${ytId}` : url;
}

// Current attribute schema.
// Defaults MUST be static so save() output is deterministic — dynamic defaults
// from site settings caused block validation failures whenever an admin changed
// a setting (e.g. muted). Site defaults are applied to fresh blocks in edit.js.
const attributes = {
	aspectRatio: { type: 'string', default: '16:9' },
	autoplay: { type: 'boolean', default: false },
	loop: { type: 'boolean', default: false },
	muted: { type: 'boolean', default: false },
	width: { type: 'string', default: '1280' },
	height: { type: 'string', default: '720' },
	controls: { type: 'boolean', default: true },
	playsInline: { type: 'boolean', default: false },
	responsive: { type: 'boolean', default: true },
	poster: { type: 'string', default: 'none' },
	posterId: { type: 'number' },
	posterMediaLibrary: { type: 'string' },
	posterAnoterSource: { type: 'string' },
	posterUrl: { type: 'string' },
	// New unified URL field (v2.4+).
	videoUrl: { type: 'string' },
	// Legacy attributes kept for backward compat — no longer shown in UI.
	source: { type: 'string', default: 'media_library' },
	videoId: { type: 'number' },
	videoInternalUrl: { type: 'string' },
	youtube: { type: 'string' },
	vimeo: { type: 'string' },
	anotherSource: { type: 'string' },
	swarmifyUrl: { type: 'string' },
	preload: { type: 'string', default: 'auto' },
	smartvideoEmbedLink: {
		type: 'string',
		default:
			'https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4',
	},
};

// Attributes without aspectRatio — for deprecated block formats that predate
// the aspect ratio feature. getSmartVideoElem falls back to raw width/height
// when aspectRatio is undefined (not in ASPECT_RATIO_PRESETS).
const { aspectRatio: _unused, ...preAspectRatioAttrs } = attributes;

registerBlockType('smartvideo/block-smartvideo-guten', {
	title: __('SmartVideo'),
	description: __(
		'SmartVideo makes building a beautiful, professional video experience for your site effortless.'
	),
	icon,
	category: 'media',
	keywords: [__('video'), __('smartvideo'), __('embed')],
	supports: {
		align: true,
	},
	attributes,
	edit,
	save: (props) => {
		const smartVideoEl = getSmartVideoElem(props);
		const smartVideoHtml = smartVideoEl.outerHTML;
		const dangerHtml = { __html: smartVideoHtml };
		return <div dangerouslySetInnerHTML={dangerHtml} />;
	},

	deprecated: [
		// Pre-2.2.0: unified URL field but no aspectRatio attribute.
		// Old blocks stored raw width/height (e.g. 1280x720); the current save
		// resolves 16:9 to 640x360, causing a validation mismatch.
		// className:false prevents WP from adding wp-block-* class to the
		// wrapper div — old blocks were saved without it.
		{
			attributes: preAspectRatioAttrs,
			supports: { className: false },
			save: (props) => {
				const smartVideoEl = getSmartVideoElem(props);
				return (
					<div
						dangerouslySetInnerHTML={{
							__html: smartVideoEl.outerHTML,
						}}
					/>
				);
			},
			migrate: (attrs) => ({
				...attrs,
				aspectRatio: 'custom',
			}),
		},
		// Pre-v2.4: source-specific URL attributes (youtube, vimeo, etc.)
		// instead of the unified videoUrl/smartvideoEmbedLink field.
		{
			supports: { className: false },
			attributes: {
				...preAspectRatioAttrs,
				// Override: no default, so the actual stored value is used
				// instead of falling back to the intro video.
				smartvideoEmbedLink: { type: 'string' },
			},
			save: (props) => {
				const embedUrl = deriveLegacyEmbedUrl(props.attributes);
				const modifiedProps = {
					...props,
					attributes: {
						...props.attributes,
						smartvideoEmbedLink: embedUrl,
					},
				};
				const smartVideoEl = getSmartVideoElem(modifiedProps);
				return (
					<div
						dangerouslySetInnerHTML={{
							__html: smartVideoEl.outerHTML,
						}}
					/>
				);
			},
			migrate: (attrs) => ({
				...attrs,
				smartvideoEmbedLink: deriveLegacyEmbedUrl(attrs),
				aspectRatio: 'custom',
			}),
		},
	],
});
