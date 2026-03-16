import { omit } from 'lodash';
import { addAction } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';

import { smartVideoModule } from './components/smartvideo';

import './module-icons';

// Register SmartVideo module with Divi 5.
addAction(
	'divi.moduleLibrary.registerModuleLibraryStore.after',
	'smartvideo',
	() => {
		registerModule(
			smartVideoModule.metadata,
			omit(smartVideoModule, 'metadata')
		);
	}
);
