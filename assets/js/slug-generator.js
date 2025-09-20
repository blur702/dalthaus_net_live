/**
 * Slug Generator for CMS
 * Automatically generates URL-friendly slugs from titles
 */

(function() {
    'use strict';

    /**
     * Convert a string to a URL-friendly slug
     * @param {string} text - The text to convert to slug
     * @return {string} - URL-friendly slug
     */
    function generateSlug(text) {
        return text
            .toString()                     // Convert to string
            .toLowerCase()                   // Convert to lowercase
            .trim()                          // Remove whitespace from both ends
            .replace(/[\s\W-]+/g, '-')       // Replace spaces and non-word chars with -
            .replace(/^-+|-+$/g, '')         // Remove leading/trailing hyphens
            .substring(0, 100);              // Limit to 100 characters (field maxlength)
    }

    /**
     * Setup slug generation for a form
     * @param {string} titleId - ID of the title input field
     * @param {string} slugId - ID of the slug/url_alias input field
     */
    function setupSlugGeneration(titleId, slugId) {
        const titleField = document.getElementById(titleId);
        const slugField = document.getElementById(slugId);

        if (!titleField || !slugField) {
            return;
        }

        // Track if user has manually edited the slug
        let slugManuallyEdited = false;

        // Check if slug already has a value (edit mode)
        if (slugField.value && slugField.value.trim() !== '') {
            slugManuallyEdited = true;
        }

        // Listen for manual edits to the slug field
        slugField.addEventListener('input', function() {
            // If user types in the slug field, stop auto-generation
            slugManuallyEdited = true;
        });

        // Clear the manual edit flag if user clears the slug field
        slugField.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                slugManuallyEdited = false;
            }
        });

        // Auto-generate slug from title
        titleField.addEventListener('input', function() {
            // Only auto-generate if user hasn't manually edited the slug
            if (!slugManuallyEdited) {
                const slug = generateSlug(this.value);
                slugField.value = slug;

                // Update the field's visual state to show it has content
                if (slug) {
                    slugField.classList.remove('border-red-300', 'bg-red-50');
                }
            }
        });

        // Add a reset button next to the slug field (optional enhancement)
        const slugContainer = slugField.parentElement;
        if (slugContainer && !slugContainer.querySelector('.slug-reset-btn')) {
            const resetButton = document.createElement('button');
            resetButton.type = 'button';
            resetButton.className = 'slug-reset-btn mt-2 text-sm text-blue-600 hover:text-blue-800';
            resetButton.textContent = 'Auto-generate from title';
            resetButton.style.display = slugManuallyEdited ? 'block' : 'none';

            resetButton.addEventListener('click', function(e) {
                e.preventDefault();
                const slug = generateSlug(titleField.value);
                slugField.value = slug;
                slugManuallyEdited = false;
                this.style.display = 'none';
            });

            // Show/hide reset button based on manual edit status
            slugField.addEventListener('input', function() {
                resetButton.style.display = slugManuallyEdited ? 'block' : 'none';
            });

            // Insert after the helper text if it exists
            const helperText = slugContainer.querySelector('.text-gray-500');
            if (helperText) {
                helperText.parentNode.insertBefore(resetButton, helperText.nextSibling);
            } else {
                slugContainer.appendChild(resetButton);
            }
        }
    }

    /**
     * Initialize slug generation when DOM is ready
     */
    function init() {
        // Setup for content forms (articles and photobooks)
        setupSlugGeneration('title', 'url_alias');

        // Also handle any additional forms that might be on the page
        // This covers both create and edit forms
        const forms = document.querySelectorAll('form#contentForm, form#pageForm');
        forms.forEach(function(form) {
            const titleInput = form.querySelector('input[name="title"]');
            const slugInput = form.querySelector('input[name="url_alias"]');
            if (titleInput && slugInput) {
                setupSlugGeneration(titleInput.id, slugInput.id);
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM is already loaded
        init();
    }

    // Export for testing or manual use
    window.SlugGenerator = {
        generate: generateSlug,
        setup: setupSlugGeneration,
        init: init
    };
})();