import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';

const SmartVideoSidebarPanel = () => {
	const postType = useSelect(
		(select) => select('core/editor').getCurrentPostType(),
		[]
	);

	const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

	if (!meta) {
		return null;
	}

	const isDisabled = meta._smartvideo_disabled || false;

	return (
		<PluginDocumentSettingPanel
			name="smartvideo-settings"
			title={__('SmartVideo', 'swarmify')}
		>
			<ToggleControl
				__nextHasNoMarginBottom
				label={__('Disable SmartVideo on this page', 'swarmify')}
				help={
					isDisabled
						? __(
								'SmartVideo player will not load on this page.',
								'swarmify'
							)
						: __(
								'SmartVideo player will load normally.',
								'swarmify'
							)
				}
				checked={isDisabled}
				onChange={(value) => {
					setMeta({ ...meta, _smartvideo_disabled: value });
				}}
			/>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin('smartvideo-sidebar', {
	render: SmartVideoSidebarPanel,
	icon: 'video-alt3',
});
