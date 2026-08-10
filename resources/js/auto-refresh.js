/**
 * Auto-Refresh Module for CryptoPay Dashboard
 * Refreshes page after operations (forms, AJAX) instead of time intervals
 */

class AutoRefreshManager {
    constructor(options = {}) {
        // Configuration
        this.enabled = options.enabled !== false; // Enabled by default
        this.mode = options.mode || 'event'; // 'event' or 'interval'
        this.refreshInterval = options.refreshInterval || 0; // 0 = no interval, only events
        this.refreshDelay = options.refreshDelay || 10000; // تأخیر بعد از آپریشن (پیش‌فرض 10 ثانیه)
        this.pollingInterval = options.pollingInterval || 10000; // Poll every 10 seconds
        this.storageKey = 'autoRefreshEnabled';
        this.modeKey = 'autoRefreshMode';
        
        // State
        this.intervalId = null;
        this.pollingId = null;
        this.isRefreshing = false;
        this.lastRefreshTime = 0;
        this.lastServerCheck = null;
        // Feature flags - set to false to hide UI elements
        this.showUI = false; // hide the header toggle container
        this.showIndicator = false; // hide the floating refresh indicator
        
        this.init();
    }

    init() {
        // Load preferences from localStorage
        this.loadPreferences();
        
        // Initialize last check time
        if (!localStorage.getItem('autoRefreshLastCheck')) {
            localStorage.setItem('autoRefreshLastCheck', new Date(Date.now() - 60000).toISOString());
        }
        
        // Setup UI controls
        this.setupControls();
        
        // Setup event listeners for operations
        this.setupEventListeners();
        
        // Start polling (for real-time updates from other users' actions)
        this.startPolling();
        
        // Start interval if mode is 'interval' and enabled
        if (this.enabled && this.mode === 'interval' && this.refreshInterval > 0) {
            this.startInterval();
        }
        
        // Listen for visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pause();
            } else {
                this.resume();
            }
        });
    }

    loadPreferences() {
        const storedEnabled = localStorage.getItem(this.storageKey);
        const storedMode = localStorage.getItem(this.modeKey);
        
        if (storedEnabled !== null) {
            this.enabled = storedEnabled === 'true';
        }
        
        if (storedMode !== null) {
            this.mode = storedMode;
        }
    }

    savePreferences() {
        localStorage.setItem(this.storageKey, this.enabled.toString());
        localStorage.setItem(this.modeKey, this.mode);
    }

    setupEventListeners() {
        // 1. Intercept form submissions
        document.addEventListener('submit', (e) => {
            if (this.enabled) {
                // Delay to allow server to process
                setTimeout(() => {
                    this.refresh('form_submit');
                }, this.refreshDelay);
            }
        }, true);

        // 2. Intercept Axios/Fetch requests
        if (window.axios) {
            window.axios.interceptors.response.use(
                (response) => {
                    try {
                        // Avoid triggering full-page auto-refresh for notification-related API calls
                        const respUrl = response?.config?.url || '';
                        if (this.enabled && response.status >= 200 && response.status < 300 && !/\/notifications(\/|$)/.test(respUrl)) {
                            setTimeout(() => {
                                this.refresh('ajax_success');
                            }, this.refreshDelay);
                        }
                    } catch (e) {
                        console.error('auto-refresh axios wrapper error:', e);
                    }

                    return response;
                },
                (error) => {
                    return Promise.reject(error);
                }
            );
        }

        // 3. Intercept fetch requests
        const originalFetch = window.fetch;
        // Capture the manager instance so the wrapper doesn't rely on a dynamic `this`
        const self = this;
        window.fetch = function(...args) {
            // Call the original fetch with the global/window context to avoid recursion
            return originalFetch.apply(window, args)
                .then(response => {
                    try {
                        if (self.enabled && response && response.ok) {
                            // Check if it's a POST/PUT/DELETE/PATCH request
                            const method = (args[1]?.method || 'GET').toUpperCase();
                            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                                // Inspect URL and skip auto-refresh for notification endpoints
                                const requestUrl = String(args[0] || '');
                                if (!/\/notifications(\/|$)/.test(requestUrl)) {
                                    setTimeout(() => {
                                        self.refresh('fetch_success');
                                    }, self.refreshDelay);
                                }
                            }
                        }
                    } catch (e) {
                        // Don't break the fetch promise chain if something goes wrong
                        console.error('auto-refresh fetch wrapper error:', e);
                    }
                    return response;
                });
        };

        // 4. Listen for Alpine.js or jQuery AJAX
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ajaxSuccess(() => {
                if (this.enabled) {
                    setTimeout(() => {
                        this.refresh('jquery_ajax');
                    }, this.refreshDelay);
                }
            });
        }
    }

    startInterval() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        
        this.intervalId = setInterval(() => {
            this.refresh('interval');
        }, this.refreshInterval);
    }

    startPolling() {
        if (this.pollingId) {
            clearInterval(this.pollingId);
        }
        
        // First check immediately
        this.checkServerChanges();
        
        // Then check every 30 seconds
        this.pollingId = setInterval(() => {
            this.checkServerChanges();
        }, this.pollingInterval);
    }

    checkServerChanges() {
        if (!this.enabled) {
            return;
        }

        // Get last refresh time from localStorage
        const lastRefresh = localStorage.getItem('autoRefreshLastCheck') || new Date(Date.now() - 60000).toISOString();
        
        // Call API to check for changes
        fetch(`/api/refresh-status?lastRefresh=${encodeURIComponent(lastRefresh)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update last check time
            localStorage.setItem('autoRefreshLastCheck', data.timestamp);

            // If there are changes, either perform a FORCE refresh (server requested)
            // or dispatch a 'server-change' event so the page can react without a full reload.
            if (data.hasChanges) {
                if (data.forceRefresh) {
                    this.refresh('server_update:' + (data.changeType || 'unknown'));
                } else {
                    try {
                        window.dispatchEvent(new CustomEvent('server-change', { detail: data }));
                    } catch (e) {
                        console.error('server-change event dispatch error:', e);
                    }
                }
            }
        })
        .catch(error => {
            console.log('Polling check error:', error);
            // Silent fail - don't break the page
        });
    }

    pausePolling() {
        if (this.pollingId) {
            clearInterval(this.pollingId);
            this.pollingId = null;
        }
    }

    resumePolling() {
        if (this.enabled) {
            this.startPolling();
        }
    }

    pause() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        this.pausePolling();
    }

    resume() {
        if (this.enabled && this.mode === 'interval' && this.refreshInterval > 0 && !this.intervalId) {
            this.startInterval();
        }
        this.resumePolling();
    }

    stop() {
        this.pause();
        this.enabled = false;
        this.savePreferences();
        this.updateUI();
    }

    toggle() {
        this.enabled = !this.enabled;
        this.savePreferences();
        
        if (this.enabled && this.mode === 'interval') {
            this.startInterval();
        } else if (!this.enabled) {
            this.pause();
        }
        
        this.updateUI();
    }

    setMode(mode) {
        if (!['event', 'interval'].includes(mode)) {
            console.warn('Invalid mode. Use "event" or "interval"');
            return;
        }
        
        this.mode = mode;
        this.savePreferences();
        
        if (mode === 'event') {
            this.pause();
        } else if (mode === 'interval' && this.enabled) {
            this.startInterval();
        }
    }

    refresh(source = 'manual') {
        if (!this.enabled) {
            return;
        }
        
        // Prevent too frequent refreshes (min 2 seconds apart)
        const now = Date.now();
        if (now - this.lastRefreshTime < 2000) {
            return;
        }
        
        if (this.isRefreshing) {
            return;
        }
        
        this.isRefreshing = true;
        this.lastRefreshTime = now;
        
        // Add visual feedback
        this.showRefreshIndicator(source);
        
        // Debugging: trace and suppress reload temporarily to identify callers
        console.trace('[AUTO-REFRESH] refresh triggered, source:', source);
        // Prevent an immediate full reload during investigation. Replace with a logged message so the page doesn't keep reloading while we debug.
        setTimeout(() => {
            console.warn('[AUTO-REFRESH] (DEBUG) reload suppressed. Source:', source);
            // Uncomment next line to actually reload after debugging is complete.
            // window.location.reload();
        }, 500);
    }

    showRefreshIndicator(source = 'manual') {
        if (!this.showIndicator) return; // indicator disabled by config
        let indicator = document.getElementById('auto-refresh-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'auto-refresh-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                animation: slideInRight 0.3s ease;
            `;
            document.body.appendChild(indicator);
        }
        
        const sourceMap = {
            'ajax_success': '🔄 آپدیت دریافت شد - ',
            'fetch_success': '⬇️  درخواست موفق - ',
            'jquery_ajax': '🔗 درخواست موفق - ',
            'interval': '⏱️  بروزرسانی دوره‌ای - ',
            'manual': '👆 بروزرسانی دستی - ',
            'server_update:transaction': '💰 تراکنش جدید - ',
            'server_update:payment_request': '📧 درخواست پرداخت جدید - ',
            'server_update:wallet_update': '💳 کیف پول بروزرسانی شد - '
        };
        
        indicator.innerHTML = `<i class="fas fa-sync-alt fa-spin"></i> ${sourceMap[source] || ''} بروزرسانی...`;
        indicator.style.display = 'flex';
        
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 1500);
    }

    setupControls() {
        const setupListener = () => {
            this.createControlUI();
            this.updateUI();
        };
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupListener);
        } else {
            setupListener();
        }
    }

    createControlUI() {
        if (!this.showUI) return; // UI disabled by config
        // Check if control already exists
        if (document.getElementById('auto-refresh-toggle')) {
            return;
        }
        
        // Find header
        let header = document.querySelector('header');
        if (!header) {
            return;
        }
        
        // Create container
        const container = document.createElement('div');
        container.id = 'auto-refresh-toggle-container';
        container.style.cssText = `
            padding: 12px 16px;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: inherit;
            font-size: 14px;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
            flex-wrap: wrap;
        `;
        
        container.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                <input type="checkbox" id="auto-refresh-toggle" 
                       style="cursor: pointer; width: 18px; height: 18px; accent-color: #4f46e5;">
                <label for="auto-refresh-toggle" 
                       style="cursor: pointer; user-select: none; margin: 0; font-weight: 500;">
                    <span id="auto-refresh-label">بروزرسانی خودکار</span>
                </label>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <select id="auto-refresh-mode" style="padding: 6px 10px; border-radius: 6px; border: 1px solid #ddd; cursor: pointer; font-size: 12px;">
                    <option value="event" selected>بعد از عملیات</option>
                    <option value="interval">هر 10 ثانیه</option>
                </select>
            </div>
        `;
        
        // Insert at end of header
        header.appendChild(container);
        
        // Setup event listeners
        const toggle = document.getElementById('auto-refresh-toggle');
        const modeSelect = document.getElementById('auto-refresh-mode');
        
        if (toggle) {
            toggle.addEventListener('change', () => {
                this.toggle();
            });
        }
        
        if (modeSelect) {
            modeSelect.addEventListener('change', (e) => {
                this.setMode(e.target.value);
                if (e.target.value === 'interval') {
                    this.refreshInterval = 10000; // 10 seconds
                    if (this.enabled) {
                        this.startInterval();
                    }
                } else {
                    this.pause();
                }
            });
        }
        
        // Add CSS animations
        if (!document.getElementById('auto-refresh-styles')) {
            const style = document.createElement('style');
            style.id = 'auto-refresh-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                .fa-spin {
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    from {
                        transform: rotate(0deg);
                    }
                    to {
                        transform: rotate(360deg);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    updateUI() {
        const toggle = document.getElementById('auto-refresh-toggle');
        const modeSelect = document.getElementById('auto-refresh-mode');
        
        if (toggle) {
            toggle.checked = this.enabled;
        }
        
        if (modeSelect) {
            modeSelect.value = this.mode;
        }
        
        const label = document.getElementById('auto-refresh-label');
        if (label) {
            if (!this.enabled) {
                label.textContent = 'بروزرسانی خودکار غیرفعال';
            } else if (this.mode === 'event') {
                label.textContent = 'بروزرسانی خودکار (بعد از عملیات)';
            } else {
                label.textContent = 'بروزرسانی خودکار (هر 10 ثانیه)';
            }
        }
    }

    getStatus() {
        return {
            enabled: this.enabled,
            mode: this.mode,
            interval: this.refreshInterval,
            isRunning: this.intervalId !== null
        };
    }
}

// Initialize on document ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.autoRefreshManager) {
            window.autoRefreshManager = new AutoRefreshManager({
                mode: 'event', // Event-based by default
                enabled: true,
                refreshDelay: 10000 // 10 second delay after operation
            });
        }
    });
} else {
    if (!window.autoRefreshManager) {
        window.autoRefreshManager = new AutoRefreshManager({
            mode: 'event',
            enabled: true,
            refreshDelay: 10000
        });
    }
}

export default AutoRefreshManager;

