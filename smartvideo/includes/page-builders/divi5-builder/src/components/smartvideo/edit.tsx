// External Dependencies.
import React, { ReactElement, useEffect, useRef, useState } from 'react';

// Divi Dependencies.
import { ModuleContainer } from '@divi/module';

// Local Dependencies.
import { SmartVideoEditProps } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';

/**
 * Parse YouTube ID from a URL.
 * @param url
 */
const parseYouTubeId = (url: string): string | null => {
	const match = url.match(
		/^(?:https?:\/\/)?(?:(?:www|m|music)\.)?(?:youtube(?:-nocookie)?\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?|shorts|live)\/|.*[?&]v=)|youtu\.be\/)([A-Za-z0-9_-]{11})/
	);
	return match ? match[1] : null;
};

/**
 * Helper to get innerContent value from attrs (desktop breakpoint).
 * @param attr
 * @param fallback
 */
const getVal = (attr: any, fallback: string = ''): string => {
	return attr?.innerContent?.desktop?.value ?? fallback;
};

/**
 * SmartVideo edit component for the Divi 5 Visual Builder.
 *
 * Renders a <smartvideo> custom element inside a ModuleContainer,
 * matching the D4 module's visual behavior.
 * @param props
 */
const SmartVideoEdit = (props: SmartVideoEditProps): ReactElement => {
	const { attrs, id, name, elements } = props;

	const containerRef = useRef<HTMLDivElement>(null);

	// Aspect ratio presets (must match PHP AspectRatio::PRESETS).
	const ASPECT_RATIO_PRESETS: Record<string, [string, string]> = {
		'16:9': ['640', '360'],
		'4:3': ['640', '480'],
		'21:9': ['640', '274'],
		'9:16': ['360', '640'],
		'1:1': ['640', '640'],
	};

	// Read attribute values.
	const videoUrlAttr = getVal(attrs?.videoUrl);
	const mediaLibrary = getVal(attrs?.mediaLibrary);
	// Old attributes for backward compat.
	const videoSource = getVal(attrs?.videoSource, 'media_library');
	const youtubeUrl = getVal(attrs?.youtubeUrl);
	const vimeoUrl = getVal(attrs?.vimeoUrl);
	const anotherSource = getVal(attrs?.anotherSource);
	const swarmifyUrlVal = getVal(attrs?.swarmifyUrl);
	const posterSource = getVal(attrs?.posterSource, 'none');
	const posterImage = getVal(attrs?.posterImage);
	const posterUrl = getVal(attrs?.posterUrl);
	const aspectRatio = getVal(attrs?.aspectRatio, '');
	const videoWidth = getVal(attrs?.videoWidth, '1280');
	const videoHeight = getVal(attrs?.videoHeight, '720');
	const autoplay = getVal(attrs?.autoplay, 'off');
	const muted = getVal(attrs?.muted, 'off');
	const loop = getVal(attrs?.loop, 'off');
	const controls = getVal(attrs?.controls, 'on');
	const playsinline = getVal(attrs?.playsinline, 'off');
	const responsive = getVal(attrs?.responsive, 'on');

	// Resolve video URL: new videoUrl field first, then media library, then old fields.
	let videoUrl = '';
	if (videoUrlAttr) {
		const ytId = parseYouTubeId(videoUrlAttr);
		videoUrl = ytId ? `https://www.youtube.com/embed/${ytId}` : videoUrlAttr;
	} else if (mediaLibrary) {
		videoUrl = mediaLibrary;
	} else {
		// Backward compat: resolve from old source-type attributes.
		switch (videoSource) {
			case 'media_library':
				videoUrl = mediaLibrary;
				break;
			case 'youtube': {
				const ytId = youtubeUrl ? parseYouTubeId(youtubeUrl) : null;
				videoUrl = ytId ? `https://www.youtube.com/embed/${ytId}` : '';
				break;
			}
			case 'vimeo':
				videoUrl = vimeoUrl;
				break;
			case 'swarmify_url':
				videoUrl = swarmifyUrlVal;
				break;
			case 'another_source':
				videoUrl = anotherSource;
				break;
		}
	}

	// Resolve poster.
	const poster =
		posterSource === 'media_library'
			? posterImage
			: posterSource === 'another_source'
				? posterUrl
				: '';

	// Check activation status.
	const isActive =
		(window as any).smartvideoBlockData &&
		(window as any).smartvideoBlockData.isActive;

	// Probe direct video URLs for native dimensions (aspect ratio).
	// YouTube/Vimeo default to 16:9 since we can't probe cross-origin.
	const [detectedWidth, setDetectedWidth] = useState<number | null>(null);
	const [detectedHeight, setDetectedHeight] = useState<number | null>(null);

	const isYouTubeOrVimeo = videoUrl.includes('youtube.com/embed/') || videoUrl.includes('vimeo.com');

	useEffect(() => {
		if (isYouTubeOrVimeo || !videoUrl) {
			setDetectedWidth(null);
			setDetectedHeight(null);
			return;
		}

		const probe = document.createElement('video');
		probe.preload = 'metadata';
		probe.muted = true;
		probe.style.display = 'none';

		const cleanup = () => {
			probe.removeAttribute('src');
			probe.load();
			probe.remove();
		};

		probe.addEventListener('loadedmetadata', () => {
			if (probe.videoWidth && probe.videoHeight) {
				setDetectedWidth(probe.videoWidth);
				setDetectedHeight(probe.videoHeight);
			}
			cleanup();
		});

		probe.addEventListener('error', () => {
			setDetectedWidth(null);
			setDetectedHeight(null);
			cleanup();
		});

		probe.src = videoUrl;
	}, [videoUrl, isYouTubeOrVimeo]);

	// Resolve dimensions: preset > user-configured > auto-detected (backward compat only).
	const presetDims = ASPECT_RATIO_PRESETS[aspectRatio];
	let effectiveWidth: string, effectiveHeight: string;
	if (presetDims) {
		// Standard preset selected — use preset dimensions.
		effectiveWidth = presetDims[0];
		effectiveHeight = presetDims[1];
	} else if (aspectRatio === 'custom') {
		// Explicit custom — always honor user's values.
		effectiveWidth = videoWidth;
		effectiveHeight = videoHeight;
	} else {
		// No aspect ratio field (backward compat) — detected > user's values.
		effectiveWidth = detectedWidth ? String(detectedWidth) : videoWidth;
		effectiveHeight = detectedHeight ? String(detectedHeight) : videoHeight;
	}

	// Build <smartvideo> element via DOM manipulation.
	useEffect(() => {
		const container = containerRef.current;
		if (!container) {
			return;
		}

		// Clear previous content.
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}

		if (!videoUrl) {
			container.innerHTML =
				'<div style="padding:40px;text-align:center;color:#999;border:2px dashed #ddd;border-radius:8px">Paste a video URL or choose from Media Library to preview your SmartVideo.</div>';
			return;
		}

		const el = document.createElement('smartvideo');
		el.setAttribute('src', videoUrl);
		el.setAttribute('width', effectiveWidth);
		el.setAttribute('height', effectiveHeight);

		if (poster && posterSource !== 'none') {
			el.setAttribute('poster', poster);
		}
		if (responsive === 'on') {
			el.setAttribute('class', 'swarm-fluid');
		}

		const toggles: Record<string, string> = {
			autoplay, muted, loop, controls, playsinline,
		};
		Object.keys(toggles).forEach((k) => {
			if (toggles[k] === 'on') el.setAttribute(k, k);
		});

		container.appendChild(el);
	}, [
		videoUrlAttr,
		mediaLibrary,
		videoSource,
		youtubeUrl,
		vimeoUrl,
		anotherSource,
		swarmifyUrlVal,
		posterSource,
		posterImage,
		posterUrl,
		effectiveWidth,
		effectiveHeight,
		autoplay,
		muted,
		loop,
		controls,
		playsinline,
		responsive,
		videoUrl,
		poster,
	]);

	// Hide irrelevant poster fields based on selected source.
	// Also hide width/height when a preset aspect ratio is selected.
	useEffect(() => {
		const posterHide: Record<string, string[]> = {
			none: ['poster-image', 'poster-url'],
			media_library: ['poster-url'],
			another_source: ['poster-image'],
		};

		// Hide width/height fields when a preset aspect ratio is selected.
		const dimensionHide =
			aspectRatio && aspectRatio !== 'custom'
				? ['video-width', 'video-height']
				: [];

		const fields = [
			...(posterHide[posterSource] || []),
			...dimensionHide,
		];

		const css = fields
			.map(
				(f) =>
					`.et-vb-field-${f}-inner-content { display: none !important; }`
			)
			.join('\n');

		// Inject into top window where the settings panel lives.
		const styleId = 'smartvideo-field-visibility';
		const targetDoc = (() => {
			try {
				return window.top?.document ?? document;
			} catch (_) {
				return document;
			}
		})();

		let styleEl = targetDoc.getElementById(
			styleId
		) as HTMLStyleElement | null;
		if (!styleEl) {
			styleEl = targetDoc.createElement('style');
			styleEl.id = styleId;
			targetDoc.head.appendChild(styleEl);
		}
		styleEl.textContent = css;
	}, [posterSource, aspectRatio]);

	return (
		<ModuleContainer
			attrs={attrs}
			elements={elements}
			id={id}
			name={name}
			stylesComponent={ModuleStyles}
			classnamesFunction={moduleClassnames}
			scriptDataComponent={ModuleScriptData}
		>
			{elements.styleComponents({
				attrName: 'module',
			})}
			{!isActive && (
				<div
					style={{
						background: '#fcf0c0',
						border: '1px solid #d4a72c',
						borderRadius: '4px',
						padding: '8px 12px',
						marginBottom: '10px',
						fontSize: '13px',
						color: '#6b5900',
					}}
				>
					SmartVideo is currently disabled. Enable it in{' '}
					<a
						href="/wp-admin/admin.php?page=SmartVideo.php"
						target="_blank"
						rel="noopener noreferrer"
					>
						Settings
					</a>{' '}
					for videos to play.
				</div>
			)}
			<div ref={containerRef} />
		</ModuleContainer>
	);
};

export { SmartVideoEdit };
