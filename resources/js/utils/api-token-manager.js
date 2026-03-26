/**
 * API Token Manager Utility
 * Handles API token generation, storage, and usage
 */
class ApiTokenManager {
    constructor() {
        this.token = null;
        this.tokenKey = 'api_token';
        this.init();
    }

    /**
     * Initialize token manager
     */
    init() {
        // Try to get token from meta tag first (session-based)
        const metaToken = document.querySelector('meta[name="api-token"]');
        if (metaToken && metaToken.getAttribute('content')) {
            this.token = metaToken.getAttribute('content');
        } else {
            // Try to get from localStorage as fallback
            this.token = localStorage.getItem(this.tokenKey);
        }
    }

    /**
     * Generate new API token
     * @returns {Promise<string|null>}
     */
    async generateToken() {
        try {
            const response = await fetch('/api-tokens/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.token) {
                this.setToken(data.token);
                return data.token;
            }
            
            throw new Error('No token received');
        } catch (error) {
            console.error('Failed to generate token:', error);
            return null;
        }
    }

    /**
     * Revoke current API token
     * @returns {Promise<boolean>}
     */
    async revokeToken() {
        try {
            const response = await fetch('/api-tokens/revoke', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Authorization': `Bearer ${this.token}`
                },
                credentials: 'include'
            });

            if (response.ok) {
                this.clearToken();
                return true;
            }
            
            return false;
        } catch (error) {
            console.error('Failed to revoke token:', error);
            return false;
        }
    }

    /**
     * Set token and update storage
     * @param {string} token 
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem(this.tokenKey, token);
        
        // Update meta tag if exists
        const metaTag = document.querySelector('meta[name="api-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', token);
        }
    }

    /**
     * Get current token
     * @returns {string|null}
     */
    getToken() {
        return this.token;
    }

    /**
     * Clear token from storage
     */
    clearToken() {
        this.token = null;
        localStorage.removeItem(this.tokenKey);
        
        // Clear meta tag if exists
        const metaTag = document.querySelector('meta[name="api-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', '');
        }
    }

    /**
     * Check if token exists
     * @returns {boolean}
     */
    hasToken() {
        return this.token !== null && this.token !== '';
    }

    /**
     * Get authorization header
     * @returns {object}
     */
    getAuthHeader() {
        if (!this.hasToken()) {
            return {};
        }
        
        return {
            'Authorization': `Bearer ${this.token}`
        };
    }

    /**
     * Make authenticated API request
     * @param {string} url 
     * @param {object} options 
     * @returns {Promise<Response>}
     */
    async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...this.getAuthHeader(),
                ...(options.headers || {})
            },
            credentials: 'include'
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {})
            }
        };

        try {
            const response = await fetch(url, mergedOptions);
            
            // If unauthorized, check if it's a session issue
            if (response.status === 401) {
                if (this.hasToken()) {
                    console.log('Token expired, generating new token...');
                    const newToken = await this.generateToken();
                    
                    if (newToken) {
                        // Retry request with new token
                        mergedOptions.headers.Authorization = `Bearer ${newToken}`;
                        return fetch(url, mergedOptions);
                    }
                }
                
                // If still 401, it might be a session issue
                console.log('Session invalid, redirecting to login...');
                this.handleSessionInvalid();
                return response;
            }
            
            return response;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    handleSessionInvalid() {
        // Show notification
        this.showNotification('Session Anda telah berakhir. Silakan login ulang.', 'warning');
        
        // Redirect to login after 3 seconds
        setTimeout(() => {
            window.location.href = '/login';
        }, 3000);
    }

    showNotification(message, type = 'info') {
        // Check if notification library exists
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Peringatan',
                text: message,
                icon: type,
                confirmButtonText: 'OK'
            });
        } else {
            // Fallback to alert
            alert(message);
        }
    }
}

// Export singleton instance
const apiTokenManager = new ApiTokenManager();
window.ApiTokenManager = apiTokenManager;

export default apiTokenManager; 