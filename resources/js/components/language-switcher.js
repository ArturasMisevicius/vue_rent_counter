/**
 * Language Switcher Enhancement
 * Provides progressive enhancement for language switching functionality
 * with improved error handling and accessibility
 */
document.addEventListener('DOMContentLoaded', function() {
    const languageForms = document.querySelectorAll('.language-switcher-form');
    
    if (languageForms.length === 0) {
        return; // No language switchers found
    }
    
    // Add global styles for loading states
    addGlobalStyles();
    
    languageForms.forEach(form => {
        const select = form.querySelector('select[data-language-switcher]');
        
        if (!select) return;
        
        // Store original state for restoration
        const originalState = {
            disabled: select.disabled,
            opacity: select.style.opacity || '1'
        };
        
        // Add loading state during language switch
        select.addEventListener('change', function(event) {
            handleLanguageChange(this, form, originalState, event);
        });
        
        // Handle form submission errors
        form.addEventListener('submit', function() {
            storeCurrentUrl();
        });
        
        // Handle keyboard navigation
        select.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.dispatchEvent(new Event('change'));
            }
        });
    });
    
    // Handle back button after language switch
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            restoreAllSwitchers();
        }
    });
    
    // Handle visibility change (tab switching)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            restoreAllSwitchers();
        }
    });
});

/**
 * Handle language change with improved UX and error handling
 */
function handleLanguageChange(select, form, originalState, event) {
    const selectedValue = select.value;
    const baseUrl = select.dataset.baseUrl;
    
    // Validate required data
    if (!selectedValue || !baseUrl) {
        console.warn('Language switcher: Missing required data attributes');
        showError(select, 'Configuration error');
        return;
    }
    
    // Prevent multiple submissions
    if (select.dataset.submitting === 'true') {
        return;
    }
    
    try {
        // Set loading state
        setLoadingState(select, form, true);
        select.dataset.submitting = 'true';
        
        // Update form action
        const newAction = baseUrl + selectedValue;
        form.action = newAction;
        
        // Add loading indicator
        const spinner = createLoadingSpinner();
        form.appendChild(spinner);
        
        // Submit form after brief delay for UX
        setTimeout(() => {
            // Final validation before submit
            if (form.action && form.action.includes(selectedValue)) {
                form.submit();
            } else {
                throw new Error('Form action validation failed');
            }
        }, 150);
        
    } catch (error) {
        console.error('Language switcher: Failed to submit form', error);
        
        // Reset state on error
        restoreSwitcherState(select, form, originalState);
        showError(select, 'Failed to switch language');
        
        // Reset submitting flag
        delete select.dataset.submitting;
    }
}

/**
 * Set loading state for the switcher
 */
function setLoadingState(select, form, isLoading) {
    if (isLoading) {
        select.disabled = true;
        select.style.opacity = '0.6';
        select.setAttribute('aria-busy', 'true');
        
        // Add loading class to form
        form.classList.add('language-switcher-loading');
    } else {
        select.disabled = false;
        select.style.opacity = '1';
        select.removeAttribute('aria-busy');
        
        // Remove loading class from form
        form.classList.remove('language-switcher-loading');
    }
}

/**
 * Create loading spinner element
 */
function createLoadingSpinner() {
    const spinner = document.createElement('div');
    spinner.className = 'language-switcher-spinner';
    spinner.innerHTML = '⟳';
    spinner.setAttribute('aria-hidden', 'true');
    spinner.style.cssText = `
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        animation: language-switcher-spin 1s linear infinite;
        color: white;
        font-size: 14px;
        pointer-events: none;
        z-index: 10;
    `;
    
    return spinner;
}

/**
 * Add global styles for the language switcher
 */
function addGlobalStyles() {
    if (document.querySelector('#language-switcher-styles')) {
        return; // Styles already added
    }
    
    const styles = document.createElement('style');
    styles.id = 'language-switcher-styles';
    styles.textContent = `
        @keyframes language-switcher-spin {
            from { transform: translateY(-50%) rotate(0deg); }
            to { transform: translateY(-50%) rotate(360deg); }
        }
        
        .language-switcher-form {
            position: relative;
        }
        
        .language-switcher-loading {
            pointer-events: none;
        }
        
        .language-switcher-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px #ef4444 !important;
        }
        
        .language-switcher-error-message {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #ef4444;
            color: white;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 0 0 4px 4px;
            z-index: 20;
        }
    `;
    document.head.appendChild(styles);
}

/**
 * Store current URL for fallback navigation
 */
function storeCurrentUrl() {
    const currentUrl = window.location.href;
    
    try {
        sessionStorage.setItem('language-switcher-fallback', currentUrl);
        sessionStorage.setItem('language-switcher-timestamp', Date.now().toString());
    } catch (error) {
        console.warn('Language switcher: Could not store fallback URL', error);
    }
}

/**
 * Restore all language switchers to normal state
 */
function restoreAllSwitchers() {
    // Remove all loading spinners
    const spinners = document.querySelectorAll('.language-switcher-spinner');
    spinners.forEach(spinner => spinner.remove());
    
    // Restore all selects
    const selects = document.querySelectorAll('.language-switcher-form select');
    selects.forEach(select => {
        select.disabled = false;
        select.style.opacity = '1';
        select.removeAttribute('aria-busy');
        delete select.dataset.submitting;
        
        // Remove error states
        select.classList.remove('language-switcher-error');
        const errorMessage = select.parentNode.querySelector('.language-switcher-error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    });
    
    // Remove loading classes from forms
    const forms = document.querySelectorAll('.language-switcher-form');
    forms.forEach(form => {
        form.classList.remove('language-switcher-loading');
    });
}

/**
 * Restore specific switcher state
 */
function restoreSwitcherState(select, form, originalState) {
    select.disabled = originalState.disabled;
    select.style.opacity = originalState.opacity;
    select.removeAttribute('aria-busy');
    
    // Remove loading elements
    const spinner = form.querySelector('.language-switcher-spinner');
    if (spinner) {
        spinner.remove();
    }
    
    form.classList.remove('language-switcher-loading');
}

/**
 * Show error message for switcher
 */
function showError(select, message) {
    // Add error styling
    select.classList.add('language-switcher-error');
    
    // Remove existing error message
    const existingError = select.parentNode.querySelector('.language-switcher-error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'language-switcher-error-message';
    errorDiv.textContent = message;
    errorDiv.setAttribute('role', 'alert');
    
    select.parentNode.appendChild(errorDiv);
    
    // Remove error after 3 seconds
    setTimeout(() => {
        select.classList.remove('language-switcher-error');
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 3000);
}