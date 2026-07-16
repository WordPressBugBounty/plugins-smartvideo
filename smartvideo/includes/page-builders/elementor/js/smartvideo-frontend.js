/**
 * SmartVideo Elementor frontend handler.
 *
 * When a SmartVideo widget is added or updated in the Elementor editor,
 * the <smartvideo> custom element is AJAX-rendered and inserted into the
 * preview iframe. The player initializes but poster extraction can fail
 * due to timing (video not yet loaded). Re-triggering the custom element
 * after a short delay fixes this.
 * @param {Function} $ jQuery.
 */
(function ($) {
	const handler = function ($scope) {
		// Only act inside the Elementor editor, not on the live frontend.
		if (!window.elementorFrontend || !elementorFrontend.isEditMode()) {
			return;
		}

		const el = $scope.find('smartvideo')[0];
		if (!el || !el.parentNode) {
			return;
		}

		// Short delay lets the AJAX-rendered content settle, then
		// clone-and-replace to re-trigger connectedCallback.
		setTimeout(function () {
			if (!el.parentNode) {
				return;
			}
			const clone = el.cloneNode(true);
			// Clear any player-generated inner content so the custom
			// element reinitializes from scratch.
			clone.innerHTML = '';
			el.parentNode.replaceChild(clone, el);
		}, 500);
	};

	$(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/smartvideo.default',
			handler
		);
	});
})(jQuery);
