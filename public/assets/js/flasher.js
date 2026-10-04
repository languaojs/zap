/**
 * Zap Framework Core JS - Flasher
 */
const Flasher = {
    // Icon mapping matching server-side Flasher.php HTML entities
    icons: {
        success: '&#10004;',
        info:    '&#8505;',
        warning: '&#9888;',
        error:   '&#10006;'
    },

    init: function () {
        this.processExisting();
    },

    /**
     * Process any flash messages rendered by PHP on page load
     */
    processExisting: function () {
        const flashMessages = document.querySelectorAll('.flash-message');
        flashMessages.forEach((flashMsg, index) => {
            this.scheduleDismissal(flashMsg, index);
        });
    },

    /**
     * Trigger a flash message dynamically from JavaScript
     * Usage: Flasher.show('success', 'Saved', 'Data updated successfully');
     */
    show: function (type, title, message) {
        let container = document.querySelector('.flash-container');

        // Create container if it doesn't exist on the page yet
        if (!container) {
            container = document.createElement('div');
            container.className = 'flash-container';
            document.body.appendChild(container);
        }

        const icon = this.icons[type] || this.icons.info;

        // Build element matching the PHP template markup
        const flashMsg = document.createElement('div');
        flashMsg.className = `flash-message flash-${type}`;
        flashMsg.innerHTML = `
            <div class="flash-icon">${icon}</div>
            <div class="flash-body">
                <div class="flash-title">${this.escapeHtml(title)}</div>
                <div class="flash-text">${this.escapeHtml(message)}</div>
            </div>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(flashMsg);

        // Schedule auto-dismissal (index 0 so it stays up for full duration)
        this.scheduleDismissal(flashMsg, 0);
    },

    /**
     * Handles timed fade-out and DOM removal
     */
    scheduleDismissal: function (flashMsg, index = 0) {
        const baseDelay = 5000;
        const staggerDelay = 1000;
        const hideDelay = baseDelay + (index * staggerDelay);

        setTimeout(() => {
            flashMsg.classList.add('hide');
            setTimeout(() => {
                flashMsg.remove();
            }, 400); // Matches CSS transition duration
        }, hideDelay);
    },

    /**
     * Helper to sanitize text input for JS-generated messages
     */
    escapeHtml: function (str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

// Start when the DOM is ready
document.addEventListener('DOMContentLoaded', () => Flasher.init());