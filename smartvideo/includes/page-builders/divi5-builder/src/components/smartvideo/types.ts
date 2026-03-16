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
	videoSource?: StringAttr;
	mediaLibrary?: StringAttr;
	youtubeUrl?: StringAttr;
	vimeoUrl?: StringAttr;
	anotherSource?: StringAttr;

	// Poster settings.
	posterSource?: StringAttr;
	posterImage?: StringAttr;
	posterUrl?: StringAttr;

	// Dimensions.
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
