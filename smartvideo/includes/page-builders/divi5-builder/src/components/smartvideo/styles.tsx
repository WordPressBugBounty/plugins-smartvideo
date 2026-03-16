// External dependencies.
import React, { ReactElement } from 'react';

// Divi dependencies.
import { StyleContainer, StylesProps } from '@divi/module';

// Local dependencies.
import { SmartVideoAttrs } from './types';

/**
 * SmartVideo style components.
 *
 * SmartVideo doesn't have styled sub-elements (all rendering is via the
 * <smartvideo> custom element), so we only process the module wrapper styles.
 * @param root0
 * @param root0.attrs
 * @param root0.settings
 * @param root0.orderClass
 * @param root0.mode
 * @param root0.state
 * @param root0.noStyleTag
 * @param root0.elements
 */
const ModuleStyles = ({
	attrs,
	settings,
	orderClass,
	mode,
	state,
	noStyleTag,
	elements,
}: StylesProps<SmartVideoAttrs>): ReactElement => {
	return (
		<StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
			{elements.style({
				attrName: 'module',
				styleProps: {
					disabledOn: {
						disabledModuleVisibility:
							settings?.disabledModuleVisibility,
					},
				},
			})}
		</StyleContainer>
	);
};

export { ModuleStyles };
