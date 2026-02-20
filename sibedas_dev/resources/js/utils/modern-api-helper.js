/**
 * Modern API Helper with Token Management
 */
import ApiTokenManager from './api-token-manager.js';

class ApiHelper {
    constructor() {
        this.baseUrl = window.GlobalConfig?.apiHost || '';
        this.tokenManager = ApiTokenManager;
    }

    /**
     * Make GET request
     * @param {string} endpoint 
     * @param {object} options 
     * @returns {Promise<any>}
     */
    async get(endpoint, options = {}) {
        return this.request(endpoint, {
            method: 'GET',
            ...options
        });
    }

    /**
     * Make POST request
     * @param {string} endpoint 
     * @param {object} data 
     * @param {object} options 
     * @returns {Promise<any>}
     */
    async post(endpoint, data = null, options = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: data ? JSON.stringify(data) : null,
            ...options
        });
    }

    /**
     * Make PUT request
     * @param {string} endpoint 
     * @param {object} data 
     * @param {object} options 
     * @returns {Promise<any>}
     */
    async put(endpoint, data = null, options = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: data ? JSON.stringify(data) : null,
            ...options
        });
    }

    /**
     * Make DELETE request
     * @param {string} endpoint 
     * @param {object} options 
     * @returns {Promise<any>}
     */
    async delete(endpoint, options = {}) {
        return this.request(endpoint, {
            method: 'DELETE',
            ...options
        });
    }

    /**
     * Make authenticated request using token manager
     * @param {string} endpoint 
     * @param {object} options 
     * @returns {Promise<any>}
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        try {
            const response = await this.tokenManager.request(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    /**
     * Upload file with authentication
     * @param {string} endpoint 
     * @param {FormData} formData 
     * @param {object} options 
     * @returns {Promise<any>}
     */
    async upload(endpoint, formData, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        const uploadOptions = {
            method: 'POST',
            body: formData,
            headers: {
                // Don't set Content-Type for FormData, let browser set it
                ...this.tokenManager.getAuthHeader(),
                ...(options.headers || {})
            },
            credentials: 'include',
            ...options
        };

        try {
            const response = await fetch(url, uploadOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Upload failed:', error);
            throw error;
        }
    }

    /**
     * Download file with authentication
     * @param {string} endpoint 
     * @param {string} filename 
     * @param {object} options 
     */
    async download(endpoint, filename = null, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        try {
            const response = await this.tokenManager.request(url, {
                method: 'GET',
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename || 'download';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);
        } catch (error) {
            console.error('Download failed:', error);
            throw error;
        }
    }
}

// Export singleton instance
const apiHelper = new ApiHelper();
window.ApiHelper = apiHelper;

export default apiHelper; 