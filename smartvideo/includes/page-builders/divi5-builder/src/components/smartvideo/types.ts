// Divi dependencies.
import { ModuleEditProps } from '@divi/module-library';
import {
	FormatBreakpointStateAttr,
	InternalAttrs,
	type Element,
	type Module,
} from '@divi/types';

/**
 * Breakpoint-aware string attribute.
 * Value is accessed via attrs?.attrName?.innerContent?.desktop?.value
 */
type StringAttr = {
	innerContent?: FormatBreakpointStateAttr<string>;
};

export interface SmartVideoAttrs extends InternalAttrs {
	// Module wrapper (standard Divi decorations).
	module?: {
		meta?: Element.Meta.Attributes;
		advanced?: {
			htmlAttributes?: Element.Advanced.IdClasses.Attributes;
		};
		decoration?: Element.Decoration.PickedAttributes<
			| 'animation'
			| 'background'
			| 'border'
			| 'boxShadow'
			| 'disabledOn'
			| 'filters'
			| 'overflow'
			| 'position'
			| 'scroll'
			| 'sizing'
			| 'spacing'
			| 'sticky'
			| 'transform'
			| 'transition'
			| 'zIndex'
		>;
	};

	// Video source settings.
	// New schema (declared in module.json, source of truth for new modules).
	videoUrl?: StringAttr;
	mediaLibrary?: StringAttr;
	// Legacy schema (NOT in module.json; populated only via D4→D5 conversion).
	// edit.tsx and RenderCallbackTrait.php still read these as a fallback so
	// converted-from-D4 modules continue to render. Full removal is deferred
	// (CHANGELOG-unreleased.md) — needs a content migration step.
	videoSource?: StringAttr;
	youtubeUrl?: StringAttr;
	vimeoUrl?: StringAttr;
	anotherSource?: StringAttr;
	swarmifyUrl?: StringAttr;

	// Poster settings.
	posterSource?: StringAttr;
	posterImage?: StringAttr;
	posterUrl?: StringAttr;

	// Dimensions.
	aspectRatio?: StringAttr;
	videoWidth?: StringAttr;
	videoHeight?: StringAttr;

	// Playback options.
	autoplay?: StringAttr;
	muted?: StringAttr;
	loop?: StringAttr;
	controls?: StringAttr;
	playsinline?: StringAttr;
	responsive?: StringAttr;
}

export type SmartVideoEditProps = ModuleEditProps<SmartVideoAttrs>;
