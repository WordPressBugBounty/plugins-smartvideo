import React, { ReactElement } from 'react';

// SmartVideo icon for D5 module panel — hexagonal play button at 16x16 grid.
export const name = 'smartvideo/smartvideo-icon';
export const viewBox = '0 0 16 16';
export const component = (): ReactElement => (
	<g>
		<path d="M8 1.1L2.2 4.45v7.1L8 14.9l5.8-3.35V4.45L8 1.1zm4.8 9.85L8 13.7l-4.8-2.75V5.05L8 2.3l4.8 2.75v6.7z" />
		<path d="M6.5 5.5v5l4.3-2.5-4.3-2.5z" />
	</g>
);
