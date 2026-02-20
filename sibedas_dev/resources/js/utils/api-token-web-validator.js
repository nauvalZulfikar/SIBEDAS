/**
 * API Token Web Validator
 * Menangani validasi token API untuk web requests dan auto-logout
 */
class ApiTokenWebValidator {
    constructor() {
        this.isLoggingOut = false;
        this.checkInterval = null;
        this.lastCheckTime = 0;
        this.consecutiveErrors = 0;
        this.maxConsecutiveErrors = 3;
        this.init();
    }

    init() {
        console.log("API Token Web Validator initialized");

        // Start periodic validation
        this.startPeriodicValidation();

        // Intercept all AJAX requests
        this.interceptAjaxRequests();

        // Listen for page visibility changes
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                this.validateToken();
            }
        });

        // Listen for window focus
        window.addEventListener("focus", () => {
            this.validateToken();
        });
    }

    startPeriodicValidation() {
        // Check token validity every 15 seconds
        this.checkInterval = setInterval(() => {
            this.validateToken();
        }, 15000);
    }

    stopPeriodicValidation() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }

    async validateToken() {
        // Prevent multiple simultaneous checks
        if (this.isLoggingOut) {
            return;
        }

        // Prevent checking too frequently
        const now = Date.now();
        if (now - this.lastCheckTime < 3000) {
            return;
        }

        this.lastCheckTime = now;

        try {
            const response = await fetch("/api/check-session", {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "Cache-Control": "no-cache",
                },
                credentials: "include",
            });

            if (response.status === 401) {
                console.log("Token validation failed: 401 Unauthorized");
                this.consecutiveErrors++;

                if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
                    this.handleTokenInvalid(
                        "Token API tidak valid. User lain mungkin telah login."
                    );
                }
            } else if (response.status === 200) {
                // Reset consecutive errors on successful response
                this.consecutiveErrors = 0;

                const data = await response.json();
                if (!data.valid) {
                    console.log("Token validation failed: Session invalid");
                    this.handleTokenInvalid(
                        "Session tidak valid. Silakan login ulang."
                    );
                }
            }
        } catch (error) {
            console.error("Token validation error:", error);
            this.consecutiveErrors++;

            if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
                this.handleTokenInvalid(
                    "Terjadi kesalahan koneksi. Silakan login ulang."
                );
            }
        }
    }

    interceptAjaxRequests() {
        // Intercept fetch requests
        const originalFetch = window.fetch;
        const validator = this;

        window.fetch = async function (...args) {
            try {
                const response = await originalFetch(...args);

                // Check if response is 401 and it's an API call
                if (response.status === 401) {
                    const url = args[0];
                    if (typeof url === "string" && url.includes("/api/")) {
                        console.log("401 detected in API fetch request:", url);
                        validator.handleApiError401(response, url);
                    }
                }

                return response;
            } catch (error) {
                console.error("Fetch request failed:", error);
                throw error;
            }
        };

        // Intercept XMLHttpRequest
        const originalXHRSend = XMLHttpRequest.prototype.send;
        const originalXHROpen = XMLHttpRequest.prototype.open;

        XMLHttpRequest.prototype.open = function (...args) {
            this._url = args[1];
            return originalXHROpen.apply(this, args);
        };

        XMLHttpRequest.prototype.send = function (...args) {
            const xhr = this;
            const originalOnReadyStateChange = xhr.onreadystatechange;

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 401) {
                    if (xhr._url && xhr._url.includes("/api/")) {
                        console.log(
                            "401 detected in API XHR request:",
                            xhr._url
                        );
                        validator.handleApiError401(null, xhr._url);
                    }
                }

                if (originalOnReadyStateChange) {
                    originalOnReadyStateChange.apply(xhr, arguments);
                }
            };

            return originalXHRSend.apply(this, args);
        };

        // Intercept jQuery AJAX if available
        if (typeof $ !== "undefined" && $.ajaxSetup) {
            $(document).ajaxError(function (event, xhr, settings, thrownError) {
                if (
                    xhr.status === 401 &&
                    settings.url &&
                    settings.url.includes("/api/")
                ) {
                    console.log(
                        "401 detected in jQuery AJAX request:",
                        settings.url
                    );
                    validator.handleApiError401(null, settings.url);
                }
            });
        }
    }

    handleApiError401(response, url) {
        if (this.isLoggingOut) {
            return;
        }

        console.log(`API 401 Error detected on ${url}`);

        // Increment consecutive errors
        this.consecutiveErrors++;

        // If we get multiple 401s, likely token is invalid
        if (this.consecutiveErrors >= 2) {
            this.handleTokenInvalid(
                "Token API tidak valid. User lain telah login atau session berakhir."
            );
        }
    }

    handleTokenInvalid(message) {
        if (this.isLoggingOut) {
            return;
        }

        this.isLoggingOut = true;
        this.stopPeriodicValidation();

        console.log("Handling token invalid:", message);

        // Show notification
        this.showNotification(message, "warning");

        // Clear any stored data
        this.clearStoredData();

        // Redirect to login after 3 seconds
        setTimeout(() => {
            this.forceLogout();
        }, 3000);
    }

    showNotification(message, type = "info") {
        // Try different notification libraries
        if (typeof toastr !== "undefined") {
            toastr[type](message);
        } else if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Peringatan Session",
                text: message,
                icon: type,
                confirmButtonText: "OK",
                allowOutsideClick: false,
                timer: 5000,
                timerProgressBar: true,
            });
        } else {
            // Create custom notification
            this.createCustomNotification(message, type);
        }
    }

    createCustomNotification(message, type) {
        const notification = document.createElement("div");
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === "warning" ? "#ffc107" : "#007bff"};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 9999;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
            font-size: 14px;
        `;
        notification.innerHTML = `
            <strong>Peringatan!</strong><br>
            ${message}
        `;

        document.body.appendChild(notification);

        // Remove after 8 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 8000);
    }

    clearStoredData() {
        // Clear localStorage
        try {
            localStorage.clear();
        } catch (e) {
            console.warn("Could not clear localStorage:", e);
        }

        // Clear sessionStorage
        try {
            sessionStorage.clear();
        } catch (e) {
            console.warn("Could not clear sessionStorage:", e);
        }
    }

    forceLogout() {
        console.log("Forcing logout...");

        // Try to logout via API first
        fetch("/logout", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "include",
        })
            .then(() => {
                window.location.href = "/login";
            })
            .catch(() => {
                // Force redirect even if logout fails
                window.location.href = "/login";
            });
    }

    // Method untuk manual reset
    reset() {
        this.consecutiveErrors = 0;
        this.isLoggingOut = false;
        this.lastCheckTime = 0;
    }

    // Method untuk manual logout
    logout() {
        this.handleTokenInvalid("Manual logout requested");
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    window.apiTokenWebValidator = new ApiTokenWebValidator();
});

// Export for module usage
if (typeof module !== "undefined" && module.exports) {
    module.exports = ApiTokenWebValidator;
}
