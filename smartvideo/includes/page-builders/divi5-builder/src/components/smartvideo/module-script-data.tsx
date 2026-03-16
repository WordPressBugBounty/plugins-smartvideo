import React, { Fragment, ReactElement } from 'react';

import { ModuleScriptDataProps } from '@divi/module';
import { SmartVideoAttrs } from './types';

/**
 * SmartVideo module script data component.
 * @param root0
 * @param root0.elements
 */
export const ModuleScriptData = ({
	elements,
}: ModuleScriptDataProps<SmartVideoAttrs>): ReactElement => (
	<Fragment>
		{elements.scriptData({
			attrName: 'module',
		})}
	</Fragment>
);
