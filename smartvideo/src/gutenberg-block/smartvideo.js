const ASPECT_RATIO_PRESETS = {
	'16:9': ['640', '360'],
	'4:3': ['640', '480'],
	'21:9': ['640', '274'],
	'9:16': ['360', '640'],
	'1:1': ['640', '640'],
};

export function resolveAspectRatio(ratio, width, height) {
	if (ASPECT_RATIO_PRESETS[ratio]) {
		return ASPECT_RATIO_PRESETS[ratio];
	}
	return [width || '1280', height || '720'];
}

export function getSmartVideoElem(props) {
	const {
		smartvideoEmbedLink,
		poster,
		posterUrl,
		width,
		height,
		aspectRatio,
		responsive,
		autoplay,
		loop,
		muted,
		controls,
		playsInline,
		preload,
	} = props.attributes;

	const [resolvedWidth, resolvedHeight] = resolveAspectRatio(
		aspectRatio,
		width,
		height
	);

	const responsiveClass = responsive ? 'swarm-fluid' : '';
	const newSmartVideo = document.createElement('smartvideo');

	newSmartVideo.setAttribute('src', smartvideoEmbedLink);
	newSmartVideo.setAttribute('width', resolvedWidth);
	newSmartVideo.setAttribute('height', resolvedHeight);

	if ('none' !== poster && posterUrl) {
		newSmartVideo.setAttribute('poster', posterUrl);
	}

	newSmartVideo.setAttribute('class', responsiveClass);

	if (autoplay) {
		newSmartVideo.setAttribute('autoplay', '');
	}
	if (muted) {
		newSmartVideo.setAttribute('muted', '');
	}
	if (loop) {
		newSmartVideo.setAttribute('loop', '');
	}
	if (controls) {
		newSmartVideo.setAttribute('controls', '');
	}
	if (playsInline) {
		newSmartVideo.setAttribute('playsinline', '');
	}
	if (preload && preload !== 'auto') {
		newSmartVideo.setAttribute('preload', preload);
	}

	return newSmartVideo;
}
