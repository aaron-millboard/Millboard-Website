/**
 * Downloads component - handles share action for files.
 */
export default class Downloads {
    /**
     * @param {HTMLElement} element The dloads component container element.
     */
    constructor(element) {
        this.element = element;
        this.shareButtons = Array.from(this.element.querySelectorAll('.downloads__file-action--share'));

        if (!this.shareButtons.length) return;

        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        this.shareButtons.forEach((button) => {
            button.addEventListener('click', this.handleShare.bind(this));
        });
    }

    /**
     * Share file using Web Share API or fallback to copy to clipboard
     * @param {Event} event Click event
     */
    async handleShare(event) {
        event.preventDefault();

        const button = event.currentTarget;
        const url = button.dataset.url;
        const title = button.dataset.title || 'File';

        if (!url) return;

        // Try Web Share API
        if (navigator.share) {
            try {
                await navigator.share({
                    title: title,
                    url: url,
                });
            } catch (error) {
                // User cancelled or error occurred
                if (error.name !== 'AbortError') {
                    console.error('Error sharing:', error);
                    this.fallbackCopyToClipboard(url, button);
                }
            }
        } else {
            // Fallback for firefox
            this.fallbackCopyToClipboard(url, button);
        }
    }

    /**
     * Fallback: Copy URL to clipboard
     * @param {string} url URL to copy
     * @param {HTMLElement} button Button element for user feedback
     */
    async fallbackCopyToClipboard(url, button) {
        try {
            // call clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(url);
                this.showFeedback(button, 'Link copied');
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    this.showFeedback(button, 'Link copied');
                } catch (error) {
                    console.error('Copy failed:', error);
                    this.showFeedback(button, 'Copy failed', true);
                }
                
                document.body.removeChild(textArea);
            }
        } catch (error) {
            console.error('Copy to clipboard failed:', error);
            this.showFeedback(button, 'Copy failed', true);
        }
    }

    /**
     * Show temporary feedback to user
     * @param {HTMLElement} button Button element
     * @param {string} message Feedback message
     * @param {boolean} isError Whether this is an error message
     */
    showFeedback(button, message, isError = false) {

        const originalText = button.querySelector('.downloads__file-action-text') || button.querySelector('span');
        const originalTextContent = originalText ? originalText.textContent : '';
        
        if (originalText) {
            originalText.textContent = message;
        }
        
        button.classList.add(isError ? 'downloads__file-action--error' : 'downloads__file-action--success');
        
        setTimeout(() => {
            if (originalText) {
                originalText.textContent = originalTextContent;
            }
            button.classList.remove('downloads__file-action--error', 'downloads__file-action--success');
        }, 2000);
    }
}
