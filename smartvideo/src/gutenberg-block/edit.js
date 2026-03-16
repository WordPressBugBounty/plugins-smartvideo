import { getSmartVideoElem } from './smartvideo';

import {
	Button,
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
} from '@wordpress/components';

import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';

import { Notice } from '@wordpress/components';
import { Component, createRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { compose, withInstanceId } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { withNotices } from '@wordpress/components';

const ALLOWED_MEDIA_TYPES = ['video'];
const VIDEO_POSTER_ALLOWED_MEDIA_TYPES = ['image'];

/**
 * Parse a YouTube video ID from any common YouTube URL format.
 */
const youtubeParser = (url) => {
	const match = url.match(
		/^.*(?:youtu\.be\/|v\/|u\/\w\/|embed\/|watch\?)\??v?=?([^#&?]*).*/
	);
	return match && match[1].length === 11 ? match[1] : false;
};

class VideoEdit extends Component {
	constructor() {
		super(...arguments);
		this.containerRef = createRef();
		this.container = null;
	}

	componentDidUpdate(prevProps) {
		if (prevProps.attributes !== this.props.attributes) {
			const containDiv = this.container;
			while (containDiv.firstChild) {
				containDiv.removeChild(containDiv.firstChild);
			}
			if (this.props.attributes.smartvideoEmbedLink) {
				containDiv.append(getSmartVideoElem(this.props));
			} else {
				containDiv.append(this.createPlaceholder());
			}
		}
	}

	componentDidMount() {
		this.container = this.containerRef.current;

		// Clear any existing children (prevents duplication on re-mount after save).
		while (this.container.firstChild) {
			this.container.removeChild(this.container.firstChild);
		}

		// Apply site defaults to freshly inserted blocks. Attribute defaults are
		// static (for deterministic save output), so site-level preferences like
		// autoplay/muted are applied here instead.
		const DEFAULT_INTRO = 'https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4';
		if (!this.props.attributes.videoUrl && this.props.attributes.smartvideoEmbedLink === DEFAULT_INTRO) {
			const siteDefaults = window.smartvideoBlockData?.defaults || {};
			this.props.setAttributes({
				autoplay: siteDefaults.autoplay ?? false,
				muted: siteDefaults.muted ?? false,
				loop: siteDefaults.loop ?? false,
				controls: siteDefaults.controls ?? true,
				playsInline: siteDefaults.playsInline ?? false,
				responsive: siteDefaults.responsive ?? true,
			});
		}

		// Migrate old source-type attributes into videoUrl on first load.
		// This also marks the editor content as dirty so saving persists
		// the migrated attributes (deprecated.migrate alone doesn't).
		const { videoUrl, source, videoInternalUrl, youtube, vimeo, swarmifyUrl, anotherSource, smartvideoEmbedLink } =
			this.props.attributes;

		let effectiveEmbedLink = smartvideoEmbedLink;

		if (!videoUrl && source) {
			let derivedUrl = '';
			switch (source) {
				case 'media_library':
					derivedUrl = videoInternalUrl || smartvideoEmbedLink || '';
					break;
				case 'youtube':
					derivedUrl = youtube || '';
					break;
				case 'vimeo':
					derivedUrl = vimeo || '';
					break;
				case 'swarmify_url':
					derivedUrl = swarmifyUrl || '';
					break;
				case 'another_source':
					derivedUrl = anotherSource || '';
					break;
			}
			if (derivedUrl) {
				const ytId = youtubeParser(derivedUrl);
				effectiveEmbedLink = ytId
					? `https://www.youtube.com/embed/${ytId}`
					: derivedUrl;
				this.props.setAttributes({
					videoUrl: derivedUrl,
					smartvideoEmbedLink: effectiveEmbedLink,
				});
			}
		}

		if (effectiveEmbedLink) {
			const renderProps = effectiveEmbedLink !== smartvideoEmbedLink
				? { ...this.props, attributes: { ...this.props.attributes, smartvideoEmbedLink: effectiveEmbedLink } }
				: this.props;
			this.container.append(getSmartVideoElem(renderProps));
		} else {
			this.container.append(this.createPlaceholder());
		}
	}

	setAttributeVal(attribute) {
		return (newValue) => {
			this.props.setAttributes({ [attribute]: newValue });
		};
	}

	createPlaceholder() {
		const el = document.createElement('div');
		el.style.cssText =
			'display:flex;align-items:center;justify-content:center;' +
			'min-height:200px;background:#3a3a3a;border-radius:4px;' +
			'color:#b0b0b0;font-size:13px;';
		el.textContent = 'Paste a video URL or choose from Media Library \u2192';
		return el;
	}

	render() {
		const {
			aspectRatio,
			autoplay,
			muted,
			loop,
			width,
			height,
			controls,
			playsInline,
			responsive,
			poster,
			posterMediaLibrary,
			posterId,
			posterAnoterSource,
			posterUrl,
			videoUrl,
			videoId,
			videoInternalUrl,
			smartvideoEmbedLink,
		} = this.props.attributes;

		const { setAttributes } = this.props;

		const onChangeVideoUrl = (newValue) => {
			// Normalize for embed (YouTube → /embed/).
			const ytId = youtubeParser(newValue);
			const embedUrl = ytId
				? `https://www.youtube.com/embed/${ytId}`
				: newValue;
			// Clear videoId — user is typing a URL, not picking from library.
			setAttributes({
				videoUrl: newValue,
				smartvideoEmbedLink: embedUrl,
				videoId: 0,
			});
		};

		const onSelectVideo = (media) => {
			setAttributes({
				videoUrl: media.url,
				videoInternalUrl: media.url,
				smartvideoEmbedLink: media.url,
				videoId: media.id,
			});
		};

		const onChangePosterMediaLibrary = (media) => {
			setAttributes({
				posterMediaLibrary: media.url,
				posterId: media.id,
				posterUrl: media.url,
			});
		};

		const onChangePosterAnotherSource = (newValue) => {
			setAttributes({
				posterAnoterSource: newValue,
				posterUrl: newValue,
			});
		};

		const isActive = window.smartvideoBlockData?.isActive;

		return (
			<>
				{!isActive && (
					<Notice status="warning" isDismissible={false}>
						{__(
							'SmartVideo is currently disabled. Enable it in',
							'swarmify'
						)}{' '}
						<a
							href={
								window.ajaxurl?.replace(
									'admin-ajax.php',
									'admin.php?page=SmartVideo.php'
								) || '/wp-admin/admin.php?page=SmartVideo.php'
							}
						>
							{__('Settings', 'swarmify')}
						</a>
						{__('for videos to play.', 'swarmify')}
					</Notice>
				)}
				<InspectorControls>
					<PanelBody title={__('Video')}>
						<TextControl
							label={__('Video URL')}
							value={videoUrl || ''}
							onChange={onChangeVideoUrl}
							placeholder="https://www.youtube.com/watch?v=... or any video URL"
							help={__(
								'YouTube, Vimeo, Swarmify, or direct video URL'
							)}
						/>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectVideo}
								allowedTypes={ALLOWED_MEDIA_TYPES}
								value={videoId}
								render={({ open }) => (
									<Button
										variant="secondary"
										onClick={open}
									>
										{videoId
											? __('Replace video')
											: __('Choose from Media Library')}
									</Button>
								)}
							/>
						</MediaUploadCheck>
					</PanelBody>
					<PanelBody title={__('Poster')} initialOpen={false}>
						<SelectControl
							label={__('Source')}
							value={poster}
							onChange={this.setAttributeVal('poster')}
							options={[
								{ value: 'none', label: __('Automatic') },
								{
									value: 'media_library',
									label: __('Media library'),
								},
								{
									value: 'another_source',
									label: __('Other'),
								},
							]}
						/>
						{'media_library' === poster && (
							<MediaUploadCheck>
								<MediaUpload
									onSelect={onChangePosterMediaLibrary}
									allowedTypes={
										VIDEO_POSTER_ALLOWED_MEDIA_TYPES
									}
									value={posterId}
									render={({ open }) => (
										<Button
											variant="secondary"
											onClick={open}
										>
											{!posterMediaLibrary
												? __('Select poster image')
												: __('Replace image')}
										</Button>
									)}
								/>
							</MediaUploadCheck>
						)}
						{'another_source' === poster && (
							<TextControl
								label="Poster URL"
								value={posterAnoterSource}
								onChange={onChangePosterAnotherSource}
								placeholder="https://example.com/poster.jpg"
							/>
						)}
					</PanelBody>
					<PanelBody title={__('Basic options')} initialOpen={false}>
						<SelectControl
							label={__('Aspect Ratio')}
							value={aspectRatio || '16:9'}
							onChange={this.setAttributeVal('aspectRatio')}
							options={[
								{ value: '16:9', label: __('16:9 (Standard)') },
								{ value: '4:3', label: __('4:3 (Legacy)') },
								{
									value: '21:9',
									label: __('21:9 (Ultrawide)'),
								},
								{ value: '9:16', label: __('9:16 (Vertical)') },
								{ value: '1:1', label: __('1:1 (Square)') },
								{ value: 'custom', label: __('Custom') },
							]}
						/>
						{'custom' === aspectRatio && (
							<TextControl
								label={__('Width')}
								value={width}
								onChange={this.setAttributeVal('width')}
							/>
						)}
						{'custom' === aspectRatio && (
							<TextControl
								label={__('Height')}
								value={height}
								onChange={this.setAttributeVal('height')}
							/>
						)}
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Autoplay')}
							help={__(
								"Automatically start playing when the video is visible. Most browsers require 'Muted' to be enabled."
							)}
							onChange={this.setAttributeVal('autoplay')}
							checked={autoplay}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Muted')}
							help={__('Start playback with audio muted.')}
							onChange={this.setAttributeVal('muted')}
							checked={muted}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Loop')}
							help={__(
								'Restart the video automatically when it reaches the end.'
							)}
							onChange={this.setAttributeVal('loop')}
							checked={loop}
						/>
					</PanelBody>
					<PanelBody
						title={__('Advanced options')}
						initialOpen={false}
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Controls')}
							help={__(
								'Show player controls (play, pause, volume, etc.).'
							)}
							onChange={this.setAttributeVal('controls')}
							checked={controls}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Play inline')}
							help={__(
								'Keep the video inline on iOS instead of opening in fullscreen.'
							)}
							onChange={this.setAttributeVal('playsInline')}
							checked={playsInline}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Responsive')}
							help={__(
								'Make the video responsive to fill its container width while maintaining aspect ratio.'
							)}
							onChange={this.setAttributeVal('responsive')}
							checked={responsive}
						/>
					</PanelBody>
				</InspectorControls>
				<div ref={this.containerRef}></div>
			</>
		);
	}
}

export default compose([
	withSelect((select) => {
		const { getSettings } = select('core/block-editor');
		const { __experimentalMediaUpload } = getSettings();
		return {
			mediaUpload: __experimentalMediaUpload,
		};
	}),
	withNotices,
	withInstanceId,
])(VideoEdit);
