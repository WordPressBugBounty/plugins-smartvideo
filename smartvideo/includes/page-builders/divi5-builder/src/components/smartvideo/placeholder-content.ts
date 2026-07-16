import { SmartVideoAttrs } from './types';

/**
 * Placeholder content shown when the module is first added.
 * Uses a sample SmartVideo URL so users can see the module working immediately.
 *
 * Uses the new schema (videoUrl, declared in module.json). The legacy
 * (videoSource + mediaLibrary) shape used previously did not round-trip
 * through D5 storage because module.json doesn't declare videoSource —
 * it worked only by accident via the mediaLibrary short-circuit in
 * edit.tsx and RenderCallbackTrait.php.
 */
export const placeholderContent: SmartVideoAttrs = {
	videoUrl: {
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
