/**
 * SmartVideo Elementor editor — migrate old source-type fields into the new
 * unified video_url field when the widget panel is opened.
 */
(function () {
	// Track which element IDs we've already migrated this session.
	const migrated = {};

	function waitForElementor() {
		if (typeof elementor === 'undefined' || !elementor.hooks) {
			setTimeout(waitForElementor, 500);
			return;
		}

		elementor.hooks.addAction(
			'panel/open_editor/widget/smartvideo',
			function (panel, model) {
				const elId = model.get('id') || model.id;

				if (migrated[elId]) {
					return;
				}

				// Short delay so the panel controls exist in DOM, then migrate.
				setTimeout(function () {
					const result = migrateSettings(model, elId);
					if (result) {
						// Update the DOM input directly since Elementor's
						// panel doesn't re-read from the model automatically.
						setTimeout(function () {
							if (result.video_url) {
								const input = document.querySelector(
									'.elementor-control-video_url input[data-setting="video_url"]'
								);
								if (input) {
									input.value = result.video_url;
								}
							}
							if (result.video_source_type) {
								const select = document.querySelector(
									'.elementor-control-video_source_type select[data-setting="video_source_type"]'
								);
								if (select) {
									select.value = result.video_source_type;
								}
							}
						}, 50);
					}
				}, 100);
			}
		);
	}

	/**
	 * Returns the applied settings object if migration ran, false if skipped.
	 * @param {Object} model Elementor element model.
	 * @param {string} elId  Element ID.
	 */
	function migrateSettings(model, elId) {
		const settings = model.get('settings');
		if (!settings) {
			migrated[elId] = true;
			return false;
		}

		if (settings.get('video_url')) {
			migrated[elId] = true;
			return false;
		}

		const videoType = settings.get('video_type') || '';
		let derived = '';
		let newSourceType = 'url';

		switch (videoType) {
			case 'youtube':
				derived = settings.get('youtube') || '';
				break;
			case 'vimeo':
				derived = settings.get('vimeo') || '';
				break;
			case 'swarmify_url':
				derived = settings.get('swarmify_url') || '';
				break;
			case 'another_source': {
				const another = settings.get('another_source');
				derived = another && another.url ? another.url : '';
				break;
			}
			case 'media_library':
				newSourceType = 'media_library';
				break;
		}

		let newSettings = {};
		if ('media_library' === newSourceType) {
			newSettings = { video_source_type: 'media_library' };
		} else if (derived) {
			newSettings = { video_source_type: 'url', video_url: derived };
		} else {
			migrated[elId] = true;
			return false;
		}

		// Use Elementor's command API for proper per-element scoping.
		if (typeof $e !== 'undefined' && $e.run) {
			try {
				const container = model.getContainer
					? model.getContainer()
					: elementor.getContainer(elId);

				if (container) {
					$e.run('document/elements/settings', {
						container,
						settings: newSettings,
					});
					migrated[elId] = true;
					return newSettings;
				}
			} catch (e) {
				if (window.console && window.console.warn) {
					console.warn(
						'[SmartVideo] Elementor $e.run migration failed:',
						e
					);
				}
			}
		}

		// Fallback: direct model set.
		for (const key in newSettings) {
			settings.set(key, newSettings[key]);
		}
		migrated[elId] = true;
		return newSettings;
	}

	if (document.readyState === 'complete') {
		waitForElementor();
	} else {
		window.addEventListener('load', waitForElementor);
	}
})();
