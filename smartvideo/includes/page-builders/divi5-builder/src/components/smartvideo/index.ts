// Divi dependencies.
import { type Metadata, type ModuleLibrary } from '@divi/types';

// Local dependencies.
import metadata from './module.json';
import { SmartVideoEdit } from './edit';
import { SmartVideoAttrs } from './types';
import { placeholderContent } from './placeholder-content';
import { conversionOutline } from './conversion-outline';

export const smartVideoModule: ModuleLibrary.Module.RegisterDefinition<SmartVideoAttrs> =
	{
		metadata: metadata as Metadata.Values<SmartVideoAttrs>,
		placeholderContent,
		conversionOutline,
		renderers: {
			edit: SmartVideoEdit,
		},
	};
