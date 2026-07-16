// Divi dependencies.
import { type Metadata, type ModuleLibrary } from '@divi/types';

// Local dependencies.
import { SmartVideoEdit } from './edit';
import { SmartVideoAttrs } from './types';

// Static config (metadata, placeholderContent, conversionOutline) is injected
// by PHP as window.smartvideoDivi5* globals to keep ~10KB of static data
// out of the JS bundle.
declare global {
	interface Window {
		smartvideoDivi5Metadata: Metadata.Values<SmartVideoAttrs>;
		smartvideoDivi5Placeholder: SmartVideoAttrs;
		smartvideoDivi5ConversionOutline: any;
	}
}

export const smartVideoModule: ModuleLibrary.Module.RegisterDefinition<SmartVideoAttrs> =
	{
		metadata: window.smartvideoDivi5Metadata,
		placeholderContent: window.smartvideoDivi5Placeholder,
		conversionOutline: window.smartvideoDivi5ConversionOutline,
		renderers: {
			edit: SmartVideoEdit,
		},
	};
