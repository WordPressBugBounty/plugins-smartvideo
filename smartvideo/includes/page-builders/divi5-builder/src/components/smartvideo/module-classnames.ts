import { ModuleClassnamesParams } from '@divi/module';
import { SmartVideoAttrs } from './types';

/**
 * Module classnames function for SmartVideo.
 *
 * SmartVideo has no custom classnames beyond the default module class.
 * @param root0
 * @param root0.classnamesInstance
 * @param root0.attrs
 */
export const moduleClassnames = ({
	classnamesInstance,
	attrs,
}: ModuleClassnamesParams<SmartVideoAttrs>): void => {
	// No custom classnames needed.
};
