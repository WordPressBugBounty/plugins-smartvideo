import { addFilter } from '@wordpress/hooks';
import { smartVideoIcon } from './icons';

// Add the SmartVideo icon to Divi's icon library.
addFilter('divi.iconLibrary.icon.map', 'smartvideo', (icons) => {
	return {
		...icons,
		[smartVideoIcon.name]: smartVideoIcon,
	};
});
