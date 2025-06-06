/**
 * Native Dialog Implementation for SmartVideo
 * Replaces Fancybox with native HTML dialog elements
 */
(function($) {
    'use strict';

    // Dialog manager to handle nested dialogs and backdrop
    const SwarmifyDialogManager = {
        openDialogs: [],
        backdropElement: null,

        init: function() {
            // Create backdrop element
            this.backdropElement = document.createElement('div');
            this.backdropElement.className = 'swarmify-dialog-backdrop';
            document.body.appendChild(this.backdropElement);

            // Handle ESC key to close dialogs
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && SwarmifyDialogManager.openDialogs.length > 0) {
                    SwarmifyDialogManager.closeTopDialog();
                }
            });

            // Handle click outside to close
            this.backdropElement.addEventListener('click', function() {
                SwarmifyDialogManager.closeTopDialog();
            });
        },

        openDialog: function(dialogElement) {
            if (!dialogElement) return;
            
            // If dialog is already open, do nothing
            if (this.openDialogs.includes(dialogElement)) return;
            
            // Add to open dialogs stack
            this.openDialogs.push(dialogElement);
            
            // Show backdrop if this is the first dialog
            if (this.openDialogs.length === 1) {
                this.backdropElement.classList.add('active');
                document.body.classList.add('swarmify-dialog-open');
            }
            
            // Show dialog
            dialogElement.classList.add('active');
            dialogElement.setAttribute('aria-hidden', 'false');
            
            // Set focus to the first focusable element
            setTimeout(function() {
                const focusable = dialogElement.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusable.length) {
                    focusable[0].focus();
                }
            }, 50);
        },

        closeDialog: function(dialogElement) {
            if (!dialogElement) return;
            
            // Remove from open dialogs stack
            const index = this.openDialogs.indexOf(dialogElement);
            if (index !== -1) {
                this.openDialogs.splice(index, 1);
            }
            
            // Hide dialog
            dialogElement.classList.remove('active');
            dialogElement.setAttribute('aria-hidden', 'true');
            
            // Hide backdrop if no more dialogs
            if (this.openDialogs.length === 0) {
                this.backdropElement.classList.remove('active');
                document.body.classList.remove('swarmify-dialog-open');
            }
        },

        closeTopDialog: function() {
            if (this.openDialogs.length > 0) {
                this.closeDialog(this.openDialogs[this.openDialogs.length - 1]);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SwarmifyDialogManager.init();
        
        // Open dialog when clicking elements with data-dialog attribute
        $(document).on('click', '[data-dialog]', function(e) {
            e.preventDefault();
            const targetSelector = $(this).data('dialog');
            const targetDialog = document.querySelector(targetSelector);
            
            if (targetDialog) {
                // Convert to dialog if not already
                if (!targetDialog.hasAttribute('role')) {
                    convertToDialog(targetDialog);
                }
                
                SwarmifyDialogManager.openDialog(targetDialog);
            }
        });
        
        // Close dialog when clicking elements with data-dialog-close attribute
        $(document).on('click', '[data-dialog-close]', function() {
            const dialog = $(this).closest('.swarmify-dialog')[0];
            if (dialog) {
                SwarmifyDialogManager.closeDialog(dialog);
            }
        });
        
        // Initialize all dialog elements
        initializeDialogs();
    });

    // Convert a div to a proper dialog
    function convertToDialog(element) {
        if (!element) return;
        
        // Add dialog attributes
        element.classList.add('swarmify-dialog');
        element.setAttribute('role', 'dialog');
        element.setAttribute('aria-modal', 'true');
        element.setAttribute('aria-hidden', 'true');
        
        // Make sure it's visible in the DOM but hidden by default
        element.style.display = '';
    }

    // Initialize all dialog elements
    function initializeDialogs() {
        // Initialize all potential dialog containers
        $('.video_url_popup, #image_url_popup, #swarmify-modal-content').each(function() {
            convertToDialog(this);
        });
    }

    // Expose SwarmifyDialogManager to the global scope for use in other scripts
    window.SwarmifyDialogManager = SwarmifyDialogManager;

})(jQuery);
