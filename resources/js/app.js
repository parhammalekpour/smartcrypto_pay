import './bootstrap';
import './auto-refresh';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Dark Mode Management
class DarkModeManager {
    constructor() {
        this.htmlElement = document.documentElement;
        this.darkModeToggleSelector = '#darkModeToggle';
        this.init();
    }

    init() {
        // Load dark mode state from localStorage
        this.loadDarkModeState();
        
        // Setup all listeners
        this.setupToggleListener();
        this.setupFormListener();
    }

    loadDarkModeState() {
        const darkModeFromStorage = localStorage.getItem('darkMode');
        
        if (darkModeFromStorage === 'true') {
            this.enable();
        } else if (darkModeFromStorage === 'false') {
            this.disable();
        }
    }

    enable() {
        this.htmlElement.classList.add('dark');
        localStorage.setItem('darkMode', 'true');
        this.updateCheckbox(true);
    }

    disable() {
        this.htmlElement.classList.remove('dark');
        localStorage.setItem('darkMode', 'false');
        this.updateCheckbox(false);
    }

    toggle(isEnabled) {
        if (isEnabled) {
            this.enable();
        } else {
            this.disable();
        }
    }

    updateCheckbox(state) {
        const toggle = document.querySelector(this.darkModeToggleSelector);
        if (toggle) {
            toggle.checked = state;
        }
    }

    setupToggleListener() {
        const setupListener = () => {
            const darkModeToggle = document.querySelector(this.darkModeToggleSelector);
            if (darkModeToggle) {
                darkModeToggle.removeEventListener('change', this.onToggleChange.bind(this));
                darkModeToggle.addEventListener('change', this.onToggleChange.bind(this));
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupListener);
        } else {
            setupListener();
        }
    }

    onToggleChange(e) {
        this.toggle(e.target.checked);
    }

    setupFormListener() {
        const setupListener = () => {
            const form = document.querySelector('form[action*="settings.update"]');
            if (form) {
                form.addEventListener('submit', (e) => {
                    // Get the current dark mode state before form submission
                    const isChecked = document.querySelector(this.darkModeToggleSelector).checked;
                    // The form will handle saving to DB, we just maintain our local state
                    localStorage.setItem('darkMode', isChecked ? 'true' : 'false');
                });
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupListener);
        } else {
            setupListener();
        }
    }

    isEnabled() {
        return this.htmlElement.classList.contains('dark');
    }
}

// Initialize dark mode on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.darkModeManager = new DarkModeManager();
    });
} else {
    window.darkModeManager = new DarkModeManager();
}

Alpine.start();
