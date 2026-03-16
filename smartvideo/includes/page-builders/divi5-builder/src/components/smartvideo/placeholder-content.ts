import { SmartVideoAttrs } from './types';

/**
 * Placeholder content shown when the module is first added.
 * Uses a sample SmartVideo URL so users can see the module working immediately.
 */
export const placeholderContent: SmartVideoAttrs = {
	videoSource: {
		innerContent: {
			desktop: {
				value: 'media_library',
			},
		},
	},
	mediaLibrary: {
		innerContent: {
			desktop: {
				value: 'https://swarmify.com/wp-content/uploads/SmartVideoIntroMain.mp4',
			},
		},
	},
	videoWidth: {
		innerContent: {
			desktop: {
				value: '1280',
			},
		},
	},
	videoHeight: {
		innerContent: {
			desktop: {
				value: '720',
			},
		},
	},
	controls: {
		innerContent: {
			desktop: {
				value: 'on',
			},
		},
	},
	responsive: {
		innerContent: {
			desktop: {
				value: 'on',
			},
		},
	},
};
